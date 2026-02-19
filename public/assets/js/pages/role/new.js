import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        form: {
            selector: '#role_form',
            rules: {
                rules: {
                    role_name: { required: true},
                    role_description: { required: true },
                },
                messages: {
                    role_name: { required: 'Enter the display name' },
                    role_description: { required: 'Enter the description' },
                },
                submitHandler: async (form) => {
                    const ctx = getPageContext();
                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/role/save', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Save role failed with status: ${response.status}`);
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

    discardCreate();

    initValidation(config.form.selector, config.form.rules);
});