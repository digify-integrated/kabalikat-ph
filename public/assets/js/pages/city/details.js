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
                selector: '#city_form',
                rules: {
                    rules: {
                        city_name: { required: true},
                        state_id: { required: true},
                    },
                    messages: {
                        city_name: { required: 'Enter the city' },
                        state_id: { required: 'Choose the state' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('city_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/city/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save city failed with status: ${response.status}`);
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
                url: '/city/fetch-details',
                formSelector: '#city_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('city_name').value = data.cityName || '';

                    await optionsPromise;

                    $('#state_id').val(data.stateId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-city',
            url: '/city/delete',
            swalTitle: 'Confirm City Deletion',
            swalText: 'Are you sure you want to delete this city?',
            confirmButtonText: 'Delete',
        },
        dropdown: {
            url: '/state/generate-options',
            dropdownSelector: '#state_id',
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
