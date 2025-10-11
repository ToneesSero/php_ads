@extends('layouts.guest')

@section('title', __('Confirm Password'))

@section('content')
    <p class="text-muted small mb-3">
        {{ __('Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="vstack gap-3">
        @csrf

        <div>
            <label for="password" class="form-label">{{ __('Password') }}</label>
            <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                {{ __('Confirm') }}
            </button>
        </div>

        <div class="text-center">
            <a class="small" href="{{ route('login') }}">{{ __('Back to login') }}</a>
        </div>
    </form>
@endsection
