<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('instructions')->nullable();
            $table->string('visibility', 16)->default('private');
            $table->dateTime('published_at')->nullable();
            $table->datetimes();

            $table->index(['visibility', 'published_at']);
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->boolean('is_checked')->default(false);
            $table->datetimes();
        });

        Schema::create('recipe_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 60)->index();

            $table->unique(['recipe_id', 'slug']);
        });

        // FULLTEXT-indexen voor een latere overstap van LIKE naar MATCH … AGAINST (MySQL/MariaDB)
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
            Schema::table('recipes', fn (Blueprint $table) => $table->fullText(['title', 'instructions']));
            Schema::table('recipe_ingredients', fn (Blueprint $table) => $table->fullText('text'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_tags');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};
