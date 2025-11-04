<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Score;

class WebRankingController extends Controller
{
    public function index(Request $request)
    {
        $kinds  = $request->query('kinds', 'total');      // 'daily'|'monthly'|'total'
        $modeid = (int) $request->query('modeid', 1);

        if (!in_array($kinds, ['daily','monthly','total'], true)) {
            $kinds = 'total';
        }

        $q = Score::query()->where('modeid', $modeid);

        if ($kinds === 'daily') {
            $q->whereDate('created_at', today());
        } elseif ($kinds === 'monthly') {
            $q->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } // total は全期間

        $rows = (clone $q)
            ->orderByDesc('score')
            ->orderBy('created_at', 'asc')   // 同点は早い者勝ち
            ->limit(100)                     // 上位100件
            ->get(['user_id','name','score','created_at']);

        // ランク付与（同点同順位のコンペ方式）
        $ranks = [];
        $rank = 0; $i = 0; $prevS = null; $prevT = null;
        foreach ($rows as $r) {
            $i++;
            if ($prevS !== $r->score || $prevT !== $r->created_at) {
                $rank = $i; $prevS = $r->score; $prevT = $r->created_at;
            }
            $ranks[] = [
                'rank'  => $rank,
                'name'  => $r->name ?? 'user',
                'score' => (int)$r->score,
            ];
        }

        // My ランキング（同じフィルタ内の自己ベストで順位算出）
        $myRank = null; $myBest = null;
        if (Auth::check()) {
            $myBest = (clone $q)->where('user_id', Auth::id())
                ->orderByDesc('score')->orderBy('created_at', 'asc')->first();

            if ($myBest) {
                $better = (clone $q)->where(function ($qq) use ($myBest) {
                    $qq->where('score', '>', $myBest->score)
                       ->orWhere(function ($qq2) use ($myBest) {
                           $qq2->where('score', $myBest->score)
                               ->where('created_at', '<', $myBest->created_at);
                       });
                })->count();
                $myRank = $better + 1;
            }
        }

        return view('ranking.index', [
            'kinds'     => $kinds,
            'modeid'    => $modeid,
            'ranks'     => $ranks,
            'myRank'    => $myRank,
            'myBest'    => $myBest,
            'updatedAt' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
