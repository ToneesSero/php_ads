<section class="card border-0 shadow-sm h-100">
    <div class="card-body">
        <header class="mb-4">
            <h2 class="h5 mb-1">{{ __('Update Password') }}</h2>
            <p class="text-muted small mb-0">
                {{ __('Ensure your account uses a strong password.') }}
            </p>
        </header>

        <form method="POST" action="{{ route('password.update') }}" class="vstack gap-3">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="form-label">{{ __('Current Password') }}</label>
                <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password" class="form-label">{{ __('New Password') }}</label>
                <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password">
            </div>

            @if (session('status') === 'password-updated')
                <div class="alert alert-success" role="alert">
                    {{ __('Password updated successfully.') }}
                </div>
            @endif

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    {{ __('Save changes') }}
                </button>
            </div>
        </form>
    </div>
</section>
