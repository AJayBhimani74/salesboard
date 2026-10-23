@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="card narrow">
    <h1>Log in</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        @error('email') <p class="error">{{ $message }}</p> @enderror
        <label>Password <input type="password" name="password" required></label>
        <button class="btn" type="submit">Log in</button>
    </form>
</div>
@endsection
