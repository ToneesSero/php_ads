(function () {
    'use strict';

    const POLL_INTERVAL = 20000;

    function initComments() {
        const container = document.querySelector('[data-comments]');

        if (!container) {
            return;
        }

        const listingId = container.getAttribute('data-listing-id');
        const listElement = container.querySelector('[data-comments-list]');
        const emptyElement = container.querySelector('[data-comments-empty]');
        const loaderElement = container.querySelector('[data-comments-loader]');
        const errorElement = container.querySelector('[data-comments-error]');
        const showNewButton = container.querySelector('[data-comments-show-new]');
        const newCountElement = container.querySelector('[data-comments-new-count]');
        const csrfToken = container.getAttribute('data-csrf-token') || '';
        const form = document.querySelector('[data-comment-form-target]');
        const statusElement = document.querySelector('[data-comment-status]');

        if (!listingId || !listElement || !emptyElement || !loaderElement || !errorElement || !showNewButton || !newCountElement) {
            console.error('Comments: required elements not found');
            return;
        }

        let lastCommentId = 0;
        let pendingComments = [];
        let pollTimer = null;

        function setLoading(isLoading) {
            if (loaderElement) {
                loaderElement.hidden = !isLoading;
            }
        }

        function setError(message) {
            if (!errorElement) {
                return;
            }
            
            if (!message) {
                errorElement.hidden = true;
                errorElement.textContent = '';
                return;
            }

            errorElement.hidden = false;
            errorElement.textContent = message;
        }

        function updateEmptyState() {
            const hasComments = listElement.children.length > 0;
            emptyElement.hidden = hasComments;
        }

        function createCommentElement(comment) {
            const item = document.createElement('li');
            item.className = 'comment-item';
            item.dataset.commentId = String(comment.id);

            const header = document.createElement('div');
            header.className = 'comment-item__header';

            const author = document.createElement('span');
            author.className = 'comment-item__author';
            author.textContent = comment.author.name;

            const date = document.createElement('time');
            date.className = 'comment-item__date';
            date.dateTime = comment.created_at;
            date.textContent = comment.created_at_formatted;

            header.append(author, date);

            const text = document.createElement('p');
            text.className = 'comment-item__text';
            text.textContent = comment.text;

            item.append(header, text);

            if (comment.own) {
                const actions = document.createElement('div');
                actions.className = 'comment-item__actions';

                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'comment-item__delete';
                deleteButton.dataset.commentDelete = 'true';
                deleteButton.textContent = 'Удалить';

                actions.append(deleteButton);
                item.append(actions);
            }

            return item;
        }

        function appendComments(comments) {
            if (!Array.isArray(comments) || comments.length === 0) {
                return;
            }

            comments.forEach(function (comment) {
                const existing = listElement.querySelector('[data-comment-id="' + comment.id + '"]');

                if (existing) {
                    return;
                }

                const element = createCommentElement(comment);
                listElement.append(element);
            });

            updateEmptyState();
        }

        function prependComments(comments) {
            if (!Array.isArray(comments) || comments.length === 0) {
                return;
            }

            comments.forEach(function (comment) {
                const existing = listElement.querySelector('[data-comment-id="' + comment.id + '"]');

                if (existing) {
                    return;
                }

                const element = createCommentElement(comment);
                listElement.insertBefore(element, listElement.firstChild);
            });

            updateEmptyState();
        }

        function updateLastId(comments) {
            comments.forEach(function (comment) {
                if (typeof comment.id === 'number' && comment.id > lastCommentId) {
                    lastCommentId = comment.id;
                }
            });
        }

        function fetchComments(after) {
            const url = new URL(window.location.origin + '/api/comments/' + listingId);

            if (after) {
                url.searchParams.set('after', String(after));
            }

            return fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Ошибка загрузки комментариев');
                }

                return response.json();
            });
        }

        function startPolling() {
            if (pollTimer !== null) {
                window.clearInterval(pollTimer);
            }

            pollTimer = window.setInterval(function () {
                if (!lastCommentId) {
                    return;
                }

                fetchComments(lastCommentId).then(function (data) {
                    if (!data || !Array.isArray(data.comments) || data.comments.length === 0) {
                        return;
                    }

                    updateLastId(data.comments);
                    pendingComments = pendingComments.concat(data.comments);

                    newCountElement.textContent = String(pendingComments.length);
                    showNewButton.hidden = false;
                }).catch(function () {
                    // Ошибку фонового обновления тихо игнорируем
                });
            }, POLL_INTERVAL);
        }

        function showPendingComments() {
            if (pendingComments.length === 0) {
                return;
            }

            appendComments(pendingComments);
            pendingComments = [];
            newCountElement.textContent = '0';
            showNewButton.hidden = true;
        }

        function handleFormSubmit(event) {
            event.preventDefault();

            if (!form) {
                return;
            }

            const formData = new FormData(form);

            setError('');

            fetch('/api/comments', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (payload) {
                        throw new Error(payload.error || 'Не удалось отправить комментарий');
                    }).catch(function (error) {
                        throw new Error(error.message || 'Не удалось отправить комментарий');
                    });
                }

                return response.json();
            }).then(function (data) {
                if (!data || !data.comment) {
                    return;
                }

                const comment = data.comment;

                updateLastId([comment]);
                appendComments([comment]);

                const textarea = form.querySelector('textarea[name="comment_text"]');

                if (textarea) {
                    textarea.value = '';
                }

                if (statusElement) {
                    statusElement.textContent = 'Комментарий отправлен';
                    window.setTimeout(function () {
                        statusElement.textContent = '';
                    }, 4000);
                }
            }).catch(function (error) {
                if (statusElement) {
                    statusElement.textContent = error.message;
                } else {
                    setError(error.message);
                }
            });
        }

        function handleDeleteClick(event) {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (!target.dataset.commentDelete) {
                return;
            }

            const item = target.closest('.comment-item');

            if (!item) {
                return;
            }

            const commentId = item.dataset.commentId;

            if (!commentId) {
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);

            fetch('/api/comments/' + commentId + '/delete', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (payload) {
                        throw new Error(payload.error || 'Не удалось удалить комментарий');
                    }).catch(function (error) {
                        throw new Error(error.message || 'Не удалось удалить комментарий');
                    });
                }

                return response.json();
            }).then(function () {
                item.remove();
                updateEmptyState();
            }).catch(function (error) {
                setError(error.message);
            });
        }

        // Инициализация комментариев
        setLoading(true);
        setError('');
        
        fetchComments().then(function (data) {
            try {
                if (data && Array.isArray(data.comments)) {
                    appendComments(data.comments);
                    updateLastId(data.comments);
                }

                updateEmptyState();
                startPolling();
            } catch (error) {
                console.error('Error processing comments:', error);
                setError('Ошибка при обработке комментариев');
            }
        }).catch(function (error) {
            console.error('Error fetching comments:', error);
            setError(error.message || 'Ошибка загрузки комментариев');
        }).finally(function () {
            // Принудительно скрываем индикатор загрузки
            setLoading(false);
        });

        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }

        showNewButton.addEventListener('click', function () {
            showPendingComments();
        });

        listElement.addEventListener('click', handleDeleteClick);

        window.addEventListener('beforeunload', function () {
            if (pollTimer !== null) {
                window.clearInterval(pollTimer);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initComments);
}());