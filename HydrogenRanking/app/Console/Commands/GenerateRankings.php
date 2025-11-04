<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Score;
use App\Models\Ranking;
use Carbon\Carbon;

class GenerateRankings extends Command
{
 protected $signature = 'rankings:generate';
    protected $description = 'ランキングをdaily/monthly/totalで集計する';

    public function handle()
    {
        $this->info('ランキング集計を開始');

        $this->generateDailyRanking();
        $this->generateMonthlyRanking();
        $this->generateTotalRanking();

        $this->info('ランキング集計完了');
    }

    private function generateDailyRanking()
    {
        $today = Carbon::yesterday()->toDateString(); // 昨日のランキングを作成

        // スコア取得
        $scores = Score::whereDate('created_at', $today)
            ->select('user_id', DB::raw('MAX(score) as score'), 'modeid')
            ->groupBy('user_id', 'modeid')
            ->orderByDesc('score')
            ->take(100) // 上位100件（Web用）
            ->get();

        // 既存削除（modeidごと）
        Ranking::where('rank_type', 'daily')->where('date', $today)->delete();

        // 登録
        foreach ($scores as $score) {
            Ranking::create([
                'user_id'   => $score->user_id,
                'score'     => $score->score,
                'rank_type' => 'daily',
                'modeid'    => $score->modeid,
                'date'      => $today,
            ]);
        }
    }

    private function generateMonthlyRanking()
    {
        $month = Carbon::now()->subMonth()->format('Y-m'); // 先月

        $scores = Score::whereBetween('created_at', [
                Carbon::parse($month . '-01'),
                Carbon::parse($month . '-01')->endOfMonth()
            ])
            ->select('user_id', DB::raw('MAX(score) as score'), 'modeid')
            ->groupBy('user_id', 'modeid')
            ->orderByDesc('score')
            ->take(100)
            ->get();

        Ranking::where('rank_type', 'monthly')->where('date', $month)->delete();

        foreach ($scores as $score) {
            Ranking::create([
                'user_id'   => $score->user_id,
                'score'     => $score->score,
                'rank_type' => 'monthly',
                'modeid'    => $score->modeid,
                'date'      => $month,
            ]);
        }
    }

    private function generateTotalRanking()
    {
        $scores = Score::select('user_id', DB::raw('MAX(score) as score'), 'modeid')
            ->groupBy('user_id', 'modeid')
            ->orderByDesc('score')
            ->take(100)
            ->get();

        Ranking::where('rank_type', 'total')->delete();

        foreach ($scores as $score) {
            Ranking::create([
                'user_id'   => $score->user_id,
                'score'     => $score->score,
                'rank_type' => 'total',
                'modeid'    => $score->modeid,
                'date'      => now()->format('Y-m-d'), // 実行日
            ]);
        }
    }}
