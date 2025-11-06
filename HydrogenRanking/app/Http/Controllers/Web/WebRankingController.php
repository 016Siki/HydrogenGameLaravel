<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Score;

/**
 * Web用ランキング表示コントローラ
 *
 * 指定されたモードと期間（total / daily / monthly）に応じて
 * スコアランキングを集計し、ビューに渡す。
 */
class WebRankingController extends Controller
{
    /**
     * ランキングページ表示
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        // リクエストパラメータの取得・初期化
        //  kinds : 'daily'（日間） / 'monthly'（月間） / 'total'（全期間）
        //  modeid : ゲームモード識別用ID
        $kinds  = $request->query('kinds', 'total');
        $modeid = (int) $request->query('modeid', 1);

        // 不正値が入っていた場合は total に補正
        if (!in_array($kinds, ['daily', 'monthly', 'total'], true)) {
            $kinds = 'total';
        }

        // 基本クエリ作成（対象モードのスコア）
        $q = Score::query()->where('modeid', $modeid);

        // 種類に応じた期間フィルタを適用
        if ($kinds === 'daily') {
            // 当日スコアのみ
            $q->whereDate('created_at', today());
        } elseif ($kinds === 'monthly') {
            // 今月分スコア
            $q->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }
        // total は全期間対象

        // ランキング上位データ取得
        // スコア降順、同スコアは登録日時が早い方を上位に
        $rows = (clone $q)
            ->orderByDesc('score')
            ->orderBy('created_at', 'asc')
            ->limit(100) // 上位100件のみ表示
            ->get(['user_id', 'name', 'score', 'created_at']);

        // ランク付与処理（同点・同日時は同順位）
        $ranks = [];
        $rank = 0;  // 現在の順位
        $i = 0;     // ループカウンタ
        $prevS = null; // 前回のスコア
        $prevT = null; // 前回の日時

        foreach ($rows as $r) {
            $i++;
            // スコアまたは日時が異なる場合は順位を更新
            if ($prevS !== $r->score || $prevT !== $r->created_at) {
                $rank = $i;
                $prevS = $r->score;
                $prevT = $r->created_at;
            }

            // 名前が空なら 'user' を表示用に設定
            $ranks[] = [
                'rank'  => $rank,
                'name'  => $r->name ?? 'user',
                'score' => (int)$r->score,
            ];
        }

        // 自分の順位算出（ログインユーザーのみ）
        $myRank = null;
        $myBest = null;

        if (Auth::check()) {
            // 自分の最高スコアを取得（スコア降順＋日時昇順）
            $myBest = (clone $q)
                ->where('user_id', Auth::id())
                ->orderByDesc('score')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($myBest) {
                // 自分より上位のスコアをカウント
                // 条件：スコアが高い or 同スコアで登録が早い
                $better = (clone $q)->where(function ($qq) use ($myBest) {
                    $qq->where('score', '>', $myBest->score)
                       ->orWhere(function ($qq2) use ($myBest) {
                           $qq2->where('score', $myBest->score)
                               ->where('created_at', '<', $myBest->created_at);
                       });
                })->count();

                // 自分の順位 = 上位件数 + 1
                $myRank = $better + 1;
            }
        }

        // ビューへデータを渡して表示
        return view('ranking.index', [
            'kinds'     => $kinds,                          // 表示種別
            'modeid'    => $modeid,                         // モードID
            'ranks'     => $ranks,                          // 上位ランキング配列
            'myRank'    => $myRank,                         // 自分の順位
            'myBest'    => $myBest,                         // 自分の最高スコア
            'updatedAt' => now()->format('Y-m-d H:i:s'),    // 表示更新日時
        ]);
    }
}
