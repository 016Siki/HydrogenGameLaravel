<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Score;
/**
 * ランキング取得コントローラ
 * 
 * 指定モード・期間に応じたスコアランキングを返却する。
 * 種類：total（全期間） / daily（当日） / monthly（当月）
*/
class RankingController extends Controller
{
    /**
     * ランキング一覧を取得
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // 取得するランキング種別（total / daily / monthly）
        // kindsパラメータ または rank_typeパラメータから取得（どちらでも対応）
        $kinds  = $request->query('kinds') ?? $request->query('rank_type') ?? 'total';
        // ゲームモードID（デフォルト1）
        $modeid = (int) $request->query('modeid', 1);
        // ベースとなるスコアクエリを構築
        $q = \App\Models\Score::query()->where('modeid', $modeid);
        // 種別に応じて期間を絞り込み
        if ($kinds === 'daily') {
            // 今日の日付のスコアのみ
            $q->whereDate('created_at', today());
        } elseif ($kinds === 'monthly') {
            // 今月の範囲（1日〜月末）
            $q->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }
        // 上位50件をスコア降順・日時昇順で取得
        $rows = (clone $q)
            ->orderByDesc('score')// スコアが高い順
            ->orderBy('created_at','asc')// 同スコア時は先に取った方を上位に
            ->limit(50)
            ->get(['user_id','name','score','created_at']);

        // ==============================================
        // ランク付与処理（同点・同日時は同順位にする）
        // ==============================================
        $ranking = [];
        $rank=0; // 現在の順位
        $i=0; // ループカウンタ
        $prevS=null; // 前回スコア
        $prevT=null; // 前回日時
        foreach ($rows as $r) {
            $i++;
            // スコアまたは日時が異なれば順位を更新
            if ($prevS !== $r->score || $prevT !== $r->created_at) {
                $rank = $i; $prevS = $r->score; $prevT = $r->created_at;
            }
            // 表示用配列に格納（名前が空なら'user'）
            $ranking[] = [
                'rank'=>$rank, 
                'name'=>$r->name ?? 'user', 
                'score'=>(int)$r->score
            ];
        }

        // ==============================================
        // 自分の順位を計算（期間内の自己ベストスコア基準）
        // ==============================================
        $myRank = null;
        if ($request->user()) {
            // 自分の最高スコアを1件取得
            $myBest = (clone $q)
                ->where('user_id',$request->user()->id)
                ->orderByDesc('score')
                ->orderBy('created_at','asc')
                ->first();
            if ($myBest) {
                $better = (clone $q)->where(function($qq) use($myBest){
                    $qq->where('score','>', $myBest->score)
                    ->orWhere(function($qq2) use($myBest){
                        $qq2->where('score',$myBest->score)
                            ->where('created_at','<',$myBest->created_at);
                    });
                })->count();
                // 自分の順位は「上位件数 + 1」
                $myRank = $better + 1;
            }
        }
        // ==============================================
        // JSON形式でレスポンス返却
        // ==============================================
        return response()->json([
            'ranking'    => $ranking,// 上位50件
            'my_rank'    => $myRank,// 自分の順位（認証時のみ）
            'updated_at' => now()->format('Y-m-d H:i:s'),// 更新時刻
        ]);
    }
}
