<?php

namespace App\Support;

use App\Exceptions\RecipeImportException;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Recept ophalen uit een link: leest de schema.org Recipe (JSON-LD) die vrijwel alle receptensites meesturen.
 *
 * De server haalt een adres op dat de gebruiker invult, dus: alleen http(s), geen interne/privé adressen
 * (ook niet na een redirect), het opgezochte IP wordt vastgezet (geen DNS-rebinding), korte timeout en maximale grootte.
 */
class RecipeImporter
{
    private const MAX_REDIRECTS = 3;

    private const MAX_BYTES = 3_000_000;

    /** @return array{title: string, servings: ?int, instructions: string, ingredients: list<string>, tags: list<string>} */
    public function import(string $url): array
    {
        $html = $this->fetch(trim($url));
        $recipe = $this->findRecipe($html);

        if ($recipe === null) {
            throw new RecipeImportException('Geen recept gevonden op deze pagina');
        }

        return $this->fromSchema($recipe);
    }

    /**
     * Een schema.org Recipe (als array) omzetten naar de velden van het formulier.
     * Ook gebruikt door "Naar Takkie" (bladwijzer/Opdracht): dan leest de browser van de gebruiker het recept uit de pagina.
     *
     * @return array{title: string, servings: ?int, instructions: string, ingredients: list<string>, tags: list<string>}
     */
    public function fromSchema(array $recipe): array
    {
        return [
            'title' => mb_substr($this->clean($recipe['name'] ?? ''), 0, 255),
            'servings' => $this->servings($recipe['recipeYield'] ?? null),
            'instructions' => $this->instructions($recipe['recipeInstructions'] ?? ''),
            'ingredients' => collect($this->list($recipe['recipeIngredient'] ?? $recipe['ingredients'] ?? []))
                ->map(fn ($item) => mb_substr($this->clean($item), 0, 255))
                ->filter()->values()->take(200)->all(),
            'tags' => collect([$recipe['keywords'] ?? [], $recipe['recipeCategory'] ?? [], $recipe['recipeCuisine'] ?? []])
                ->flatMap(fn ($value) => is_string($value) ? explode(',', $value) : $this->list($value))
                ->map(fn ($tag) => mb_substr($this->clean($tag), 0, 50))
                ->filter()->unique(fn ($tag) => mb_strtolower($tag))->values()->take(20)->all(),
        ];
    }

    private function fetch(string $url): string
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            [$host, $port] = $this->validateUrl($url);
            $ip = $this->safeIp($host);

            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Takkie recept-import', 'Accept' => 'text/html'])
                    ->withOptions([
                        'allow_redirects' => false,
                        'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]],
                    ])
                    ->get($url);
            } catch (ConnectionException) {
                throw new RecipeImportException('De pagina kon niet worden opgehaald');
            }

            if ($response->redirect() && $response->header('Location')) {
                $url = $this->absolute($response->header('Location'), $url);

                continue;
            }

            if (in_array($response->status(), [401, 403, 429], true)) {
                throw new RecipeImportException('Deze website blokkeert het ophalen door apps. Neem het recept over met kopiëren en plakken.');
            }

            if (! $response->successful()) {
                throw new RecipeImportException('De pagina kon niet worden opgehaald');
            }

            if (strlen($response->body()) > self::MAX_BYTES) {
                throw new RecipeImportException('Deze pagina is te groot');
            }

            return $response->body();
        }

        throw new RecipeImportException('Te veel doorverwijzingen');
    }

    /** @return array{0: string, 1: int} */
    private function validateUrl(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new RecipeImportException('Vul een geldige link in (https://…)');
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! in_array($port, [80, 443], true)) {
            throw new RecipeImportException('Vul een geldige link in (https://…)');
        }

        return [strtolower($parts['host']), $port];
    }

    private function safeIp(string $host): string
    {
        $ips = $this->resolve($host);

        if ($ips === []) {
            throw new RecipeImportException('Deze website bestaat niet');
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new RecipeImportException('Dit adres is niet toegestaan');
            }
        }

        return $ips[0];
    }

    /** IPv4-adressen van de host (of de host zelf als het al een IP is). Overschrijfbaar in tests. */
    protected function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        return gethostbynamel($host) ?: [];
    }

    private function absolute(string $location, string $base): string
    {
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parts = parse_url($base);
        $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        return str_starts_with($location, '/') ? $origin.$location : $origin.'/'.$location;
    }

    private function findRecipe(string $html): ?array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NONET);
        libxml_clear_errors();

        foreach ((new DOMXPath($dom))->query('//script[@type="application/ld+json"]') as $script) {
            $data = json_decode(trim($script->textContent), true);

            if (is_array($data) && ($recipe = $this->searchRecipe($data))) {
                return $recipe;
            }
        }

        return $this->findRecipeInNextFlight($html);
    }

    /**
     * Next.js-sites (bijv. Picnic) zetten de JSON-LD niet als <script> in de HTML maar in de "flight"-data:
     * self.__next_f.push([1,"…"]) met daarin tekstblokken als `25:T72d,{json}` (lengte in bytes, hexadecimaal).
     */
    private function findRecipeInNextFlight(string $html): ?array
    {
        // possessief (++ / *+): anders loopt PCRE vast op de lange stukken (JIT stack limit)
        if (! preg_match_all('#self\.__next_f\.push\(\[1,("(?:[^"\\\\]++|\\\\.)*+")\]\)#s', $html, $matches)) {
            return null;
        }

        $flight = implode('', array_map(fn (string $chunk) => (string) json_decode($chunk), $matches[1]));

        preg_match_all('/(?:^|\n)[0-9a-f]+:T([0-9a-f]+),/', $flight, $blocks, PREG_OFFSET_CAPTURE);

        foreach ($blocks[1] as $index => [$length]) {
            $start = $blocks[0][$index][1] + strlen($blocks[0][$index][0]);
            $data = json_decode(substr($flight, $start, hexdec($length)), true);

            if (is_array($data) && ($recipe = $this->searchRecipe($data))) {
                return $recipe;
            }
        }

        return null;
    }

    private function searchRecipe(array $node): ?array
    {
        $types = (array) ($node['@type'] ?? []);

        if (in_array('Recipe', $types, true)) {
            return $node;
        }

        foreach (array_is_list($node) ? $node : ($node['@graph'] ?? []) as $child) {
            if (is_array($child) && ($recipe = $this->searchRecipe($child))) {
                return $recipe;
            }
        }

        return null;
    }

    /** Stappen als tekst, één stap per regel; secties krijgen hun naam als kopje. */
    private function instructions(mixed $value): string
    {
        $lines = [];

        $walk = function (mixed $node) use (&$walk, &$lines): void {
            if (is_string($node)) {
                $lines[] = $this->clean($node);
            } elseif (is_array($node) && array_is_list($node)) {
                array_map($walk, $node);
            } elseif (is_array($node) && in_array('HowToSection', (array) ($node['@type'] ?? []), true)) {
                $lines[] = $this->clean($node['name'] ?? '');
                $walk($node['itemListElement'] ?? []);
            } elseif (is_array($node)) {
                $lines[] = $this->clean($node['text'] ?? $node['name'] ?? '');
            }
        };
        $walk($value);

        return mb_substr(implode("\n", array_filter($lines)), 0, 19000);
    }

    /** recipeYield: "4", 4, "4 personen" of ["4", "4 servings"] → 4 (1–50), anders null. */
    private function servings(mixed $value): ?int
    {
        foreach ((array) $value as $item) {
            if (preg_match('/\d+/', (string) $item, $match) && (int) $match[0] >= 1 && (int) $match[0] <= 50) {
                return (int) $match[0];
            }
        }

        return null;
    }

    private function list(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_string')) : (is_string($value) ? [$value] : []);
    }

    private function clean(mixed $text): string
    {
        // gegevens uit de browser (Naar Takkie) kunnen van alles bevatten
        if (! is_scalar($text)) {
            return '';
        }

        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
