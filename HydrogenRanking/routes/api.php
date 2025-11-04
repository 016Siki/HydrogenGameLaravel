<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ScoreController;   // ← 追加
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebRankingController;
use App\Http\Controllers\AccountController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    });
Route::post('/logout', [AuthController::class, 'logout']);
    // ランキング
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ranking', [RankingController::class, 'index']);
});
    // スコア登録
Route::post('/scores', [ScoreController::class, 'store']);
});