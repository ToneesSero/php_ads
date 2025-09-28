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


// Инициализация галереи изображений
const gallery = document.querySelector('[data-gallery]');

if (gallery) {
    const track = gallery.querySelector('[data-gallery-track]');
    const items = track ? Array.from(track.querySelectorAll('[data-gallery-item]')) : [];
    const prevButton = gallery.querySelector('[data-gallery-prev]');
    const nextButton = gallery.querySelector('[data-gallery-next]');

    // Если изображений 1 или меньше - скрываем обе кнопки
    if (items.length <= 1) {
        if (prevButton instanceof HTMLButtonElement) {
            prevButton.style.display = 'none';
        }
        if (nextButton instanceof HTMLButtonElement) {
            nextButton.style.display = 'none';
        }
        console.log('Gallery controls hidden - only', items.length, 'images');
    } else {
        // Убираем hidden атрибут и показываем обе кнопки для галереи с несколькими изображениями
        if (prevButton instanceof HTMLButtonElement) {
            prevButton.removeAttribute('hidden');
            prevButton.style.display = 'flex';
        }
        if (nextButton instanceof HTMLButtonElement) {
            nextButton.removeAttribute('hidden');
            nextButton.style.display = 'flex';
        }
        console.log('Gallery controls shown for', items.length, 'images');
    }

    if (track && items.length > 1) {
        let currentIndex = 0;

        const setActive = (index) => {
            // Ограничиваем индекс допустимыми значениями
            currentIndex = Math.max(0, Math.min(index, items.length - 1));
            track.style.transform = `translateX(-${currentIndex * 100}%)`;

            items.forEach((item, itemIndex) => {
                item.classList.toggle('is-active', itemIndex === currentIndex);
                item.setAttribute('aria-hidden', itemIndex === currentIndex ? 'false' : 'true');

                if (itemIndex === currentIndex) {
                    const image = item.querySelector('img');

                    if (image instanceof HTMLImageElement && image.dataset.fullImage) {
                        if (!image.dataset.loadedFull) {
                            image.src = image.dataset.fullImage;
                            image.dataset.loadedFull = 'true';
                        }
                    }
                }
            });

            // Обновляем состояние кнопок
            if (prevButton instanceof HTMLButtonElement) {
                prevButton.disabled = currentIndex <= 0;
                prevButton.style.opacity = currentIndex <= 0 ? '0.5' : '1';
                console.log('Prev button - disabled:', prevButton.disabled, 'index:', currentIndex);
            }

            if (nextButton instanceof HTMLButtonElement) {
                nextButton.disabled = currentIndex >= items.length - 1;
                nextButton.style.opacity = currentIndex >= items.length - 1 ? '0.5' : '1';
                console.log('Next button - disabled:', nextButton.disabled, 'index:', currentIndex, 'max:', items.length - 1);
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

        // Привязываем обработчики событий
        if (prevButton instanceof HTMLButtonElement) {
            prevButton.addEventListener('click', showPrev);
            console.log('Prev button event listener added');
        }

        if (nextButton instanceof HTMLButtonElement) {
            nextButton.addEventListener('click', showNext);
        }
        // Инициализируем первый слайд
        setActive(0);        
    }
}
