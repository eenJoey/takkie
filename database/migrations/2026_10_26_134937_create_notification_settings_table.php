<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per gebruiker, niet per huishouden (uitzondering op de huishoudregel; zie MIGRATIE-PLAN.md sectie c)
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->json('times');
            $table->string('timezone', 64)->default('Europe/Amsterdam');
            $table->boolean('only_assigned')->default(false);
            $table->datetimes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
