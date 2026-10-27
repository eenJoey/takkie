<?php

namespace Tests\Feature;

use App\Actions\CreateHousehold;
use App\Exceptions\RecipeImportException;
use App\Livewire\Recipes\Form;
use App\Models\User;
use App\Support\NaarTakkie;
use App\Support\RecipeImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RecipeImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // DNS in tests: voorbeeld.test is publiek, intern.test wijst naar het interne netwerk
        $this->app->bind(RecipeImporter::class, fn () => new class extends RecipeImporter
        {
            protected function resolve(string $host): array
            {
                return match ($host) {
                    'intern.test' => ['10.0.0.5'],
                    'localhost' => ['127.0.0.1'],
                    default => filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ['93.184.216.34'],
                };
            }
        });

        $user = User::factory()->create();
        app(CreateHousehold::class)->handle($user, 'Thuis');
        $this->actingAs($user);
    }

    private function page(array $jsonLd): string
    {
        return '<html><head><script type="application/ld+json">'.json_encode($jsonLd).'</script></head><body>Hoi</body></html>';
    }

    public function test_reads_a_schema_org_recipe_and_fills_the_form(): void
    {
        Http::fake(['https://voorbeeld.test/*' => Http::response($this->page(['@context' => 'https://schema.org', '@graph' => [
            ['@type' => 'WebPage', 'name' => 'Pagina'],
            [
                '@type' => ['Recipe'],
                'name' => 'Pasta &amp; pesto',
                'recipeIngredient' => ['200 g pasta', '1 ui, gesnipperd', ''],
                'recipeInstructions' => [
                    ['@type' => 'HowToStep', 'text' => 'Kook de <b>pasta</b>.'],
                    ['@type' => 'HowToSection', 'name' => 'Saus', 'itemListElement' => [['@type' => 'HowToStep', 'text' => 'Meng de pesto.']]],
                ],
                'recipeYield' => ['4', '4 personen'],
                'keywords' => 'pasta, snel',
                'recipeCuisine' => ['Italiaans'],
            ],
        ]]))]);

        Livewire::test(Form::class)
            ->set('importUrl', 'https://voorbeeld.test/recept/pasta')
            ->call('importFromUrl')
            ->assertSet('title', 'Pasta & pesto')
            ->assertSet('servings', 4)
            ->assertSet('items', ['200 g pasta', '1 ui, gesnipperd'])
            ->assertSet('tags', 'pasta, snel, Italiaans')
            ->assertSet('instructions', "Kook de pasta.\nSaus\nMeng de pesto.\n\nBron: https://voorbeeld.test/recept/pasta")
            ->assertDispatched('toast', type: 'success', message: 'Recept opgehaald. Controleer het en sla op.');
    }

    public function test_follows_safe_redirects(): void
    {
        Http::fake([
            'https://voorbeeld.test/kort' => Http::response('', 301, ['Location' => '/recept']),
            'https://voorbeeld.test/recept' => Http::response($this->page(['@type' => 'Recipe', 'name' => 'Soep', 'recipeInstructions' => 'Koken'])),
        ]);

        $this->assertSame('Soep', app(RecipeImporter::class)->import('https://voorbeeld.test/kort')['title']);
    }

    public function test_blocks_internal_addresses_also_after_a_redirect(): void
    {
        Http::fake([
            'https://voorbeeld.test/stiekem' => Http::response('', 302, ['Location' => 'http://intern.test/admin']),
            '*' => Http::response($this->page(['@type' => 'Recipe', 'name' => 'Mag niet'])),
        ]);

        foreach (['http://localhost/', 'http://127.0.0.1/', 'http://intern.test/', 'http://169.254.169.254/latest', 'https://voorbeeld.test/stiekem'] as $url) {
            try {
                app(RecipeImporter::class)->import($url);
                $this->fail("{$url} had geweigerd moeten worden");
            } catch (RecipeImportException $e) {
                $this->assertSame('Dit adres is niet toegestaan', $e->getMessage());
            }
        }

        foreach (['ftp://voorbeeld.test/', 'file:///etc/passwd', 'https://user:pw@voorbeeld.test/', 'https://voorbeeld.test:8080/'] as $url) {
            try {
                app(RecipeImporter::class)->import($url);
                $this->fail("{$url} had geweigerd moeten worden");
            } catch (RecipeImportException $e) {
                $this->assertSame('Vul een geldige link in (https://…)', $e->getMessage());
            }
        }

        Http::assertSentCount(1);
    }

    public function test_page_without_recipe_gives_a_friendly_error(): void
    {
        Http::fake(['*' => Http::response('<html><body>Geen recept</body></html>')]);

        Livewire::test(Form::class)
            ->set('importUrl', 'https://voorbeeld.test/blog')
            ->call('importFromUrl')
            ->assertDispatched('toast', type: 'error', message: 'Geen recept gevonden op deze pagina')
            ->assertSet('title', '');
    }

    public function test_reads_a_recipe_from_next_js_flight_data_like_picnic(): void
    {
        $json = json_encode(['@context' => 'https://schema.org', '@type' => 'Recipe', 'name' => "Burrito's met gehakt", 'recipeIngredient' => ['300 g gehakt', '8 wraps'], 'recipeInstructions' => [['@type' => 'HowToStep', 'text' => 'Bak het gehakt.']]]);
        // groot stuk ervoor, zoals op een echte pagina (liep eerder vast op de PCRE JIT stack)
        $flight = '0:'.str_repeat('["$","div",null,{"className":"x"}]', 20000)."\n25:T".dechex(strlen($json)).','.$json."\n26:[]";
        $html = '<html><body><script>self.__next_f.push([1,'.json_encode($flight).'])</script></body></html>';
        Http::fake(['*' => Http::response($html)]);

        $recipe = app(RecipeImporter::class)->import('https://voorbeeld.test/nl/recepten/burritos');

        $this->assertSame("Burrito's met gehakt", $recipe['title']);
        $this->assertSame(['300 g gehakt', '8 wraps'], $recipe['ingredients']);
        $this->assertSame('Bak het gehakt.', $recipe['instructions']);
    }

    public function test_sites_that_block_apps_get_a_clear_message(): void
    {
        Http::fake(['*' => Http::response('Access Denied', 403)]);

        Livewire::test(Form::class)
            ->set('importUrl', 'https://voorbeeld.test/allerhande/recept/x')
            ->call('importFromUrl')
            ->assertDispatched('toast', type: 'error', message: 'Deze website blokkeert het ophalen door apps. Neem het recept over met kopiëren en plakken.');
    }

    public function test_naar_takkie_fills_the_form_from_the_users_browser(): void
    {
        Http::fake();

        // zoals het script het op ah.nl uitleest
        Livewire::test(Form::class)
            ->call('importFromSchema', [
                'name' => 'Broodje pittige kip',
                'recipeYield' => '4',
                'recipeIngredient' => ['60 g peen julienne', '60 g taugé'],
                'recipeInstructions' => [['@type' => 'HowToStep', 'name' => 'stap 1', 'text' => 'Doe de peen in een kom.']],
                'keywords' => ['Lunch'],
            ], 'https://www.ah.nl/allerhande/recept/R-R1195921/broodje-hete-kip')
            ->assertSet('title', 'Broodje pittige kip')
            ->assertSet('servings', 4)
            ->assertSet('items', ['60 g peen julienne', '60 g taugé'])
            ->assertSet('tags', 'Lunch')
            ->assertSet('instructions', "Doe de peen in een kom.\n\nBron: https://www.ah.nl/allerhande/recept/R-R1195921/broodje-hete-kip");

        // Takkie haalt zelf niets op
        Http::assertNothingSent();

        // rommel of geen recept: nette melding, geen fout
        Livewire::test(Form::class)
            ->call('importFromSchema', 'geen recept', 'javascript:alert(1)')
            ->assertDispatched('toast', type: 'error', message: 'Geen recept gevonden op deze pagina');
        Livewire::test(Form::class)
            ->call('importFromSchema', ['name' => ['raar'], 'recipeIngredient' => [['x' => 1], 'ui']], 'javascript:alert(1)')
            ->assertSet('items', ['ui'])
            ->assertSet('instructions', '');
    }

    public function test_naar_takkie_page_shows_bookmarklet_and_shortcut_code(): void
    {
        $this->get(route('recipes.send-to-takkie'))
            ->assertOk()
            ->assertSee('Naar Takkie')
            ->assertSee('javascript:', false)
            ->assertSee('Opdracht-code kopiëren');

        $this->assertStringStartsWith('javascript:', NaarTakkie::bookmarklet());
        $this->assertStringContainsString(json_encode(route('recipes.create'), JSON_UNESCAPED_SLASHES), NaarTakkie::shortcutScript());
        $this->assertStringEndsWith('completion(u);})()', NaarTakkie::shortcutScript());
    }
}
