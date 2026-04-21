import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate, passwordAddOn } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        form: {
            selector: '#product_form',
            rules: {
                rules: {
                    product_name: { required: true},
                    product_type: { required: true},
                    product_status: { required: true},
                    tax_classification: { required: true},
                    base_price: { required: true},
                    cost_price: { required: true},
                    base_unit_id: { required: true},
                    inventory_flow: { required: true},
                    reorder_level: { required: true},
                },
                messages: {
                    product_name: { required: 'Enter the product name' },
                    product_type: { required: 'Choose the product type' },
                    product_status: { required: 'Choose the product status' },
                    tax_classification: { required: 'Choose the tax classification' },
                    base_price: { required: 'Enter the base price' },
                    cost_price: { required: 'Enter the cost price' },
                    base_unit_id: { required: 'Choose the base unit' },
                    inventory_flow: { required: 'Choose the inventory flow' },
                    reorder_level: { required: 'Enter the reorder level' },
                },
                submitHandler: async (form) => {
                    const ctx = getPageContext();
                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/products/save', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Save product failed with status: ${response.status}`);
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
        dropdown: {
            url: '/unit/generate-options',
            dropdownSelector: '#base_unit_id'
        }
    }

    discardCreate();
 
    generateDropdownOptions(config.dropdown);

    initValidation(config.form.selector, config.form.rules);
});