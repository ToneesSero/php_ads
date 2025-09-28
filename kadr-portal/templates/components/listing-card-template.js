(function () {
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

    const truncate = (text, limit = 220) => {
        const value = typeof text === 'string' ? text : '';

        if (value.length <= limit) {
            return value;
        }

        return `${value.slice(0, limit - 1)}…`;
    };

    const buildImageSection = (listing) => {
        const url = escapeHtml(listing.url || `/listings/${listing.id}`);
        const thumb = typeof listing.main_image_thumb === 'string' ? listing.main_image_thumb : null;
        const full = typeof listing.main_image_path === 'string' ? listing.main_image_path : null;

        if (thumb) {
            const fullAttr = full ? ` data-full-image="${escapeHtml(full)}"` : '';

            return `
                <a class="listing-card-image" href="${url}">
                    <img src="${escapeHtml(thumb)}"${fullAttr} alt="Превью объявления" loading="lazy">
                </a>
            `;
        }

        return `
            <a class="listing-card-placeholder" href="${url}">
                <span>Нет фото</span>
            </a>
        `;
    };

    const isAuthenticated = () => {
        if (!document.body || !document.body.dataset) {
            return false;
        }

        return document.body.dataset.auth === '1';
    };

    const getCsrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');

        if (meta) {
            const token = meta.getAttribute('content');

            if (token) {
                return token;
            }
        }

        return '';
    };

    const buildFavoriteButton = (listing) => {
        const favorited = Boolean(listing.is_favorite);
        const csrfToken = escapeHtml(getCsrfToken());
        const auth = isAuthenticated();
        const buttonClasses = ['button', 'button-secondary', 'listing-card-favorite'];

        if (favorited) {
            buttonClasses.push('listing-card-favorite--active');
        }

        const requiresAuthAttr = auth ? '' : ' data-requires-auth="1"';

        return `
            <button type="button"
                    class="${buttonClasses.join(' ')}"
                    data-favorite-button
                    data-listing-id="${escapeHtml(listing.id)}"
                    data-favorited="${favorited ? '1' : '0'}"
                    data-csrf="${csrfToken}"
                    aria-pressed="${favorited ? 'true' : 'false'}"${requiresAuthAttr}>
                ${favorited ? 'В избранном' : 'В избранное'}
            </button>
        `;
    };

    window.renderListingCard = (listing) => {
        const url = escapeHtml(listing.url || `/listings/${listing.id}`);
        const title = escapeHtml(listing.title);
        const price = formatPrice(listing.price);
        const description = escapeHtml(truncate(listing.description));
        const category = escapeHtml(listing.category_name ?? 'Без категории');
        const author = escapeHtml(listing.author_name ?? 'Неизвестно');
        const createdAt = escapeHtml(new Date(listing.created_at).toLocaleDateString('ru-RU'));

        return `
            <article class="listing-card">
                <div class="listing-card-media">
                    ${buildImageSection(listing)}
                </div>
                <h2>
                    <a href="${url}">${title}</a>
                </h2>
                <p class="text-muted">${price} ₽</p>
                <p>${description}</p>
                <div class="listing-card-meta">
                    <span>Категория: ${category}</span>
                    <span>Автор: ${author}</span>
                    <span>Опубликовано: ${createdAt}</span>
                </div>
                <div class="listing-card-actions">
                    <a class="button button-link" href="${url}">Подробнее</a>
                    ${buildFavoriteButton(listing)}
                </div>
            </article>
        `;
    };
})();