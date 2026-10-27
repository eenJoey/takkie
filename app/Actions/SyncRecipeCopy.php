<?php

namespace App\Actions;

use App\Models\Recipe;
use App\Models\Scopes\HouseholdScope;
use App\Support\CurrentHousehold;
use Illuminate\Support\Facades\DB;

/**
 * Nieuwe versie van het originele recept in een kopie: overnemen (inhoud vervangen) of negeren (melding weg).
 * Afvinkstatus blijft bewaard voor ingrediënten met dezelfde tekst, net als bij bewerken.
 */
class SyncRecipeCopy
{
    public function accept(Recipe $copy): void
    {
        $source = $copy->source;
        abort_unless($copy->hasSourceUpdate(), 409);

        // in het huishouden van de kopie, ook als de gebruiker nu in een ander huishouden zit
        app(CurrentHousehold::class)->runAs($copy->household, fn () => DB::transaction(function () use ($copy, $source) {
            $copy->update([
                'title' => $source->title,
                'instructions' => $source->instructions,
                'servings' => $source->servings,
                'source_synced_at' => $source->content_changed_at,
            ]);

            $checked = $copy->ingredients()->get()->groupBy('text')->map(fn ($group) => $group->pluck('is_checked')->all())->all();
            $copy->ingredients()->delete();

            foreach ($source->ingredients()->withoutGlobalScope(HouseholdScope::class)->get() as $ingredient) {
                $copy->ingredients()->create([
                    'text' => $ingredient->text,
                    'is_checked' => ! empty($checked[$ingredient->text]) && (bool) array_shift($checked[$ingredient->text]),
                ]);
            }

            $copy->tags()->delete();
            $source->tags()->withoutGlobalScope(HouseholdScope::class)->get()
                ->each(fn ($tag) => $copy->tags()->create(['name' => $tag->name, 'slug' => $tag->slug]));
        }));
    }

    public function dismiss(Recipe $copy): void
    {
        abort_unless($copy->hasSourceUpdate(), 409);

        $copy->update(['source_synced_at' => $copy->source->content_changed_at]);
    }
}
