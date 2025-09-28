document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.auth-form');

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            const errors = validateForm(form);
            clearErrors(form);

            if (errors.length > 0) {
                event.preventDefault();
                renderErrors(form, errors);
            }
        });
    });
});

function validateForm(form) {
    const errors = [];
    const emailField = form.querySelector('input[type="email"]');
    const passwordField = form.querySelector('input[type="password"]');
    const nameField = form.querySelector('input[name="name"]');

    if (emailField) {
        const email = emailField.value.trim();
        if (email.length === 0) {
            errors.push({ field: 'email', message: 'Введите email.' });
        } else if (!/^\S+@\S+\.\S+$/.test(email)) {
            errors.push({ field: 'email', message: 'Email указан неверно.' });
        }
    }

    if (passwordField) {
        const password = passwordField.value;
        if (password.length === 0) {
            errors.push({ field: 'password', message: 'Введите пароль.' });
        } else if (password.length < 8) {
            errors.push({ field: 'password', message: 'Пароль должен быть не короче 8 символов.' });
        }
    }

    if (nameField) {
        const name = nameField.value.trim();
        if (name.length === 0) {
            errors.push({ field: 'name', message: 'Введите имя.' });
        }
    }

    return errors;
}

function clearErrors(form) {
    const errorElements = form.querySelectorAll('.form-error');
    errorElements.forEach((element) => {
        element.textContent = '';
    });
}

function renderErrors(form, errors) {
    errors.forEach(({ field, message }) => {
        const errorElement = form.querySelector(`.form-error[data-error="${field}"]`);
        if (errorElement) {
            errorElement.textContent = message;
        }
    });
}
