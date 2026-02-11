import { initValidation } from '../util/validation.js';
import { showNotification } from '../util/notifications.js';
import { handleSystemError } from '../modules/system-errors.js';
import { disableButton, enableButton, passwordAddOn } from '../modules/form-utilities.js';

document.addEventListener('DOMContentLoaded', () => {
    initValidation('#login_form', {
        rules: {
            email: { required: true, email: true },
            password: { required: true }
        },
        messages: {
            email: { required: 'Please enter your email.' },
            password: { required: 'Please enter your password.' }
        },
        submitHandler: async (form) => {
            const formData = new URLSearchParams(new FormData(form));

            disableButton('signin');

            try {
                const response = await fetch('/authenticate', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    }
                });

                let data = null;
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    try {
                        data = await response.json();
                    } catch {
                        data = null;
                    }
                }

                if (data && data.success === false) {
                    showNotification({
                        message: data.message,
                        type: data.message_type || 'error'
                    });
                    return;
                }

                if (response.ok && data?.success) {
                    window.location.href = data.redirect_link;
                    return;
                }

                await handleSystemError(
                    response,
                    'error',
                    `Request failed (${response.status})`
                );

            } catch (err) {
                await handleSystemError(
                    err,
                    'error',
                    'Network error'
                );
            } finally {
                enableButton('signin');
            }
        },
    });

    passwordAddOn();
});
