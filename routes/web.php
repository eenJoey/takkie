<?php

use App\Http\Controllers\Auth\TemporaryLoginController;
use App\Livewire\Lists;
use Illuminate\Support\Facades\Route;

// TIJDELIJK tot M2: alleen lokaal, logt de testgebruiker in
Route::get('/login', TemporaryLoginController::class)->middleware('guest')->name('login');

Route::middleware(['auth', 'household'])->group(function () {
    Route::get('/', Lists\Index::class)->name('lists.index');
    Route::get('/lists/{list}', Lists\Show::class)->name('lists.show');
});
