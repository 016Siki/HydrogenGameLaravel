@extends('layouts.app')
@section('title','すいそかゲームランキング')

@section('content')
<form method="post" action="{{ route('login.post') }}">
  @csrf
  <fieldset>
    <label for="email">メールアドレス</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
    @error('email') <p style="color:#c00">{{ $message }}</p> @enderror

    <label for="password">パスワード</label>
    <input id="password" type="password" name="password" required>

    {{-- <label class="flex" style="gap:.4rem">
      <input type="checkbox" name="remember"> ログイン状態を保持
    </label> --}}

    <button type="submit" class="button">ログイン</button>
  </fieldset>
</form>
@endsection
