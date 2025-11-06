<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Web認証コントローラ
 *
 * Webブラウザ経由のログイン・ログアウト処理を担当。
 */
class WebAuthController extends Controller
{
    /**
     * ログイン画面の表示
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function showLogin()
    {
        // すでにログイン済みならランキングページへリダイレクト
        if (Auth::check()) {
            return redirect()->route('ranking.index');
        }

        // 未ログインの場合はログイン画面を表示
        return view('auth.login');
    }

    /**
     * ログイン処理
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // ==============================================
        // ① フォーム入力のバリデーション
        // ==============================================
        // - email: 必須・メール形式
        // - password: 必須・6文字以上
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        // ==============================================
        // ② ログイン試行
        // ==============================================
        // Auth::attempt() は認証に成功すると true を返す
        // 第2引数は「ログイン状態を保持するか（Remember Me）」
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // セッションIDを再生成してセッション固定攻撃を防止
            $request->session()->regenerate();

            // intended() は、直前にアクセスしようとしたページに戻す
            // 無ければ ranking.index に遷移
            return redirect()->intended(route('ranking.index'));
        }

        // ==============================================
        // ③ ログイン失敗時の処理
        // ==============================================
        // エラーメッセージを返し、email入力欄のみ保持
        return back()
            ->withErrors(['email' => 'メールアドレスかパスワードが違います。'])
            ->onlyInput('email');
    }

    /**
     * ログアウト処理
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // ==============================================
        // ① ログアウト実行
        // ==============================================
        Auth::logout(); // 認証情報を破棄

        // ==============================================
        // ② セッションを無効化
        // ==============================================
        // - invalidate(): セッション情報を完全破棄
        // - regenerateToken(): CSRFトークン再生成
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ==============================================
        // ③ ログインページへリダイレクト
        // ==============================================
        return redirect()->route('login');
    }
}
