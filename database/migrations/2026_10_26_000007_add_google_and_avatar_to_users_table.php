<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Alleen-Google-gebruikers (alle drie de huidige vinkie-gebruikers) hebben geen wachtwoord
            $table->string('password')->nullable()->change();
            $table->string('google_id')->nullable()->unique()->after('password');
            // storage-pad (avatars/{user_id}/…) of externe URL (Google), zoals vinkie's avatar_url
            $table->string('avatar')->nullable()->after('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
