<section class="card border-0 shadow-sm h-100">
    <div class="card-body">
        <header class="mb-4">
            <h2 class="h5 text-danger mb-1">{{ __('Delete Account') }}</h2>
            <p class="text-muted small mb-0">
                {{ __('Permanently delete your account and all related data.') }}
            </p>
        </header>

        <div class="alert alert-warning" role="alert">
            {{ __('Once your account is deleted, all resources and data will be permanently removed. Please download any data you wish to keep before deleting your account.') }}
        </div>

        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmDeletionModal">
            {{ __('Delete Account') }}
        </button>

        <div class="modal fade" id="confirmDeletionModal" tabindex="-1" aria-labelledby="confirmDeletionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeletionModalLabel">{{ __('Delete Account') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ __('Are you sure you want to delete your account? This action cannot be undone.') }}</p>
                        <form method="POST" action="{{ route('profile.destroy') }}" class="vstack gap-3">
                            @csrf
                            @method('DELETE')

                            <div>
                                <label for="password" class="form-label">{{ __('Password') }}</label>
                                <input id="password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="{{ __('Enter your password to confirm') }}">
                                @error('password', 'userDeletion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Delete Account') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->userDeletion->isNotEmpty())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof bootstrap !== 'undefined') {
                        const deletionModal = new bootstrap.Modal(document.getElementById('confirmDeletionModal'));
                        deletionModal.show();
                    }
                });
            </script>
        @endif
    </div>
</section>
