<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('cost_points');
            $table->string('icon', 16)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('archived')->default(false);
            $table->datetimes();
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('redeemed_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('points_spent');
            $table->dateTime('redeemed_at');

            $table->index(['household_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('rewards');
    }
};
