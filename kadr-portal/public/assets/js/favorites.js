(function () {
    const selector = '[data-favorite-button]';

    const isAuthenticated = () => {
        if (!document.body || !document.body.dataset) {
            return false;
        }

        return document.body.dataset.auth === '1';
    };

    const getCsrfToken = (button) => {
        const meta = document.querySelector('meta[name="csrf-token"]');

        if (meta) {
            const token = meta.getAttribute('content');

            if (token) {
                return token;
            }
        }

        if (button && button.dataset && button.dataset.csrf) {
            return button.dataset.csrf;
        }

        return '';
    };

    const updateButton = (button, favorited) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.dataset.favorited = favorited ? '1' : '0';
        button.setAttribute('aria-pressed', favorited ? 'true' : 'false');

        if (favorited) {
            button.classList.add('listing-card-favorite--active');
            button.textContent = 'В избранном';
        } else {
            button.classList.remove('listing-card-favorite--active');
            button.textContent = 'В избранное';
        }
    };

    const showError = (message) => {
        if (!message) {
            return;
        }

        window.alert(message);
    };

    const handleRemovalFromList = (button, favorited) => {
        if (favorited) {
            return;
        }

        const container = button.closest('[data-favorites-list]');

        if (!container) {
            return;
        }

        const card = button.closest('.listing-card');

        if (card) {
            card.remove();
        }

        const remaining = container.querySelectorAll('.listing-card').length;
        const emptyMessage = document.querySelector('[data-favorites-empty]');

        if (emptyMessage) {
            emptyMessage.hidden = remaining !== 0;
        }
    };

    const toggleFavorite = async (button) => {
        const listingId = button.dataset.listingId;

        if (!listingId) {
            return;
        }

        if (!isAuthenticated()) {
            window.location.href = '/login';
            return;
        }

        const csrfToken = getCsrfToken(button);

        if (!csrfToken) {
            showError('Не удалось получить CSRF токен. Обновите страницу и попробуйте снова.');
            return;
        }

        const favorited = button.dataset.favorited === '1';
        const formData = new FormData();
        formData.append('listing_id', listingId);
        formData.append('csrf_token', csrfToken);
        formData.append('action', favorited ? 'remove' : 'add');

        button.disabled = true;

        try {
            const response = await fetch('/api/favorites', {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await response.json();

            if (!response.ok || !payload) {
                const message = payload && payload.error ? payload.error : 'Не удалось обновить избранное.';

                throw new Error(message);
            }

            if (payload.error) {
                throw new Error(payload.error);
            }

            const isFavorited = Boolean(payload.favorite);
            updateButton(button, isFavorited);
            handleRemovalFromList(button, isFavorited);
        } catch (error) {
            console.error(error);
            showError(error instanceof Error ? error.message : 'Не удалось обновить избранное.');
        } finally {
            button.disabled = false;
        }
    };

    document.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const button = target.closest(selector);

        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        event.preventDefault();
        toggleFavorite(button);
    });
})();
