import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        form: {
            selector: '#unit_conversion_form',
            rules: {
                rules: {
                    from_unit_id: { required: true},
                    to_unit_id: { required: true},
                    conversion_factor: { required: true},
                    },
                messages: {
                    from_unit_id: { required: 'Choose the from' },
                    to_unit_id: { required: 'Choose the to' },
                    conversion_factor: { required: 'Enter the conversion factor' },
                },
                submitHandler: async (form) => {
                    const ctx = getPageContext();
                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/unit-conversion/save', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Save unit conversion failed with status: ${response.status}`);
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
        },
        dropdown : [
            { url: '/unit/generate-options', dropdownSelector: '#from_unit_id' },
            { url: '/unit/generate-options', dropdownSelector: '#to_unit_id' },
        ]
    }

    discardCreate();

    config.dropdown.forEach(cfg => {
        generateDropdownOptions({
            url: cfg.url,
            dropdownSelector: cfg.dropdownSelector
        });
    });

    initValidation(config.form.selector, config.form.rules);
});