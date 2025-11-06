<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Score モデル
 *
 * 各ユーザーのゲームスコアを保持するモデル。
 * 
 * このテーブルはランキング集計の元データとして使用される。
 * 1プレイごとに1レコードが登録される設計で、
 * modeid ごとに複数のスコアを持つことができる。
 */
class Score extends Model
{
    /**
     * 一括代入を許可するカラム
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',  // スコアを登録したユーザーのID
        'name',     // プレイヤー名（匿名時は "NoName" 等）
        'modeid',   // ゲームモード識別ID（例：1=ノーマル、2=ハードなど）
        'score',    // 獲得スコア（整数）
    ];
}
