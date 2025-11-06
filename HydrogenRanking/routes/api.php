<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ScoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebRankingController;
use App\Http\Controllers\AccountController;

/*
 API Routes
 Sanctum 認証を利用した API エンドポイント群。
 Unity クライアントとの通信を
*/

/**
 * 認証関連エンドポイント
 */

// 新規登録
Route::post('/register', [AuthController::class, 'register']);

// ログイン（トークン発行）
Route::post('/login', [AuthController::class, 'login']);


/**
 * 認証必須ルート (Sanctum)
 * Sanctum 認証トークンが必要なAPI群。
 * → Unityから Authorization: Bearer {token} を付けてアクセス。
 */
Route::middleware('auth:sanctum')->group(function () {

    // ログイン中ユーザー情報を取得
    Route::get('/user', function (Request $request) {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
        ]);
    });

    // ログアウト（トークン削除）
    Route::post('/logout', [AuthController::class, 'logout']);

    /**
     * ランキング関連
     * GET /api/ranking?kinds=daily|monthly|total&modeid=1
     * ランキング一覧をUnityクライアントから取得
     */
    Route::get('/ranking', [RankingController::class, 'index']);

    /**
     * スコア登録
     * POST /api/scores
     * ゲーム終了後にスコアを送信して保存
     */
    Route::post('/scores', [ScoreController::class, 'store']);
});
