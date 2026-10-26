<?php

namespace App\Models;

use App\Enums\RecipeVisibility;
use App\Models\Concerns\BelongsToHousehold;
use App\Models\Scopes\HouseholdScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Recept van een huishouden; kan openbaar gemaakt worden (MIGRATIE-PLAN.md, sectie e). */
#[Fillable(['household_id', 'created_by', 'source_recipe_id', 'title', 'slug', 'instructions', 'visibility', 'published_at', 'copied_at'])]
class Recipe extends Model
{
    use BelongsToHousehold;

    /** Paden onder /recipes die geen slug mogen zijn */
    public const RESERVED_SLUGS = ['create', 'discover'];

    protected $attributes = [
        'visibility' => 'private',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $recipe): void {
            $recipe->slug ??= self::uniqueSlug($recipe->title);
        });
    }

    protected function casts(): array
    {
        return [
            'visibility' => RecipeVisibility::class,
            'published_at' => 'datetime',
            'copied_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(RecipeTag::class)->orderBy('name');
    }

    /** Kopie van een openbaar recept (alleen-lezen). Ook als het origineel inmiddels verwijderd is. */
    public function isCopy(): bool
    {
        return $this->copied_at !== null;
    }

    /** Het origineel; buiten de huishoud-scope, want het hoort bij een ander huishouden. */
    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_recipe_id')->withoutGlobalScope(HouseholdScope::class);
    }

    public function isPublic(): bool
    {
        return $this->visibility === RecipeVisibility::Public;
    }

    public function setVisibility(RecipeVisibility $visibility): void
    {
        $this->visibility = $visibility;

        if ($visibility === RecipeVisibility::Public) {
            $this->published_at ??= now();
        }
    }

    /** Openbare recepten van alle huishoudens (uitzondering op de huishoud-scope). */
    public static function publicQuery(): Builder
    {
        return static::withoutGlobalScope(HouseholdScope::class)->where('visibility', RecipeVisibility::Public);
    }

    /** Zoeken op titel, ingrediënt of tag (eerst LIKE; FULLTEXT-indexen staan klaar). */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        $tagSlug = Str::slug($term);

        $query->where(function (Builder $query) use ($like, $tagSlug) {
            $query->where('title', 'like', $like)
                ->orWhereHas('ingredients', fn (Builder $q) => $q->withoutGlobalScope(HouseholdScope::class)->where('text', 'like', $like))
                ->orWhereHas('tags', fn (Builder $q) => $q->withoutGlobalScope(HouseholdScope::class)->where('slug', $tagSlug));
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'recept';

        if (in_array($base, self::RESERVED_SLUGS, true)) {
            $base .= '-recept';
        }

        $slug = $base;
        $counter = 2;

        while (static::withoutGlobalScopes()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
