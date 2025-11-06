<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * アカウント削除コントローラ
 *
 * ログイン中ユーザー自身のアカウントおよび関連データを削除する。
 * - 関連スコア（scores）を先に削除
 * - 例外発生時はロールバックし、ログ出力
 */
class AccountController extends Controller
{
    /**
     * アカウント削除処理
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // ログイン中ユーザーの取得
        $user = $request->user(); // 認証済みユーザーを取得
        if (!$user) {
            // 未ログイン時はログインページへリダイレクト
            return redirect()->route('login');
        }

        // トランザクションで安全に削除処理を実行
        try {
            DB::transaction(function () use ($user) {

                // 関連テーブル削除（scoresテーブル）
                // 外部キー制約に CASCADE が設定されていない場合に備えて明示削除
                if (method_exists($user, 'scores')) {
                    // SoftDeletes を使用していない前提。
                    // SoftDeletesを使用している場合は ->forceDelete() に変更。
                    $user->scores()->delete();
                }

                // ユーザー本体の削除処理
                // SoftDeletes 対応：
                // モデルが SoftDeletes trait を持つ場合は forceDelete() で物理削除。
                // 持たない場合は delete() = 通常削除。
                if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($user))) {
                    $user->forceDelete(); // 論理削除モデル → 物理削除
                } else {
                    $user->delete(); // 通常モデル → 物理削除
                }
            });
        } catch (\Throwable $e) {
            // 例外発生時（DBエラーなど）はログ出力して処理を中断
            Log::error('Account delete failed', [
                'user_id' => $user->id,
                'err'     => $e->getMessage(),
            ]);

            // 元のページに戻してメッセージ表示
            return back()->with('status', '削除に失敗しました（ログを確認してください）');
        }

        // 認証セッションの終了処理
        // - ログアウト
        // - セッション無効化（セキュリティ確保）
        // - トークン再生成（CSRF防止）
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 完了メッセージ付きでログインページへリダイレクト
        return redirect()->route('login')
            ->with('status', 'アカウントを削除しました');
    }
}
