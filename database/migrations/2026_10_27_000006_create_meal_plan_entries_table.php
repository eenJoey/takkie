<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // weekmenu: per huishouden per dag één gerecht (eigen recept of vrij ingevuld)
        Schema::create('meal_plan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            // titel blijft staan als het recept verdwijnt; bij vrij invullen de enige bron
            $table->string('title');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetimes();

            $table->unique(['household_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_entries');
    }
};
