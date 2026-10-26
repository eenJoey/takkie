<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth;
use App\Livewire\Lists;
use Illuminate\Support\Facades\Route;

// Inloggen, account maken en wachtwoord-reset (M2, 1-op-1 zoals vinkie)
Route::middleware('guest')->group(function () {
    Route::get('/login', Auth\Login::class)->name('login');
    Route::get('/forgot-password', Auth\ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', Auth\ResetPassword::class)->name('password.reset');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(['auth', 'household'])->group(function () {
    Route::get('/', Lists\Index::class)->name('lists.index');
    Route::get('/lists/{list}', Lists\Show::class)->name('lists.show');
});
