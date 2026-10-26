<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // origineel: wanneer titel, instructies, ingrediënten of tags voor het laatst echt veranderden
            $table->dateTime('content_changed_at')->nullable()->after('published_at');
            // kopie: welke versie van het origineel (content_changed_at) is overgenomen of genegeerd
            $table->dateTime('source_synced_at')->nullable()->after('copied_at');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['content_changed_at', 'source_synced_at']);
        });
    }
};
