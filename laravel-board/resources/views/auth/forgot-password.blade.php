@extends('layouts.guest')

@section('title', __('Forgot Password'))

@section('content')
    <p class="text-muted small mb-3">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.') }}
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="vstack gap-3">
        @csrf

        <div>
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">
                {{ __('Email Password Reset Link') }}
            </button>
        </div>

        <div class="text-center">
            <a class="small" href="{{ route('login') }}">{{ __('Back to login') }}</a>
        </div>
    </form>
@endsection
