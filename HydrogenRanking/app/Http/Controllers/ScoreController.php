<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Score;

class ScoreController extends Controller
{
public function store(Request $request)
{
    $validated = $request->validate([
        'score'  => ['required','integer','min:0','max:20000'],
        'modeid' => ['required','integer','min:1','max:1000'],
        'name'   => ['nullable','string','min:1','max:32'],
    ]);

    $rawName = $validated['name'] ?? 'NoName';
    $name = trim(mb_substr(
        preg_replace('/[^\p{L}\p{N}\s_\-\.]/u', '', $rawName),
        0, 32
    ));
    if ($name === '') $name = 'NoName';

    $score = \App\Models\Score::create([
        'user_id' => $request->user()->id,
        'name'    => $name,
        'modeid'  => $validated['modeid'],
        'score'   => $validated['score'],
    ]);

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