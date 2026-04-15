import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton } from '../../form/button.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { initializeDatatable } from '../../util/datatable.js';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = getPageContext();

    let optionsPromise = Promise.resolve();

    const config = {
        forms: [
            {
                selector: '#file_type_form',
                rules: {
                    rules: {
                        file_type_name: { required: true},
                    },
                    messages: {
                        file_type_name: { required: 'Enter the file type' },
                    },
                    submitHandler: async (form) => {
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('file_type_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/file-type/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save file type failed with status: ${response.status}`);
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
                url: '/file-type/fetch-details',
                formSelector: '#file_type_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('file_type_name').value = data.fileTypeName || '';

                    await optionsPromise;
                },
            }
        ],
        delete: {
            trigger: '#delete-file-type',
            url: '/file-type/delete',
            swalTitle: 'Confirm File Type Deletion',
            swalText: 'Are you sure you want to delete this file type?',
            confirmButtonText: 'Delete',
        }
    };

    ;(async () => {
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

    config.forms.forEach((cfg) => {
        initValidation(cfg.selector, cfg.rules);
    });

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
