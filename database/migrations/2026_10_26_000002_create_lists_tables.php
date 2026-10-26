<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 16)->default('checklist');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetimes();

            $table->index(['household_id', 'created_at']);
        });

        Schema::create('list_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->constrained('lists')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 32);
            $table->datetimes();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->constrained('lists')->cascadeOnDelete();
            $table->foreignId('list_category_id')->nullable()->constrained('list_categories')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->string('recurrence_type', 16)->default('once');
            $table->unsignedSmallInteger('recurrence_interval')->default(1);
            $table->date('due_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->datetimes();

            $table->index(['household_id', 'due_date']);
            $table->index(['list_id', 'list_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('list_categories');
        Schema::dropIfExists('lists');
    }
};
