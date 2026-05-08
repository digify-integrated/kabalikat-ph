import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton } from '../../form/button.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    let optionsPromise = Promise.resolve();

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
                        formData.append('charge_type_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/charge-type/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save charge type failed with status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-data');
                        }
                    },
                }
            }
        ],
        details: [
            {
                url: '/charge-type/fetch-details',
                formSelector: '#charge_type_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('charge_type_name').value = data.chargeTypeName || '';
                    document.getElementById('charge_value').value = data.chargeValue || '0';

                    await optionsPromise;

                    $('#value_type').val(data.valueType).trigger('change');
                    $('#is_variable').val(data.isVariable).trigger('change');
                    $('#application_order').val(data.applicationOrder).trigger('change');
                    $('#tax_type').val(data.taxType).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-charge-type',
            url: '/charge-type/delete',
            swalTitle: 'Confirm Charge Type Deletion',
            swalText: 'Are you sure you want to delete this charge type?',
            confirmButtonText: 'Delete',
        },
    };

    (async () => {
        try {
            const fetchDetailsPromise = Promise.all(
                config.details.map((cfg) => displayDetails(cfg))
            );

            await Promise.all([
                fetchDetailsPromise,
            ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);

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
