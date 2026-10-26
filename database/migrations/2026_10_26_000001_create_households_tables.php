<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->datetimes();
        });

        Schema::create('household_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->index()->constrained()->cascadeOnDelete();
            $table->string('role', 16)->default('member');
            $table->integer('points_balance')->default(0);
            $table->dateTime('joined_at');
            $table->datetimes();

            $table->unique(['household_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_household_id')->nullable()->after('password')
                ->constrained('households')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_household_id');
        });

        Schema::dropIfExists('household_user');
        Schema::dropIfExists('households');
    }
};
