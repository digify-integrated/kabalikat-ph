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
                selector: '#charge_type_form',
                rules: {
                    rules: {
                        charge_type_name: { required: true},
                        value_type: { required: true},
                        charge_value: { required: true},
                        is_variable: { required: true},
                        application_order: { required: true},
                        tax_type: { required: true},
                    },
                    messages: {
                        charge_type_name: { required: 'Enter the charge type' },
                        value_type: { required: 'Choose the value type' },
                        charge_value: { required: 'Enter the charge value' },
                        is_variable: { required: 'Choose if is variable' },
                        application_order: { required: 'Choose the application order' },
                        tax_type: { required: 'Choose the tax type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/charge-type/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save charge type failed with status: ${response.status}`);
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

    $('#is_variable').on('change', function () {
        const isVariable = $(this).val(); // Get the selected value
        const $chargeInput = $('#charge_value'); // Target the input field

        if (isVariable === 'Yes') {
            $chargeInput.val(0);           // Set value to 0
            $chargeInput.prop('readonly', true); // Add readonly
        } else {
            $chargeInput.prop('readonly', false); // Remove readonly
        }
    });
});