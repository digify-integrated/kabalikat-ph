import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        forms: [
            {
                selector: '#unit_form',
                rules: {
                    rules: {
                        unit_name: { required: true},
                        abbreviation: { required: true},
                        unit_type_id: { required: true},
                    },
                    messages: {
                        unit_name: { required: 'Enter the unit' },
                        abbreviation: { required: 'Enter the abbreviation' },
                        unit_type_id: { required: 'Choose the unit type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/unit/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save unit failed with status: ${response.status}`);
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
            { url: '/unit-type/generate-options', dropdownSelector: '#unit_type_id' }
        ]
    }

    discardCreate();

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
});