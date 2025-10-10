<section class="card border-0 shadow-sm h-100">
    <div class="card-body">
        <header class="mb-4">
            <h2 class="h5 mb-1">{{ __('Profile Information') }}</h2>
            <p class="text-muted small mb-0">
                {{ __('Update your account profile details.') }}
            </p>
        </header>

        <form method="POST" action="{{ route('profile.update') }}" class="vstack gap-3">
            @csrf
            @method('PATCH')

            <div>
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="alert alert-warning" role="alert">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Your email address is unverified.') }}</span>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Resend Verification Email') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            @if (session('status') === 'profile-updated')
                <div class="alert alert-success" role="alert">
                    {{ __('Profile updated successfully.') }}
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
