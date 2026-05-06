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
                selector: '#purchase_order_form',
                rules: {
                    rules: {
                        reference_number: { required: true},
                        supplier_id: { required: true},
                        warehouse_id: { required: true},
                        order_date: { required: true},
                        expected_delivery_date: { required: true},
                    },
                    messages: {
                        reference_number: { required: 'Enter the reference number' },
                        supplier_id: { required: 'Choose the supplier' },
                        warehouse_id: { required: 'Choose the warehouse' },
                        order_date: { required: 'Choose the order date' },
                        expected_delivery_date: { required: 'Choose the expected delivery date' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/purchase-order/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save purchase order failed with status: ${response.status}`);
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
            { url: '/supplier/generate-options', dropdownSelector: '#supplier_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#warehouse_id' },
        ],
        datepickers: [
            { selector: '#order_date' },
            { selector: '#expected_delivery_date' },
        ]
    }

    discardCreate();

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));
    config.datepickers.map((cfg) => initializeDatePicker(cfg));

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
});