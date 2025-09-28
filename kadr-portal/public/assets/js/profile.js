const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfMeta ? csrfMeta.content : '';

const showMessage = (text) => {
    if (typeof window.Toastify === 'function') {
        window.Toastify({ text, duration: 3000 }).showToast();
        return;
    }

    alert(text);
};

const truncate = (value, max = 120) => {
    if (value.length <= max) {
        return value;
    }

    return `${value.slice(0, max - 1)}…`;
};

const sendJson = async (url, method, payload = {}) => {
    const headers = {
        'X-Requested-With': 'XMLHttpRequest',
    };

    let body;

    if (method === 'GET') {
        body = undefined;
    } else {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(payload);
    }

    if (csrfToken) {
        headers['X-CSRF-Token'] = csrfToken;
    }

    const response = await fetch(url, {
        method,
        headers,
        body,
    });

    if (!response.ok) {
        const data = await response.json().catch(() => ({ error: 'Неизвестная ошибка' }));
        throw new Error(data.error || 'Не удалось выполнить запрос');
    }

    return response.json().catch(() => ({}));
};

const dialog = document.querySelector('[data-edit-dialog]');
const editForm = dialog ? dialog.querySelector('[data-edit-form]') : null;
const cancelButton = dialog ? dialog.querySelector('[data-edit-cancel]') : null;
let currentEditingId = null;

const openDialog = () => {
    if (!(dialog instanceof HTMLDialogElement)) {
        dialog?.classList.add('is-open');
        return;
    }

    dialog.showModal();
};

const closeDialog = () => {
    currentEditingId = null;

    if (!(dialog instanceof HTMLDialogElement)) {
        dialog?.classList.remove('is-open');
        return;
    }

    dialog.close();
};

if (editForm) {
    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!currentEditingId) {
            closeDialog();
            return;
        }

        const formData = new FormData(editForm);
        const payload = Object.fromEntries(formData.entries());

        try {
            const data = await sendJson(`/api/listings/${currentEditingId}`, 'PATCH', payload);
            const row = document.querySelector(`[data-listing-id="${currentEditingId}"]`);

            if (row) {
                const titleCell = row.querySelector('.profile-table__title strong');
                const descriptionCell = row.querySelector('.profile-table__description');
                const priceCell = row.querySelector('td:nth-child(3)');
                const editButton = row.querySelector('[data-profile-edit]');

                if (titleCell) {
                    titleCell.textContent = data.listing?.title || payload.title;
                }

                if (descriptionCell) {
                    descriptionCell.textContent = truncate(data.listing?.description || payload.description || '');
                }

                if (priceCell) {
                    const priceRaw = data.listing?.price || payload.price || '0.00';
                    const priceNumber = Number.parseFloat(priceRaw);
                    const formattedPrice = Number.isNaN(priceNumber)
                        ? `${priceRaw} ₽`
                        : `${priceNumber.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ₽`;
                    priceCell.textContent = formattedPrice;
                }

                if (editButton instanceof HTMLButtonElement) {
                    editButton.dataset.listingTitle = data.listing?.title || payload.title || '';
                    editButton.dataset.listingDescription = data.listing?.description || payload.description || '';
                    editButton.dataset.listingPrice = data.listing?.price || payload.price || '';
                }
            }

            showMessage('Объявление обновлено.');
            closeDialog();
        } catch (error) {
            showMessage(error instanceof Error ? error.message : 'Не удалось обновить объявление');
        }
    });
}

if (cancelButton) {
    cancelButton.addEventListener('click', (event) => {
        event.preventDefault();
        closeDialog();
    });
}

document.addEventListener('click', async (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (target.matches('[data-profile-edit]')) {
        currentEditingId = parseInt(target.dataset.listingId || '', 10);

        if (!currentEditingId || !editForm) {
            return;
        }

        const titleInput = editForm.querySelector('input[name="title"]');
        const priceInput = editForm.querySelector('input[name="price"]');
        const descriptionInput = editForm.querySelector('textarea[name="description"]');

        if (!(titleInput instanceof HTMLInputElement) || !(priceInput instanceof HTMLInputElement) || !(descriptionInput instanceof HTMLTextAreaElement)) {
            return;
        }

        titleInput.value = target.dataset.listingTitle || '';
        priceInput.value = target.dataset.listingPrice || '';
        descriptionInput.value = target.dataset.listingDescription || '';

        openDialog();
        return;
    }

    if (target.matches('[data-profile-toggle]')) {
        const listingId = parseInt(target.dataset.listingId || '', 10);
        const currentStatus = target.dataset.currentStatus === 'active' ? 'active' : 'inactive';
        const nextStatus = currentStatus === 'active' ? 'inactive' : 'active';

        if (!listingId) {
            return;
        }

        try {
            const data = await sendJson(`/api/listings/${listingId}/status`, 'PATCH', {
                status: nextStatus,
                csrf_token: csrfToken,
            });

            const row = document.querySelector(`[data-listing-id="${listingId}"]`);

            if (row) {
                const badge = row.querySelector('[data-status-badge]');

                if (badge) {
                    badge.textContent = data.listing_status === 'active' ? 'Активно' : 'Неактивно';
                    badge.classList.toggle('status-badge--active', data.listing_status === 'active');
                    badge.classList.toggle('status-badge--inactive', data.listing_status !== 'active');
                }
            }

            target.dataset.currentStatus = data.listing_status || nextStatus;
            target.textContent = (data.listing_status || nextStatus) === 'active' ? 'Снять с публикации' : 'Опубликовать';
            showMessage('Статус обновлён.');
        } catch (error) {
            showMessage(error instanceof Error ? error.message : 'Не удалось обновить статус');
        }

        return;
    }

    if (target.matches('[data-profile-delete]')) {
        const listingId = parseInt(target.dataset.listingId || '', 10);

        if (!listingId) {
            return;
        }

        if (!confirm('Удалить объявление без возможности восстановления?')) {
            return;
        }

        try {
            await sendJson(`/api/listings/${listingId}`, 'DELETE', { csrf_token: csrfToken });
            const row = document.querySelector(`[data-listing-id="${listingId}"]`);
            row?.remove();
            showMessage('Объявление удалено.');
        } catch (error) {
            showMessage(error instanceof Error ? error.message : 'Не удалось удалить объявление');
        }

        return;
    }

    if (target.matches('[data-profile-duplicate]')) {
        const listingId = parseInt(target.dataset.listingId || '', 10);

        if (!listingId) {
            return;
        }

        try {
            await sendJson(`/api/listings/${listingId}/duplicate`, 'POST', { csrf_token: csrfToken });
            showMessage('Создана копия объявления. Страница будет обновлена.');
            window.location.reload();
        } catch (error) {
            showMessage(error instanceof Error ? error.message : 'Не удалось создать копию');
        }

        return;
    }

    if (target.matches('[data-profile-refresh]')) {
        const listingId = parseInt(target.dataset.listingId || '', 10);

        if (!listingId) {
            return;
        }

        const row = document.querySelector(`[data-listing-id="${listingId}"]`);

        if (!row) {
            return;
        }

        try {
            const data = await sendJson(`/api/listings/${listingId}/stats`, 'GET');
            const statsContainer = row.querySelector('[data-stats]');

            if (statsContainer) {
                const views = statsContainer.querySelector('[data-stat-views]');
                const comments = statsContainer.querySelector('[data-stat-comments]');
                const favorites = statsContainer.querySelector('[data-stat-favorites]');
                const lastViewed = statsContainer.querySelector('[data-stat-last]');

                if (views) {
                    views.textContent = data.data?.views_count ?? '0';
                }

                if (comments) {
                    comments.textContent = data.data?.comments ?? '0';
                }

                if (favorites) {
                    favorites.textContent = data.data?.favorites ?? '0';
                }

                if (lastViewed) {
                    const last = data.data?.last_viewed_at;
                    lastViewed.textContent = last ? new Date(last).toLocaleString('ru-RU') : '—';
                }
            }

            showMessage('Статистика обновлена.');
        } catch (error) {
            showMessage(error instanceof Error ? error.message : 'Не удалось обновить статистику');
        }
    }
});

const bulkForm = document.querySelector('[data-bulk-form]');

if (bulkForm) {
    bulkForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const actionSelect = bulkForm.querySelector('select[name="action"]');
        const selectedCheckboxes = Array.from(document.querySelectorAll('[data-listing-checkbox]:checked'));
        const ids = selectedCheckboxes.map((checkbox) => parseInt(checkbox.value, 10)).filter((id) => Number.isInteger(id));

        if (!(actionSelect instanceof HTMLSelectElement) || !actionSelect.value) {
            showMessage('Выберите действие для выполнения.');
            return;
        }

        if (ids.length === 0) {
            showMessage('Не выбрано ни одного объявления.');
            return;
        }

        try {
            await sendJson('/api/listings/bulk-action', 'POST', {
                action: actionSelect.value,
                ids,
                csrf_token: csrfToken,
            });

            showMessage('Действие выполнено. Страница будет обновлена.');
            window.location.reload();
        } catch (error) {
            showMessage(error instanceof Error ? error.message : 'Не удалось выполнить действие');
        }
    });
}

const selectAll = document.querySelector('[data-profile-select-all]');

if (selectAll instanceof HTMLInputElement) {
    selectAll.addEventListener('change', () => {
        const checkboxes = document.querySelectorAll('[data-listing-checkbox]');

        checkboxes.forEach((checkbox) => {
            if (checkbox instanceof HTMLInputElement) {
                checkbox.checked = selectAll.checked;
            }
        });
    });
}
