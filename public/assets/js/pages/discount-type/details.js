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
                        formData.append('discount_type_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/discount-type/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save discount type failed with status: ${response.status}`);
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
                url: '/discount-type/fetch-details',
                formSelector: '#discount_type_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('discount_type_name').value = data.discountTypeName || '';
                    document.getElementById('discount_value').value = data.discountValue || '0';

                    await optionsPromise;

                    $('#value_type').val(data.valueType).trigger('change');
                    $('#is_variable').val(data.isVariable).trigger('change');
                    $('#application_order').val(data.applicationOrder).trigger('change');
                    $('#is_vat_exempt').val(data.isVatExempt).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-discount-type',
            url: '/discount-type/delete',
            swalTitle: 'Confirm Discount Type Deletion',
            swalText: 'Are you sure you want to delete this discount type?',
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
