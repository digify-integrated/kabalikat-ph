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
                selector: '#stock_transfer_form',
                rules: {
                    rules: {
                        reference_number: { required: true},
                        from_warehouse_id: { required: true},
                        to_warehouse_id: { required: true},
                        stock_transfer_reason_id: { required: true},
                    },
                    messages: {
                        reference_number: { required: 'Enter the reference number' },
                        from_warehouse_id: { required: 'Choose the from' },
                        to_warehouse_id: { required: 'Choose the to' },
                        stock_transfer_reason_id: { required: 'Choose the stock transfer reason' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/stock-transfer/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save stock transfer failed with status: ${response.status}`);
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
            { url: '/warehouse/generate-options', dropdownSelector: '#from_warehouse_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#to_warehouse_id' },
            { url: '/stock-transfer-reason/generate-options', dropdownSelector: '#stock_transfer_reason_id' },
        ],
    }

    discardCreate();

    config.dropdown.map((cfg) => generateDropdownOptions(cfg));

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
});