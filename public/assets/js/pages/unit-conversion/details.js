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
                selector: '#unit_conversion_form',
                rules: {
                    rules: {
                        from_unit_id: { required: true},
                        to_unit_id: { required: true},
                        conversion_factor: { required: true},
                    },
                    messages: {
                        from_unit_id: { required: 'Choose the from' },
                        to_unit_id: { required: 'Choose the to' },
                        conversion_factor: { required: 'Enter the conversion factor' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('unit_conversion_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/unit-conversion/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save unit conversion failed with status: ${response.status}`);
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
                url: '/unit-conversion/fetch-details',
                formSelector: '#unit_conversion_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('conversion_factor').value = data.conversionFactor || '';

                    await optionsPromise;

                    $('#from_unit_id').val(data.fromUnitId).trigger('change');
                    $('#to_unit_id').val(data.toUnitId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-unit-conversion',
            url: '/unit-conversion/delete',
            swalTitle: 'Confirm Unit Conversion Deletion',
            swalText: 'Are you sure you want to delete this unit conversion?',
            confirmButtonText: 'Delete',
        },
        dropdown: [
            { url: '/unit/generate-options', dropdownSelector: '#from_unit_id' },
            { url: '/unit/generate-options', dropdownSelector: '#to_unit_id' },
        ],
    };

    (async () => {
        try {
            optionsPromise = Promise.all(
                config.dropdown.map((cfg) =>
                    generateDropdownOptions({
                        url: cfg.url,
                        dropdownSelector: cfg.dropdownSelector,
                    })
                )
            );

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

    initValidation(config.form.selector, config.form.rules);

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
