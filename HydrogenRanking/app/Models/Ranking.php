<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Ranking モデル
 *
 * ランキング情報を保持するデータモデル。
 * 
 * データベース上では、集計済みのランキングデータを JSON 形式で保存する。
 * 「日間」「月間」「総合」などの種類や、モードごとに集計結果をキャッシュ的に保持する用途。
 */
class Ranking extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可するカラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rank_type',     // ランキングの種類 ('daily', 'monthly', 'total' など)
        'modeid',        // ゲームモード識別ID
        'ranking_json',  // JSON形式で格納されたランキングデータ
    ];
}
