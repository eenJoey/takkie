<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // weekmenu: wie uit het huishouden kookt die dag (optioneel)
        Schema::table('meal_plan_entries', function (Blueprint $table) {
            $table->foreignId('cook_id')->nullable()->after('title')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('meal_plan_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cook_id');
        });
    }
};
