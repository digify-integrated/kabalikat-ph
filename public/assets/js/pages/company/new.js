import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { generateDropdownOptions } from '../../form/field.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        form: {
            selector: '#company_form',
            rules: {
                rules: {
                    company_name: { required: true},
                    address: { required: true },
                    city_id: { required: true },
                    currency_id: { required: true },
                },
                messages: {
                    company_name: { required: 'Enter the display name' },
                    address: { required: 'Enter the address' },
                    city_id: { required: 'Select the city' },
                    currency_id: { required: 'Select the currency' },
                },
                submitHandler: async (form) => {
                    const ctx = getPageContext();
                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/company/save', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Save company failed with status: ${response.status}`);
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
            { url: '/city/generate-options', dropdownSelector: '#city_id' },
            { url: '/currency/generate-options', dropdownSelector: '#currency_id' },
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