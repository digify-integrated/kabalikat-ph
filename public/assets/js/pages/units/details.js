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
                        formData.append('unit_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/unit/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save unit failed with status: ${response.status}`);
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
                url: '/unit/fetch-details',
                formSelector: '#unit_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('unit_name').value = data.unitName || '';
                    document.getElementById('abbreviation').value = data.abbreviation || '';

                    await optionsPromise;

                    $('#unit_type_id').val(data.unitTypeId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-unit',
            url: '/unit/delete',
            swalTitle: 'Confirm Unit Deletion',
            swalText: 'Are you sure you want to delete this unit?',
            confirmButtonText: 'Delete',
        },
        dropdown: [
            { url: '/unit-type/generate-options', dropdownSelector: '#unit_type_id' }
        ],
    };

    (async () => {
        try {
            optionsPromise = Promise.all(
                config.dropdown.map((cfg) => generateDropdownOptions(cfg))
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

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
