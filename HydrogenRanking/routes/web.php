<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\WebRankingController;
use App\Http\Controllers\Web\AccountController as WebAccountController;

/*
 Web Routes
 ブラウザ向けルート定義（Blade + セッション認証）
*/

// ログイン関連
Route::get('/login',  [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');

// 認証必須ルート
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // ランキング画面
    Route::get('/', fn () => redirect()->route('ranking.index'));
    Route::get('/ranking', [WebRankingController::class, 'index'])->name('ranking.index');

    // アカウント削除
    Route::delete('/account', [WebAccountController::class, 'destroy'])->name('account.destroy');
});
