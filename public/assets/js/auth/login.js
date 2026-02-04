import { initValidation } from '../util/validation.js';
import { showNotification } from '../util/notifications.js';
import { passwordAddOn } from '../util/password.js';
import { disableButton, enableButton } from '../util/button.js';

document.addEventListener('DOMContentLoaded', () => {
    initValidation('#login_form', {
        rules: {
            email: { required: true, email: true },
            password: { required: true },
            relation_name: { required: true },
        },
        messages: {
            email: {
                required: 'Please enter your email.',
            },
            password: {
                required: 'Please enter your password.',
                minlength: 'Password must be at least 6 characters.',
            },
            relation_name: {
                required: 'Please enter the relation name',
            },
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

                // Try to parse JSON regardless of status
                let data = null;
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    data = await response.json();
                }

                // If Laravel returned your JSON error shape (429, 401, 422, etc.)
                if (data && data.success === false) {
                    showNotification(data.message, data.message_type);
                    enableButton('signin');
                    return;
                }

                // If success
                if (response.ok && data?.success) {
                    window.location.href = data.redirect_link;
                    return;
                }

                // Fallback for unexpected non-JSON / non-standard responses
                showNotification(
                    `Request failed (${response.status}). Please try again.`,
                    'error'
                );
                enableButton('signin');
            } catch (error) {
                showNotification('Network error. Please try again.', 'error');
                enableButton('signin');
            }

        },
    });

    passwordAddOn();
});
