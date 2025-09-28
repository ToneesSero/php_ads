(function () {
    const formSelector = '[data-message-form]';

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

    const createMessageElement = (message) => {
        const wrapper = document.createElement('div');
        const isOwn = Boolean(message && message.own);
        wrapper.className = 'message-item';

        if (isOwn) {
            wrapper.classList.add('message-item--own');
        } else {
            wrapper.classList.add('message-item--incoming');
        }

        const text = document.createElement('p');
        text.className = 'message-item__text';
        text.textContent = message && typeof message.text === 'string' ? message.text : '';
        wrapper.appendChild(text);

        if (message && message.listing_title) {
            const listingInfo = document.createElement('p');
            listingInfo.className = 'message-item__listing';
            listingInfo.textContent = `Объявление: ${message.listing_title}`;
            wrapper.appendChild(listingInfo);
        }

        if (message && message.created_at_formatted) {
            const time = document.createElement('time');
            time.className = 'message-item__time';
            time.textContent = message.created_at_formatted;
            wrapper.appendChild(time);
        }

        return wrapper;
    };

    const appendMessageToThread = (form, message) => {
        const selector = form.dataset.messagesTarget;

        if (!selector) {
            return;
        }

        const container = document.querySelector(selector);

        if (!container) {
            return;
        }

        const element = createMessageElement(message);
        container.appendChild(element);

        const emptyState = document.querySelector('[data-messages-empty]');

        if (emptyState) {
            emptyState.hidden = true;
        }

        container.scrollTop = container.scrollHeight;
    };

    const showSuccess = (form, message) => {
        const box = form.querySelector('[data-message-success]');

        if (!box) {
            return;
        }

        box.hidden = false;
        box.textContent = message;

        window.setTimeout(() => {
            box.hidden = true;
        }, 4000);
    };

    const showError = (form, message) => {
        const box = form.querySelector('[data-message-error]');

        if (!box) {
            window.alert(message);
            return;
        }

        box.textContent = message;
    };

    const clearError = (form) => {
        const box = form.querySelector('[data-message-error]');

        if (box) {
            box.textContent = '';
        }
    };

    const handleSubmit = async (form) => {
        const textarea = form.querySelector('textarea[name="text"]');
        const recipient = form.querySelector('input[name="to_user_id"]');
        const submit = form.querySelector('button[type="submit"]');

        if (!(textarea instanceof HTMLTextAreaElement) || !(recipient instanceof HTMLInputElement)) {
            return;
        }

        const csrfToken = getCsrfToken();

        if (!csrfToken) {
            showError(form, 'Не удалось получить CSRF токен. Обновите страницу.');
            return;
        }

        const messageText = textarea.value.trim();

        if (messageText === '') {
            showError(form, 'Введите текст сообщения.');
            textarea.focus();
            return;
        }

        clearError(form);

        if (submit instanceof HTMLButtonElement) {
            submit.disabled = true;
        }

        const formData = new FormData(form);
        formData.set('text', messageText);
        formData.set('csrf_token', csrfToken);

        try {
            const response = await fetch('/api/messages', {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                },
            });

            const payload = await response.json();

            if (!response.ok || !payload) {
                const message = payload && payload.error ? payload.error : 'Не удалось отправить сообщение.';

                throw new Error(message);
            }

            if (payload.error) {
                throw new Error(payload.error);
            }

            if (!payload.message) {
                throw new Error('Некорректный ответ сервера.');
            }

            textarea.value = '';

            if (payload.message) {
                appendMessageToThread(form, payload.message);
            }

            showSuccess(form, 'Сообщение отправлено.');
        } catch (error) {
            console.error(error);
            showError(form, error instanceof Error ? error.message : 'Не удалось отправить сообщение.');
        } finally {
            if (submit instanceof HTMLButtonElement) {
                submit.disabled = false;
            }
        }
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches(formSelector)) {
            return;
        }

        event.preventDefault();
        void handleSubmit(form);
    });
})();
