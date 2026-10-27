<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\AvatarController;
use App\Livewire\Auth;
use App\Livewire\Household;
use App\Livewire\InviteShow;
use App\Livewire\Lists;
use App\Livewire\Onboarding;
use App\Livewire\Profile;
use App\Livewire\Recipes;
use App\Livewire\Rewards;
use App\Livewire\Today;
use Illuminate\Support\Facades\Route;

// Inloggen, account maken en wachtwoord-reset (M2, 1-op-1 zoals vinkie)
Route::middleware('guest')->group(function () {
    Route::get('/login', Auth\Login::class)->name('login');
    Route::get('/forgot-password', Auth\ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', Auth\ResetPassword::class)->name('password.reset');

    // Google-login via Socialite (M8)
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

// Openbare recepten ontdekken: ook uitgelogd (M7). Vóór /recipes/{slug} registreren.
Route::get('/recipes/discover', Recipes\Discover::class)->name('recipes.discover');

// Uitnodiging: ook uitgelogd te bekijken (M3)
Route::get('/invite/{code}', InviteShow::class)->name('invites.show');

Route::middleware('auth')->group(function () {
    Route::get('/onboarding', Onboarding::class)->name('onboarding');
});

Route::middleware(['auth', 'household'])->group(function () {
    // Start: naar het gekozen starttabblad (profiel). Inloggen, onboarding en de PWA komen hier binnen.
    Route::get('/', fn () => redirect()->route(auth()->user()->startPage()->route()))->name('home');
    Route::get('/lijstjes', Lists\Index::class)->name('lists.index');
    Route::get('/lists/{list}', Lists\Show::class)->name('lists.show');
    Route::get('/dashboard', Today::class)->name('dashboard');
    Route::get('/taken', Lists\Show::class)->name('tasks');
    Route::get('/rewards', Rewards\Index::class)->name('rewards');
    Route::get('/recipes', Recipes\Index::class)->name('recipes.index');
    Route::get('/weekmenu', Recipes\WeekMenu::class)->name('weekmenu');
    Route::get('/recipes/create', Recipes\Form::class)->name('recipes.create');
    Route::get('/recipes/{recipe:slug}/edit', Recipes\Form::class)->name('recipes.edit');
    Route::get('/household', Household\Index::class)->name('household');
    Route::get('/profiel', Profile\Index::class)->name('profile');
    Route::get('/avatars/{user}', [AvatarController::class, 'show'])->name('avatars.show');
});

// Recept bekijken: leden altijd, anderen alleen als het openbaar is (M6/M7). Als laatste, vanwege {slug}.
Route::get('/recipes/{slug}', Recipes\Show::class)->name('recipes.show');
