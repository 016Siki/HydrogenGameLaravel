<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Score;

/**
 * スコア登録コントローラ
 * 
 * ユーザーのスコアを受け取り、検証・整形してデータベースに保存する。
 * 主にゲームからのスコア送信APIとして利用される。
 */

class ScoreController extends Controller
{
    /**
     * スコア登録処理
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'score'  => ['required','integer','min:0','max:20000'],
            'modeid' => ['required','integer','min:1','max:1000'],
            'name'   => ['nullable','string','min:1','max:32'],
        ]);

        // ==========================================
        // ② 名前の整形・無害化処理
        // ==========================================
        // - 不正文字（絵文字・記号など）を除外
        // - 32文字に切り詰め
        // - 空欄または全除去後に空の場合は 'NoName' に置換

        $rawName = $validated['name'] ?? 'NoName';
        $name = trim(mb_substr(
            preg_replace('/[^\p{L}\p{N}\s_\-\.]/u', '', $rawName),
            0, 32
        ));
        if ($name === '') $name = 'NoName';
        // ==========================================
        // ③ スコアをデータベースに保存
        // ==========================================
        // - 認証中ユーザー（$request->user()->id）に紐づけて登録
        // - name, modeid, score を保存
        $score = \App\Models\Score::create([
            'user_id' => $request->user()->id,
            'name'    => $name,
            'modeid'  => $validated['modeid'],
            'score'   => $validated['score'],
        ]);
        // ==========================================
        // ④ レスポンス生成
        // ==========================================
        // - HTTPステータス201（Created）
        // - 登録データをJSON形式で返す
        return response()->json([
            'message' => 'ok',
            'data' => [
                'user_id' => $score->user_id,
                'name'    => $score->name,
                'score'   => $score->score,
                'modeid'  => $score->modeid,
                'updated_at'=> $score->updated_at,
                'created_at'=> $score->created_at,
                'id'        => $score->id,
            ],
        ], 201);
    }
}