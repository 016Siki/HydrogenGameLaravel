@extends('layouts.app')
@section('title','ランキング')

@section('content')
<form method="get" action="{{ route('ranking.index') }}" class="flex">
  <label>種別:
    <select name="kinds">
      <option value="daily"   {{ $kinds==='daily'?'selected':'' }}>デイリー</option>
      <option value="monthly" {{ $kinds==='monthly'?'selected':'' }}>月間</option>
      <option value="total"   {{ $kinds==='total'?'selected':'' }}>総合</option>
    </select>
  </label>
  {{-- <label>モード:
    <input type="number" name="modeid" value="{{ $modeid }}" min="1" style="max-width:120px">
  </label> --}}
  <button class="button">更新</button>
  <div class="spacer"></div>
  <span>最終更新: {{ $updatedAt }}</span>
</form>

<hr>

<h5>上位100件（{{ $kinds }}）</h5>
<table>
  <thead>
    <tr><th>順位</th><th>名前</th><th class="right">スコア</th></tr>
  </thead>
  <tbody>
  @forelse($ranks as $r)
    <tr>
      <td class="right">{{ $r['rank'] }}</td>
      <td>{{ $r['name'] }}</td>
      <td class="right">{{ number_format($r['score']) }}</td>
    </tr>
  @empty
    <tr><td colspan="3">データがありません</td></tr>
  @endforelse
  </tbody>
</table>

<hr>

<h5>Myランキング</h5>
@if ($myRank)
  <p>あなたの順位: <strong>{{ $myRank }}</strong>
     （自己ベスト: {{ number_format($myBest->score ?? 0) }}）</p>
@else
  <p>この期間に自己ベストがありません。</p>
@endif

<hr>

<form method="post" action="{{ route('account.destroy') }}"
      onsubmit="return confirm('本当にアカウントを削除しますか？この操作は取り消せません。');">
  @csrf
  @method('DELETE')
  <button class="button button-outline" style="border-color:#c00;color:#c00">
    アカウント削除
  </button>
</form>

@endsection
