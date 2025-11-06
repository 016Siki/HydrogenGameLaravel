<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User モデル
 *
 * アプリケーションのユーザー情報を管理するモデル。
 * 
 * Laravel の認証機能 (Authenticatable) を継承しており、
 * ログイン認証・パスワードハッシュ・APIトークン発行 (Sanctum) に対応。
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * 一括代入を許可するカラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',       // ユーザー名（表示名）
        'email',      // メールアドレス（ログインIDとして利用）
        'password',   // ハッシュ化されたパスワード
        'is_delete',  // 論理削除フラグ（true=削除済み）
    ];

    /**
     * JSON や配列に変換する際に非表示にする属性
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',        // セキュリティ保護のため非表示
        'remember_token',  // セッション維持用トークン（不要な場合は除外）
    ];
}
