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
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Authentication failed with status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect_link;
                } else {
                    showNotification(data.message, data.message_type);
                    enableButton('signin');
                }
            } catch (error) {
                enableButton('signin');
            }
        },
    });

    passwordAddOn();
});
