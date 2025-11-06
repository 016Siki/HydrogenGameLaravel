<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
/**
 * 認証関連コントローラ
 * ユーザー登録、ログイン、ログアウトを担当
 */
class AuthController extends Controller
{   
     /**
     * 新規ユーザー登録処理
     */
    public function register(Request $request)
    {
        // 入力データのバリデーション
        $v = $request->validate([
            'email'    => 'required|email|unique:users,email',// 必須・メール形式・重複禁止
            'password' => 'required|string|min:6',// 必須・文字列・6文字以上
            'name'     => 'nullable|string|max:50',// 任意・文字列・最大50文字
        ]);
         // 新しいユーザーを作成
        $user = User::create([
            'name'     => $v['name'] ?? 'user',// 名前が空なら「user」
            'email'    => $v['email'],
            'password' => Hash::make($v['password']), // パスワードをハッシュ化して保存
        ]);
         // Sanctumトークンを発行
        $token = $user->createToken('unity')->plainTextToken;
        // 登録結果をJSONで返す
        return response()->json(['token'=>$token,'user'=>[
            'id'=>$user->id,'name'=>$user->name,'email'=>$user->email
        ]], 201);
    }
    /**
     * ログイン処理
     */
public function login(Request $request)
{
    // 入力バリデーション
    $v = $request->validate([
        'email'    => 'required|email',// メール必須
        'password' => 'required|string',// パスワード必須
    ]);
 // 小文字・トリムしてメール検索（大文字小文字を区別しない）
    $email = strtolower(trim($v['email']));
    $user = \App\Models\User::whereRaw('LOWER(email) = ?', [$email])->first();
// ユーザー存在確認＆パスワード照合
    if (!$user || !\Illuminate\Support\Facades\Hash::check($v['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
// トークン発行
    $token = $user->createToken('unity')->plainTextToken;
// 成功時レスポンス
    return response()->json([
        'token' => $token,
        'user'  => ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email],
    ]);
        /**
     * ログアウト処理
     * 現在使用中のアクセストークンを削除
     */

}
    public function logout(Request $request)
    {
        // 現在のトークンを無効化
        $request->user()->currentAccessToken()?->delete();
        // 成功メッセージ返却
        return response()->json(['message'=>'Logged out']);
    }
}
