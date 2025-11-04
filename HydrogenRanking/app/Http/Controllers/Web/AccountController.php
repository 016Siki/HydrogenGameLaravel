<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function destroy(Request $request)
    {
        $user = $request->user();                    // ログイン中ユーザー
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            DB::transaction(function () use ($user) {
                // 先に関連データを明示削除（FKのCASCADEが無くても確実に通る）
                if (method_exists($user, 'scores')) {
                    // SoftDeletes を使っていない前提。使っているなら forceDelete に合わせる
                    $user->scores()->delete();
                }

                // SoftDeletes 対応：trait が乗っていれば forceDelete、無ければ delete = 物理削除
                if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($user))) {
                    $user->forceDelete();
                } else {
                    $user->delete();
                }
            });
        } catch (\Throwable $e) {
            Log::error('Account delete failed', ['user_id' => $user->id, 'err' => $e->getMessage()]);
            return back()->with('status', '削除に失敗しました（ログを確認してください）');
        }

        // セッション終了 & /login へ
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'アカウントを削除しました');
    }
}
