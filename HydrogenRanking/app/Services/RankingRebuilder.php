<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Ranking;

class RankingRebuilder
{
    public function rebuildAllForMode(int $modeid = null): void
    {
        $this->rebuild('daily', $modeid);
        $this->rebuild('monthly', $modeid);
        $this->rebuild('total', $modeid);
    }

    public function rebuild(string $rankType, int $modeid = null): void
    {
        $scores = DB::table('scores')
            ->when($modeid, fn($q) => $q->where('modeid', $modeid))
            ->when($rankType === 'daily', function ($q) {
                $q->whereDate('created_at', now()->toDateString());
            })
            ->when($rankType === 'monthly', function ($q) {
                $q->whereYear('created_at', now()->year)
                  ->whereMonth('created_at', now()->month);
            })
            ->select('user_id', DB::raw('MAX(score) as best_score'))
            ->groupBy('user_id')
            ->orderByDesc('best_score')
            ->limit(50);

        // サブクエリとしてMAX(score)を使い、ユーザー名を結合
        $top = DB::table(DB::raw("({$scores->toSql()}) as s"))
            ->mergeBindings($scores)
            ->join('users', 'users.id', '=', 's.user_id')
            ->select('s.best_score as score', 'users.name')
            ->orderByDesc('score')
            ->get();

        // rank, name, score の配列を作成
        $payload = [];
        foreach ($top as $i => $row) {
            $payload[] = [
                'rank'  => $i + 1,
                'name'  => $row->name ?? 'user',
                'score' => (int) $row->score,
            ];
        }

        Ranking::updateOrCreate(
            ['rank_type' => $rankType, 'modeid' => $modeid],
            ['ranking_json' => json_encode($payload, JSON_UNESCAPED_UNICODE)]
        );
    }
}
