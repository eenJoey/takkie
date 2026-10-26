<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth;
use App\Livewire\Household;
use App\Livewire\InviteShow;
use App\Livewire\Lists;
use App\Livewire\Onboarding;
use App\Livewire\Today;
use Illuminate\Support\Facades\Route;

// Inloggen, account maken en wachtwoord-reset (M2, 1-op-1 zoals vinkie)
Route::middleware('guest')->group(function () {
    Route::get('/login', Auth\Login::class)->name('login');
    Route::get('/forgot-password', Auth\ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', Auth\ResetPassword::class)->name('password.reset');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

// Uitnodiging: ook uitgelogd te bekijken (M3)
Route::get('/invite/{code}', InviteShow::class)->name('invites.show');

Route::middleware('auth')->group(function () {
    Route::get('/onboarding', Onboarding::class)->name('onboarding');
});

Route::middleware(['auth', 'household'])->group(function () {
    Route::get('/', Lists\Index::class)->name('lists.index');
    Route::get('/lists/{list}', Lists\Show::class)->name('lists.show');
    Route::get('/dashboard', Today::class)->name('dashboard');
    Route::get('/household', Household\Index::class)->name('household');
});
