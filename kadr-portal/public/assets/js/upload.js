(function () {
    'use strict';

    const widgets = document.querySelectorAll('[data-image-upload]');

    if (!widgets.length) {
        return;
    }

    widgets.forEach((widget) => {
        const dropzone = widget.querySelector('[data-upload-dropzone]');
        const input = widget.querySelector('[data-upload-input]');
        const list = widget.querySelector('[data-upload-list]');
        const trigger = widget.querySelector('[data-upload-trigger]');

        if (!dropzone || !input || !list) {
            return;
        }

        const form = widget.closest('form');
        const csrfField = form ? form.querySelector('input[name="csrf_token"]') : null;
        const uploadUrl = widget.getAttribute('data-upload-url') || '';
        const deleteUrl = widget.getAttribute('data-delete-url') || '';
        const maxImages = parseInt(widget.getAttribute('data-max-images') || '0', 10);

        if (!uploadUrl || !deleteUrl) {
            return;
        }

        const showError = (message) => {
            window.alert(message);
        };

        const getCsrfToken = () => (csrfField instanceof HTMLInputElement ? csrfField.value : '');

        const getCurrentCount = () => list.querySelectorAll('[data-upload-item]').length;

        const hasReachedLimit = () => maxImages > 0 && getCurrentCount() >= maxImages;

        const updateDropzoneState = (isActive) => {
            dropzone.classList.toggle('image-upload__dropzone--active', Boolean(isActive));
        };

        const createItem = (image) => {
            const item = document.createElement('div');
            item.className = 'image-upload__item';
            item.setAttribute('data-upload-item', '');
            item.setAttribute('data-upload-id', image.id);

            const img = document.createElement('img');
            img.src = image.thumb;
            img.alt = 'Превью изображения объявления';
            item.appendChild(img);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'image-upload__remove';
            removeButton.setAttribute('data-upload-remove', '');
            removeButton.setAttribute('aria-label', 'Удалить изображение');
            removeButton.textContent = '×';
            item.appendChild(removeButton);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'uploaded_images[]';
            hidden.value = image.id;
            item.appendChild(hidden);

            removeButton.addEventListener('click', () => {
                const formData = new FormData();
                formData.append('id', image.id);
                const csrfToken = getCsrfToken();

                if (csrfToken) {
                    formData.append('csrf_token', csrfToken);
                }

                fetch(deleteUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                })
                    .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
                    .then(({ ok, data }) => {
                        if (!ok || !data.success) {
                            showError(data && data.error ? data.error : 'Не удалось удалить изображение.');
                            return;
                        }

                        item.remove();
                    })
                    .catch(() => {
                        showError('Не удалось удалить изображение.');
                    });
            });

            return item;
        };

        const uploadFile = (file) => {
            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                showError('Можно загрузить только изображения JPG или PNG.');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showError('Размер файла не должен превышать 5 МБ.');
                return;
            }

            if (hasReachedLimit()) {
                showError('Достигнут лимит изображений.');
                return;
            }

            const placeholder = document.createElement('div');
            placeholder.className = 'image-upload__item image-upload__item--loading';
            placeholder.textContent = 'Загрузка…';
            list.appendChild(placeholder);

            const formData = new FormData();
            formData.append('image', file);
            const csrfToken = getCsrfToken();

            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }

            fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
                .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    placeholder.remove();

                    if (!ok || !data.success) {
                        showError(data && data.error ? data.error : 'Не удалось загрузить изображение.');
                        return;
                    }

                    const item = createItem(data.image);
                    list.appendChild(item);
                })
                .catch(() => {
                    placeholder.remove();
                    showError('Не удалось загрузить изображение.');
                });
        };

        const handleFiles = (fileList) => {
            if (!fileList || !fileList.length) {
                return;
            }

            const availableSlots = maxImages > 0 ? maxImages - getCurrentCount() : fileList.length;

            if (maxImages > 0 && availableSlots <= 0) {
                showError('Достигнут лимит изображений.');
                return;
            }

            Array.from(fileList)
                .slice(0, availableSlots)
                .forEach((file) => uploadFile(file));
        };

        dropzone.addEventListener('dragover', (event) => {
            event.preventDefault();
            updateDropzoneState(true);
        });

        dropzone.addEventListener('dragleave', (event) => {
            event.preventDefault();
            updateDropzoneState(false);
        });

        dropzone.addEventListener('drop', (event) => {
            event.preventDefault();
            updateDropzoneState(false);
            const files = event.dataTransfer ? event.dataTransfer.files : null;
            handleFiles(files);
        });

        dropzone.addEventListener('click', () => {
            if (input instanceof HTMLInputElement) {
                input.click();
            }
        });

        if (trigger instanceof HTMLElement) {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();

                if (input instanceof HTMLInputElement) {
                    input.click();
                }
            });
        }

        if (input instanceof HTMLInputElement) {
            input.addEventListener('change', () => {
                handleFiles(input.files);
                input.value = '';
            });
        }
    });
})();
