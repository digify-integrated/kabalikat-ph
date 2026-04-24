import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton } from '../../form/button.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = getPageContext();

    let optionsPromise = Promise.resolve();

    const config = {
        forms: [
            {
                selector: '#country_form',
                rules: {
                    rules: {
                        country_name: { required: true},
                    },
                    messages: {
                        country_name: { required: 'Enter the country' },
                    },
                    submitHandler: async (form) => {
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('country_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/country/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save country failed with status: ${response.status}`);
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
        detailsList: [
            {
                url: '/country/fetch-details',
                formSelector: '#country_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('country_name').value = data.countryName || '';

                    await optionsPromise;
                },
            }
        ],
        delete: {
            trigger: '#delete-country',
            url: '/country/delete',
            swalTitle: 'Confirm Country Deletion',
            swalText: 'Are you sure you want to delete this country?',
            confirmButtonText: 'Delete',
        }
    };

    (async () => {
        try {
            const fetchDetailsPromise = Promise.all(
                config.detailsList.map((cfg) => displayDetails(cfg))
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
