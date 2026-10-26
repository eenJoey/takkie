<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // kopie van een openbaar recept: alleen-lezen; blijft bestaan als het origineel verdwijnt (dan wordt dit null)
            $table->foreignId('source_recipe_id')->nullable()->after('created_by')->constrained('recipes')->nullOnDelete();
            $table->dateTime('copied_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_recipe_id');
            $table->dropColumn('copied_at');
        });
    }
};
