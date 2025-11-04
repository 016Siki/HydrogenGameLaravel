<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Score;

class RankingController extends Controller
{
public function index(Request $request)
{
    $kinds  = $request->query('kinds') ?? $request->query('rank_type') ?? 'total';
    $modeid = (int) $request->query('modeid', 1);

    $q = \App\Models\Score::query()->where('modeid', $modeid);

    if ($kinds === 'daily') {
        $q->whereDate('created_at', today());
    } elseif ($kinds === 'monthly') {
        $q->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    $rows = (clone $q)
        ->orderByDesc('score')->orderBy('created_at','asc')
        ->limit(50)
        ->get(['user_id','name','score','created_at']);

    // ランク付与（同点同順位）
    $ranking = [];
    $rank=0; $i=0; $prevS=null; $prevT=null;
    foreach ($rows as $r) {
        $i++;
        if ($prevS !== $r->score || $prevT !== $r->created_at) {
            $rank = $i; $prevS = $r->score; $prevT = $r->created_at;
        }
        $ranking[] = ['rank'=>$rank, 'name'=>$r->name ?? 'user', 'score'=>(int)$r->score];
    }

    // My順位（期間内自己ベスト）
    $myRank = null;
    if ($request->user()) {
        $myBest = (clone $q)->where('user_id',$request->user()->id)
            ->orderByDesc('score')->orderBy('created_at','asc')->first();
        if ($myBest) {
            $better = (clone $q)->where(function($qq) use($myBest){
                $qq->where('score','>', $myBest->score)
                   ->orWhere(function($qq2) use($myBest){
                       $qq2->where('score',$myBest->score)
                           ->where('created_at','<',$myBest->created_at);
                   });
            })->count();
            $myRank = $better + 1;
        }
    }

    return response()->json([
        'ranking'    => $ranking,
        'my_rank'    => $myRank,
        'updated_at' => now()->format('Y-m-d H:i:s'),
    ]);
}
}
