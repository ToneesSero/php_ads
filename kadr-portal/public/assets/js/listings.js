const listingsPage = document.querySelector('[data-listings-container]');

if (listingsPage) {
    const filterForm = document.querySelector('[data-listings-filter]');

    if (filterForm) {
        filterForm.addEventListener('submit', (event) => {
            const minInput = filterForm.querySelector('input[name="min_price"]');
            const maxInput = filterForm.querySelector('input[name="max_price"]');

            if (!minInput || !maxInput) {
                return;
            }

            const minValue = parseFloat(minInput.value);
            const maxValue = parseFloat(maxInput.value);

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

        if (resetButton) {
            resetButton.addEventListener('click', () => {
                filterForm.reset();
                const searchInput = filterForm.querySelector('input[name="search"]');

                if (searchInput) {
                    searchInput.value = '';
                }

                window.location.href = '/listings';
            });
        }
    }

    window.listingsConfig = {
        nextPage: listingsPage.dataset.nextPage || null,
        baseQuery: listingsPage.dataset.baseQuery || '',
    };
}
