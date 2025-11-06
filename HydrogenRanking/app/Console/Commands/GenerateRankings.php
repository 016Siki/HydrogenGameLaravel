<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Score;
use App\Models\Ranking;
use Carbon\Carbon;

/**
 * rankings:generate
 * - daily:    日分の自己ベスト（ユーザー×モード）を集計して保存
 * - monthly:  月分の自己ベストを集計して保存
 * - total:    全期間の自己ベストを集計して保存
 *
 * メモ:
 * - 冪等: 同日/同月/total の既存レコードを削除→再作成するので再実行しても整合が保たれる
 * - 速度: scores(modeid, created_at, user_id, score) に複合INDEXがあると◎
 * - TZ: Carbonのnow()/yesterday()はAPP_TZ依存。DB側TIMESTAMPのTZとズレないよう注意
 */
class GenerateRankings extends Command
{
    /** コマンド名 */
    protected $signature = 'rankings:generate';

    /** CLI説明 */
    protected $description = 'ランキングをdaily/monthly/totalで集計する';

    /**
     * 実行エントリ
     * - 再実行しても最新状態に収束する（delete→create）
     */
    public function handle()
    {
        $this->info('ランキング集計を開始');

        $this->generateDailyRanking();   // 日分
        $this->generateMonthlyRanking(); // 月分
        $this->generateTotalRanking();   // 全期間

        $this->info('ランキング集計完了');
    }

    /**
     * 日次ランキングを生成
     * - 対象: created_at = 昨日 のレコード
     * - 取得: ユーザー×モードで MAX(score) を自己ベストとして採用
     * - 保存: rank_type=daily, date=YYYY-MM-DD（昨日）
     */
    private function generateDailyRanking()
    {
        $date = Carbon::yesterday()->toDateString(); 

        // ユーザー×モードの自己ベスト
        $scores = Score::whereDate('created_at', $date)
            ->select('user_id', DB::raw('MAX(score) as score'), 'modeid')
            ->groupBy('user_id', 'modeid')
            ->orderByDesc('score')
            ->take(100) // Web表示用に上位100
            ->get();

        // 冪等性: 同日の既存dailyを削除
        Ranking::where('rank_type', 'daily')
            ->where('date', $date)
            ->delete();

        // INSERT（rank列は持たず、並びはscore降順で表現）
        foreach ($scores as $s) {
            Ranking::create([
                'user_id'   => $s->user_id,
                'score'     => $s->score,
                'rank_type' => 'daily',
                'modeid'    => $s->modeid,
                'date'      => $date,
            ]);
        }
    }

    /**
     * 月次ランキングを生成
     * - 対象: 月の1日〜月末（アプリTZ基準）
     * - 保存: rank_type=monthly, date=YYYY-MM
     */
    private function generateMonthlyRanking()
    {
        $monthKey = Carbon::now()->subMonth()->format('Y-m'); 
        $from     = Carbon::parse("$monthKey-01");
        $to       = (clone $from)->endOfMonth();

        $scores = Score::whereBetween('created_at', [$from, $to])
            ->select('user_id', DB::raw('MAX(score) as score'), 'modeid')
            ->groupBy('user_id', 'modeid')
            ->orderByDesc('score')
            ->take(100)
            ->get();

        Ranking::where('rank_type', 'monthly')
            ->where('date', $monthKey)
            ->delete();

        foreach ($scores as $s) {
            Ranking::create([
                'user_id'   => $s->user_id,
                'score'     => $s->score,
                'rank_type' => 'monthly',
                'modeid'    => $s->modeid,
                'date'      => $monthKey, // YYYY-MM
            ]);
        }
    }

    /**
     * 通期ランキング（全期間）を生成
     * - 対象: 制限なし（全件）
     * - 保存: rank_type=total, date=実行日(YYYY-MM-DD)
     */
    private function generateTotalRanking()
    {
        $scores = Score::select('user_id', DB::raw('MAX(score) as score'), 'modeid')
            ->groupBy('user_id', 'modeid')
            ->orderByDesc('score')
            ->take(100)
            ->get();

        Ranking::where('rank_type', 'total')->delete();

        foreach ($scores as $s) {
            Ranking::create([
                'user_id'   => $s->user_id,
                'score'     => $s->score,
                'rank_type' => 'total',
                'modeid'    => $s->modeid,
                'date'      => now()->toDateString(), // 実行日
            ]);
        }
    }
}
