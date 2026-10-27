<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // voor hoeveel personen de hoeveelheden in het recept zijn (basis voor porties omrekenen)
            $table->unsignedTinyInteger('servings')->nullable()->after('instructions');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('servings');
        });
    }
};
