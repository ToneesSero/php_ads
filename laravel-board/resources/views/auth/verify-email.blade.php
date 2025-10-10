@extends('layouts.guest')

@section('title', __('Verify Email'))

@section('content')
    <p class="text-muted small mb-3">
        {{ __('Before continuing, please verify your email address by clicking on the link we just emailed to you.') }}
    </p>
    <p class="text-muted small">
        {{ __('If you did not receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            {{ __('A new verification link has been sent to your email address.') }}
        </div>
    @endif

    <div class="vstack gap-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('Resend Verification Email') }}</button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <div class="d-grid">
                <button type="submit" class="btn btn-outline-secondary">{{ __('Log Out') }}</button>
            </div>
        </form>
    </div>
@endsection
