<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\WebRankingController;
use App\Http\Controllers\Web\AccountController as WebAccountController;

Route::get('/login',  [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('ranking.index'));
    Route::get('/ranking', [WebRankingController::class, 'index'])->name('ranking.index');

Route::delete('/account', [WebAccountController::class, 'destroy'])->name('account.destroy');
});
