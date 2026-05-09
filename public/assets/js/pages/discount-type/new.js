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
                selector: '#discount_type_form',
                rules: {
                    rules: {
                        discount_type_name: { required: true},
                        value_type: { required: true},
                        discount_value: { required: true},
                        is_variable: { required: true},
                        application_order: { required: true},
                        is_vat_exempt: { required: true},
                    },
                    messages: {
                        discount_type_name: { required: 'Enter the discount type' },
                        value_type: { required: 'Choose the value type' },
                        discount_value: { required: 'Enter the discount value' },
                        is_variable: { required: 'Choose if is variable' },
                        application_order: { required: 'Choose the application order' },
                        is_vat_exempt: { required: 'Choose the if is VAT exempt' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/discount-type/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save discount type failed with status: ${response.status}`);
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
        const isVariable = $(this).val();
        const $discountInput = $('#discount_value');

        if (isVariable === 'Yes') {
            $discountInput.val(0);
            $discountInput.prop('readonly', true);
        } else {
            $discountInput.prop('readonly', false);
        }
    });
});