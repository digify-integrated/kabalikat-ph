import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { generateDropdownOptions } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        forms: [
            {
                selector: '#upload_setting_form',
                rules: {
                    rules: {
                        upload_setting_name: { required: true},
                        upload_setting_description: { required: true},
                        max_file_size: { required: true},
                    },
                    messages: {
                        upload_setting_name: { required: 'Enter the upload setting' },
                        upload_setting_description: { required: 'Enter the description' },
                        max_file_size: { required: 'Enter the max file size' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/upload-setting/save', {
                                method: 'POST',
                                body: formData
                            });

                            if (!response.ok) {
                                throw new Error(`Save upload setting failed with status: ${response.status}`);
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
            }
        ],
        dropdown: [
            { url: '/file-extension/generate-options', dropdownSelector: '#file_extension_id', data : { multiple: true } }
        ]
    }

    discardCreate();

    config.dropdown.map((cfg) => generateDropdownOptions(cfg))

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
});