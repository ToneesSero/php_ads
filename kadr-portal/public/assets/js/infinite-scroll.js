const listingsContainer = document.querySelector('[data-listings-container]');

if (listingsContainer && window.renderListingCard) {
    const loadingIndicator = document.querySelector('[data-loading-indicator]');
    const errorBox = document.querySelector('[data-loading-error]');
    const pagination = document.querySelector('[data-pagination]');
    const sentinel = document.querySelector('[data-scroll-sentinel]');

    if (pagination) {
        pagination.classList.add('is-hidden');
    }

    const state = {
        nextPage: window.listingsConfig?.nextPage ? Number(window.listingsConfig.nextPage) : null,
        baseQuery: window.listingsConfig?.baseQuery || '',
        isLoading: false,
        hasMore: Boolean(window.listingsConfig?.nextPage),
        limit: 10,
    };

    const setLoading = (value) => {
        state.isLoading = value;

        if (!loadingIndicator) {
            return;
        }

        if (value) {
            loadingIndicator.hidden = false;
        } else {
            loadingIndicator.hidden = true;
        }
    };

    const showError = (message) => {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = message;
        errorBox.hidden = false;
    };

    const clearError = () => {
        if (!errorBox) {
            return;
        }

        errorBox.hidden = true;
        errorBox.textContent = '';
    };

    const buildUrl = (page) => {
        const params = state.baseQuery ? new URLSearchParams(state.baseQuery) : new URLSearchParams();

        params.set('page', String(page));
        params.set('limit', String(state.limit));

        return `/api/listings?${params.toString()}`;
    };

    const appendListings = (items) => {
        const fragment = document.createDocumentFragment();

        items.forEach((item) => {
            try {
                const html = window.renderListingCard(item);
                const template = document.createElement('template');
                template.innerHTML = html.trim();

                if (template.content.firstElementChild) {
                    fragment.appendChild(template.content.firstElementChild);
                }
            } catch (error) {
                console.error('Не удалось отрисовать карточку объявления', error);
            }
        });

        listingsContainer.appendChild(fragment);
    };

    const handleResponse = (payload) => {
        if (!payload || !Array.isArray(payload.data)) {
            throw new Error('Некорректный ответ сервера.');
        }

        appendListings(payload.data);

        const pagination = payload.pagination || {};

        state.hasMore = Boolean(pagination.hasMore);
        state.nextPage = pagination.nextPage ? Number(pagination.nextPage) : null;

        if (!state.hasMore || !state.nextPage) {
            observer.disconnect();
            if (loadingIndicator) {
                loadingIndicator.hidden = true;
            }
        }
    };

    const fetchNextPage = async () => {
        if (!state.nextPage || state.isLoading) {
            return;
        }

        setLoading(true);
        clearError();

        try {
            const response = await fetch(buildUrl(state.nextPage), {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Ошибка сети.');
            }

            const payload = await response.json();
            handleResponse(payload);
        } catch (error) {
            console.error(error);
            showError('Не удалось загрузить объявления. Попробуйте позже.');
        } finally {
            setLoading(false);
        }
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                fetchNextPage();
            }
        });
    }, {
        rootMargin: '200px 0px',
    });

    if (sentinel) {
        observer.observe(sentinel);
    }
}
