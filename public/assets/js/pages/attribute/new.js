import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        forms: [
            {
                selector: '#attribute_form',
                rules: {
                    rules: {
                        attribute_name: { required: true},
                        selection_type: { required: true },
                    },
                    messages: {
                        attribute_name: { required: 'Enter the display name' },
                        selection_type: { required: 'Choose the selection type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/attribute/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save attribute failed with status: ${response.status}`);
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
        ]
    }

    discardCreate();

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
});