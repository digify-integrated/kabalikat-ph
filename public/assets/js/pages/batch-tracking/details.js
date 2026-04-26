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
                selector: '#batch_tracking_form',
                rules: {
                    rules: {
                        batch_tracking_name: { required: true},
                        batch_tracking: { required: true},
                        file_type_id: { required: true},
                    },
                    messages: {
                        batch_tracking_name: { required: 'Enter the batch tracking' },
                        batch_tracking: { required: 'Enter the extension' },
                        file_type_id: { required: 'Choose the file type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('batch_tracking_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/batch-tracking/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save batch tracking failed with status: ${response.status}`);
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
                url: '/batch-tracking/fetch-details',
                formSelector: '#batch_tracking_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('batch_tracking_name').value = data.fileExtensionName || '';
                    document.getElementById('batch_tracking').value = data.fileExtension || '';

                    await optionsPromise;

                    $('#file_type_id').val(data.fileTypeId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-batch-tracking',
            url: '/batch-tracking/delete',
            swalTitle: 'Confirm Batch Tracking Deletion',
            swalText: 'Are you sure you want to delete this batch tracking?',
            confirmButtonText: 'Delete',
        },
        dropdown: [
            { url: '/file-type/generate-options', dropdownSelector: '#file_type_id' }
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
