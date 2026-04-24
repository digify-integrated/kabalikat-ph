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
                selector: '#state_form',
                rules: {
                    rules: {
                        state_name: { required: true},
                        country_id: { required: true},
                    },
                    messages: {
                        state_name: { required: 'Enter the state' },
                        country_id: { required: 'Choose the country' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('state_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/state/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save state failed with status: ${response.status}`);
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
                url: '/state/fetch-details',
                formSelector: '#state_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('state_name').value = data.stateName || '';

                    await optionsPromise;

                    $('#country_id').val(data.countryId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-state',
            url: '/state/delete',
            swalTitle: 'Confirm State Deletion',
            swalText: 'Are you sure you want to delete this state?',
            confirmButtonText: 'Delete',
        },
        dropdown: {
            url: '/country/generate-options',
            dropdownSelector: '#country_id',
        },
    };

    (async () => {
        try {
            optionsPromise = generateDropdownOptions(config.dropdown);

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
