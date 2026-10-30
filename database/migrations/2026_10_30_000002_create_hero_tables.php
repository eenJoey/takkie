<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // elke XP-winst los, zodat levels altijd opnieuw uit te rekenen zijn
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('hero_class', 20);
            $table->unsignedInteger('amount');
            $table->string('source', 20); // task | cooked
            $table->foreignId('task_completion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->date('date'); // lokale datum (Europe/Amsterdam)
            $table->timestamp('created_at')->nullable();

            $table->index(['household_id', 'user_id', 'hero_class']);
            $table->index(['household_id', 'user_id', 'source', 'date']);
        });

        Schema::create('hero_prestiges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('hero_class', 20);
            $table->unsignedTinyInteger('prestige')->default(0);
            $table->unsignedInteger('xp_spent')->default(0); // XP die bij prestige "terug naar level 1" is opgebruikt
            $table->timestamps();

            $table->unique(['household_id', 'user_id', 'hero_class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_prestiges');
        Schema::dropIfExists('xp_events');
    }
};
