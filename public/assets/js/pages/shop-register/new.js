import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate, passwordAddOn } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        forms: [
            {
                selector: '#shop_register_form',
                rules: {
                    rules: {
                        shop_register_name: { required: true},
                        company_id: { required: true},
                        is_restaurant: { required: true},
                        shop_register_status: { required: true},
                    },
                    messages: {
                        shop_register_name: { required: 'Enter the shop register name' },
                        company_id: { required: 'Choose the company' },
                        is_restaurant: { required: 'Choose if resturant' },
                        shop_register_status: { required: 'Choose the shop register status' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/shop-register/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save shop register failed with status: ${response.status}`);
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
            { url: '/company/generate-options', dropdownSelector: '#company_id' }
        ]
    }

    discardCreate();
 
    config.dropdown.map((cfg) => generateDropdownOptions(cfg));

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
});