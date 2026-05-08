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
                selector: '#charge_type_form',
                rules: {
                    rules: {
                        charge_type_name: { required: true},
                        charge_type: { required: true},
                        file_type_id: { required: true},
                    },
                    messages: {
                        charge_type_name: { required: 'Enter the charge type' },
                        charge_type: { required: 'Enter the extension' },
                        file_type_id: { required: 'Choose the file type' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('charge_type_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/charge-type/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save charge type failed with status: ${response.status}`);
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
                url: '/charge-type/fetch-details',
                formSelector: '#charge_type_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('charge_type_name').value = data.chargeTypeName || '';
                    document.getElementById('charge_type').value = data.chargeType || '';

                    await optionsPromise;

                    $('#file_type_id').val(data.fileTypeId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-charge-type',
            url: '/charge-type/delete',
            swalTitle: 'Confirm Charge Type Deletion',
            swalText: 'Are you sure you want to delete this charge type?',
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
