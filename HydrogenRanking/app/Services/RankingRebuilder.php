<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Ranking;

/**
 * ランキング再構築サービス
 *
 * scores テーブルから集計し、ranking テーブル（Rankingモデル）へ
 * rank_type × modeid ごとのランキングJSONを保存する。
 */
class RankingRebuilder
{
    /**
     * 指定モードの全種類（daily / monthly / total）を一括再構築
     *
     * @param int|null $modeid null の場合は全モード対象
     */
    public function rebuildAllForMode(int $modeid = null): void
    {
        $this->rebuild('daily', $modeid);
        $this->rebuild('monthly', $modeid);
        $this->rebuild('total', $modeid);
    }

    /**
     * 指定の rankType（daily|monthly|total）についてランキングを再構築
     *
     * @param string   $rankType 'daily'|'monthly'|'total'
     * @param int|null $modeid   nullなら全モード
     */
    public function rebuild(string $rankType, int $modeid = null): void
    {
        //　期間・モードのフィルタを適用したベース集計
        //    - when($modeid): モード指定があれば modeid で絞る
        //    - daily/monthly: created_at を日・月で絞る（タイムゾーンは now()/toDateString() に依存）
        //    - GROUP BY user_id でユーザー別ベスト（MAX(score)）を取得
        $scores = DB::table('scores')
            ->when($modeid, fn($q) => $q->where('modeid', $modeid))
            ->when($rankType === 'daily', function ($q) {
                // 当日分（文字列日付で比較）
                $q->whereDate('created_at', now()->toDateString());
            })
            ->when($rankType === 'monthly', function ($q) {
                // 当月分（年・月で比較）
                $q->whereYear('created_at', now()->year)
                  ->whereMonth('created_at', now()->month);
            })
            ->select('user_id', DB::raw('MAX(score) as best_score'))
            ->groupBy('user_id')
            ->orderByDesc('best_score')
            ->limit(50); // 上位50ユーザーだけ取り出す

        // 上記集計（サブクエリ）に users を結合して表示名を取得
        //    - toSql() + mergeBindings(): サブクエリのバインドを親に引き継ぐための定石
        //    - 最終的に score 降順で整列
        $top = DB::table(DB::raw("({$scores->toSql()}) as s"))
            ->mergeBindings($scores)
            ->join('users', 'users.id', '=', 's.user_id')
            ->select('s.best_score as score', 'users.name')
            ->orderByDesc('score')
            ->get();

        // レスポンス用ペイロード作成
        //    - 1位からの連番を振る（同点同順位ではなく 1,2,3...）
        //    - 同点同順位が必要なら、前件比較で rank を据え置くロジックに変更する
        $payload = [];
        foreach ($top as $i => $row) {
            $payload[] = [
                'rank'  => $i + 1,
                'name'  => $row->name ?? 'user',
                'score' => (int) $row->score,
            ];
        }

        // Ranking テーブルへ upsert
        //    - (rank_type, modeid) をキーに updateOrCreate
        //    - JSON_UNESCAPED_UNICODE: 日本語を \u エスケープせず格納
        //    - modeid が null のときは DB で NULL を許可・インデックス整合に注意
        Ranking::updateOrCreate(
            ['rank_type' => $rankType, 'modeid' => $modeid],
            ['ranking_json' => json_encode($payload, JSON_UNESCAPED_UNICODE)]
        );
    }
}
