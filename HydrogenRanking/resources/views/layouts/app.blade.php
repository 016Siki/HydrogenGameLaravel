<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Ranking')</title>
  <link rel="stylesheet" href="https://unpkg.com/milligram@1.4.1/dist/milligram.min.css">
  <style>
    .container { max-width: 960px; margin: 2rem auto; }
    .right { text-align: right; }
    .flex { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
    .spacer { flex: 1; }
    table td, table th { white-space: nowrap; }
  </style>
</head>
<body>
<div class="container">
  <header class="flex">
    <h3 style="margin:0">@yield('title','Ranking')</h3>
    <div class="spacer"></div>
    @auth
      <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="button button-outline">ログアウト</button>
      </form>
    @endauth
  </header>

  <hr>
  @if (session('status'))
    <div class="message">{{ session('status') }}</div>
  @endif

  @yield('content')
</div>
</body>
</html>
