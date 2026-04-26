import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { generateDropdownOptions, initializeDatePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        forms: [
            {
                selector: '#batch_tracking_form',
                rules: {
                    rules: {
                        batch_tracking_name: { required: true},
                        batch_tracking: { required: true},
                        file_type_id: { required: true},
                    },
                    messages: {
                        batch_tracking_name: { required: 'Enter the batch tracking' },
                        batch_tracking: { required: 'Enter the extension' },
                        file_type_id: { required: 'Choose the file type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/batch-tracking/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save batch tracking failed with status: ${response.status}`);
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
        ],
        dropdown: [
            { url: '/products/generate-product-batch-tracking-options', dropdownSelector: '#product_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#warehouse_id' },
        ],
        datepickers: [
            { selector: '#expiration_date' },
            { selector: '#received_date' },
        ]
    }

    discardCreate();

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));
    config.datepickers.map((cfg) => initializeDatePicker(cfg));

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
});