(function () {
    const listingsContainer = document.querySelector('[data-listings-container]');
    const filterForm = document.querySelector('[data-listings-filter]');

    if (filterForm) {
        filterForm.addEventListener('submit', (event) => {
            const minInput = filterForm.querySelector('input[name="min_price"]');
            const maxInput = filterForm.querySelector('input[name="max_price"]');

            if (!(minInput instanceof HTMLInputElement) || !(maxInput instanceof HTMLInputElement)) {
                return;
            }

            const minValue = Number.parseFloat(minInput.value);
            const maxValue = Number.parseFloat(maxInput.value);

            if (!Number.isNaN(minValue) && minValue < 0) {
                event.preventDefault();
                minInput.focus();
                alert('Минимальная цена не может быть меньше нуля.');
                return;
            }

            if (!Number.isNaN(maxValue) && maxValue < 0) {
                event.preventDefault();
                maxInput.focus();
                alert('Максимальная цена не может быть меньше нуля.');
                return;
            }

            if (!Number.isNaN(minValue) && !Number.isNaN(maxValue) && minValue > maxValue) {
                event.preventDefault();
                maxInput.focus();
                alert('Минимальная цена не может быть больше максимальной.');
            }
        });

        const resetButton = filterForm.querySelector('[data-reset-filters]');

        if (resetButton instanceof HTMLButtonElement) {
            resetButton.addEventListener('click', () => {
                filterForm.reset();
                const searchInput = filterForm.querySelector('input[name="search"]');

                if (searchInput instanceof HTMLInputElement) {
                    searchInput.value = '';
                }

                const action = filterForm.getAttribute('action') || '/listings';
                window.location.href = action;
            });
        }
    }

    if (listingsContainer) {
        window.listingsConfig = {
            nextPage: listingsContainer.dataset.nextPage || null,
            baseQuery: listingsContainer.dataset.baseQuery || '',
        };
    }

    const escapeHtml = (value) => {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const formatPrice = (price) => {
        const amount = Number.isFinite(price) ? Number(price) : 0;

        return new Intl.NumberFormat('ru-RU', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    };

    const truncate = (text, limit = 160) => {
        const value = typeof text === 'string' ? text : '';

        if (value.length <= limit) {
            return value;
        }

        return `${value.slice(0, limit - 1)}…`;
    };

    const buildImageSection = (listing) => {
        const thumb = typeof listing.main_image_thumb === 'string' ? listing.main_image_thumb : null;
        const full = typeof listing.main_image_path === 'string' ? listing.main_image_path : null;

        if (thumb) {
            const fullAttr = full ? ` data-full-image="${escapeHtml(full)}"` : '';

            return `<img src="${escapeHtml(thumb)}"${fullAttr} class="card-img-top" alt="Превью объявления" loading="lazy">`;
        }

        return `
            <div class="card-img-top bg-light d-flex justify-content-center align-items-center text-muted" style="height: 200px;">
                Нет фото
            </div>
        `;
    };

    window.renderListingCard = (listing) => {
        const url = listing.url ? String(listing.url) : `/listings/${listing.id}`;
        const createdAt = listing.created_at ? new Date(listing.created_at).toLocaleString('ru-RU') : '';
        const price = formatPrice(listing.price);
        const description = truncate(listing.description, 160);
        const category = listing.category_name ?? 'Без категории';
        const author = listing.author_name ?? 'Неизвестно';
        const views = Number.isFinite(listing.views_count) ? listing.views_count : 0;

        return `
            <div class="col">
                <div class="card h-100 shadow-sm">
                    ${buildImageSection(listing)}
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h2 class="h5">
                                    <a class="text-decoration-none" href="${escapeHtml(url)}">
                                        ${escapeHtml(listing.title ?? '')}
                                    </a>
                                </h2>
                                <div class="text-muted small">
                                    ${escapeHtml(category)}${createdAt ? ` · ${escapeHtml(createdAt)}` : ''}
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary fs-6">
                                    ${escapeHtml(price)} ₽
                                </span>
                            </div>
                        </div>
                        <p class="text-muted mt-3 mb-4">
                            ${escapeHtml(description)}
                        </p>
                        <div class="mt-auto d-flex justify-content-between text-muted small">
                            <span>Автор: ${escapeHtml(author)}</span>
                            <span>Просмотры: ${escapeHtml(String(views))}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    };
})();
