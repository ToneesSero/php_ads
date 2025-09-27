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

const gallery = document.querySelector('[data-gallery]');

if (gallery) {
    const track = gallery.querySelector('[data-gallery-track]');
    const items = track ? Array.from(track.querySelectorAll('[data-gallery-item]')) : [];
    const prevButton = gallery.querySelector('[data-gallery-prev]');
    const nextButton = gallery.querySelector('[data-gallery-next]');

    if (items.length <= 1) {
        if (prevButton instanceof HTMLButtonElement) {
            prevButton.hidden = true;
        }

        if (nextButton instanceof HTMLButtonElement) {
            nextButton.hidden = true;
        }
    }

    if (track && items.length > 0) {
        let currentIndex = 0;

        const setActive = (index) => {
            currentIndex = index;
            track.style.transform = `translateX(-${index * 100}%)`;

            items.forEach((item, itemIndex) => {
                item.classList.toggle('is-active', itemIndex === index);
                item.setAttribute('aria-hidden', itemIndex === index ? 'false' : 'true');

                if (itemIndex === index) {
                    const image = item.querySelector('img');

                    if (image instanceof HTMLImageElement && image.dataset.fullImage) {
                        if (!image.dataset.loadedFull) {
                            image.src = image.dataset.fullImage;
                            image.dataset.loadedFull = 'true';
                        }
                    }
                }
            });

            if (prevButton instanceof HTMLButtonElement) {
                prevButton.disabled = index === 0;
            }

            if (nextButton instanceof HTMLButtonElement) {
                nextButton.disabled = index >= items.length - 1;
            }
        };

        const showPrev = () => {
            if (currentIndex > 0) {
                setActive(currentIndex - 1);
            }
        };

        const showNext = () => {
            if (currentIndex < items.length - 1) {
                setActive(currentIndex + 1);
            }
        };

        if (prevButton instanceof HTMLButtonElement) {
            prevButton.addEventListener('click', showPrev);
        }

        if (nextButton instanceof HTMLButtonElement) {
            nextButton.addEventListener('click', showNext);
        }

        setActive(0);
    }
}
