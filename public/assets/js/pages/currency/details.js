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
                selector: '#currency_form',
                rules: {
                    rules: {
                        currency_name: { required: true},
                        symbol: { required: true},
                        shorthand: { required: true},
                    },
                    messages: {
                        currency_name: { required: 'Enter the currency' },
                        symbol: { required: 'Enter the symbol' },
                        shorthand: { required: 'Enter the shorthand' },
                    },
                    submitHandler: async (form) => {
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('currency_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/currency/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save currency failed with status: ${response.status}`);
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
                url: '/currency/fetch-details',
                formSelector: '#currency_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('currency_name').value = data.currencyName || '';
                    document.getElementById('symbol').value = data.symbol || '';
                    document.getElementById('shorthand').value = data.shorthand || '';

                    await optionsPromise;
                },
            }
        ],
        delete: {
            trigger: '#delete-currency',
            url: '/currency/delete',
            swalTitle: 'Confirm Currency Deletion',
            swalText: 'Are you sure you want to delete this currency?',
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

    initValidation(config.form.selector, config.form.rules);

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
