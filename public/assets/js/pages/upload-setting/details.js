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
                selector: '#upload_setting_form',
                rules: {
                    rules: {
                        upload_setting_name: { required: true},
                        upload_setting: { required: true},
                        file_type_id: { required: true},
                    },
                    messages: {
                        upload_setting_name: { required: 'Enter the upload setting' },
                        upload_setting: { required: 'Enter the extension' },
                        file_type_id: { required: 'Choose the file type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('upload_setting_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/upload-setting/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save upload setting failed with status: ${response.status}`);
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
                url: '/upload-setting/fetch-details',
                formSelector: '#upload_setting_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('upload_setting_name').value = data.uploadSettingName || '';
                    document.getElementById('upload_setting_description').value = data.uploadSettingDescription || '';
                    document.getElementById('max_file_size').value = data.maxFileSize || '';

                    await optionsPromise;

                    $('#file_extension_id').val(data.fileExtensionId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-upload-setting',
            url: '/upload-setting/delete',
            swalTitle: 'Confirm Upload Setting Deletion',
            swalText: 'Are you sure you want to delete this upload setting?',
            confirmButtonText: 'Delete',
        },
        dropdown: {
            url: '/file-extension/generate-options',
            dropdownSelector: '#file_extension_id',
            data : {
                multiple: true
            }
        }
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

    initValidation(config.form.selector, config.form.rules);

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
