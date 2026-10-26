<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('completed_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('points_awarded')->default(0);
            $table->dateTime('completed_at');

            $table->index(['household_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_completions');
    }
};
