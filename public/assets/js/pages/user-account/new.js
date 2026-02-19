import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate, passwordAddOn } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        form: {
            selector: '#user_form',
            rules: {
                rules: {
                    user_name: { required: true},
                    email: { 
                        required: true,
                        typeEmail: true
                    },
                    password: { 
                        required: true,
                        minlength: 8,
                        pattern: '(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9]).+',
                    },
                },
                messages: {
                    user_name: { required: 'Enter the user name' },
                    email: { 
                        required: 'Enter the email',
                        typeEmail: 'Enter a valid email'
                    },
                    password: {
                        required: 'Enter the password',
                        minlength: 'Password must be at least 8 characters.',
                        pattern: 'Password must include uppercase, lowercase, number, and special character.',
                    },
                },
                submitHandler: async (form) => {
                    const ctx = getPageContext();
                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/user/save', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Save user failed with status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            setNotification(data.message, 'success');
                            window.location.assign(data.redirect_link);
                        }
                        else{
                            showNotification(data.message);
                            enableButton('submit-data');
                        }
                    } catch (error) {
                        enableButton('submit-data');
                        handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                    }

                },
            }
        }
    }

    passwordAddOn();

    discardCreate();

    initValidation(config.form.selector, config.form.rules);
});