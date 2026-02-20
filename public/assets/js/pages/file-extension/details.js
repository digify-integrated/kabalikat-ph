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
                selector: '#file_extension_form',
                rules: {
                    rules: {
                        file_extension_name: { required: true},
                        file_extension: { required: true},
                        file_type_id: { required: true},
                    },
                    messages: {
                        file_extension_name: { required: 'Enter the file extension' },
                        file_extension: { required: 'Enter the extension' },
                        file_type_id: { required: 'Choose the file type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('file_extension_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/file-extension/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save file extension failed with status: ${response.status}`);
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
                url: '/file-extension/fetch-details',
                formSelector: '#file_extension_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('file_extension_name').value = data.fileExtensionName || '';
                    document.getElementById('file_extension').value = data.fileExtension || '';

                    await optionsPromise;

                    $('#file_type_id').val(data.fileTypeId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-file-extension',
            url: '/file-extension/delete',
            swalTitle: 'Confirm File Extension Deletion',
            swalText: 'Are you sure you want to delete this file extension?',
            confirmButtonText: 'Delete',
        },
        dropdown: {
            url: '/file-type/generate-options',
            dropdownSelector: '#file_type_id',
        },
    };

    ;(async () => {
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

    config.forms.forEach((cfg) => {
        initValidation(cfg.selector, cfg.rules);
    });

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
