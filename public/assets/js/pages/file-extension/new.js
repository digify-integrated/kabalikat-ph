import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        form: {
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
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-data');

                    try {
                        const response = await fetch('/file-extension/save', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Save file extension failed with status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            setNotification(data.message, 'success');
                            window.location.assign(data.redirect_link);
                        }
                        else{
                            showNotification(data.message);
                            enableButton('submit-data');
                        }
                    } catch (error) {
                        enableButton('submit-data');
                        handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                    }

                },
            }
        },
        dropdown: {
            url: '/file-type/generate-options',
            dropdownSelector: '#file_type_id',
        }
    }

    discardCreate();

    generateDropdownOptions(config.dropdown);

    initValidation(config.form.selector, config.form.rules);
});