import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton } from '../../form/button.js';
import { generateDropdownOptions } from '../../form/field.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    const ROUTE = '/save-app';

    generateDropdownOptions({
        url: '/generate-navigation-menu-options',
        dropdownSelector: '#navigation_menu_id',
    });

    initValidation('#app_form', {
        rules: {
            app_name: { required: true},
            app_description: { required: true },
            navigation_menu_id: { required: true },
            order_sequence: { required: true },
        },
        messages: {
            app_name: { required: 'Enter the display name' },
            app_description: { required: 'Enter the description' },
            navigation_menu_id: { required: 'Select the default page' },
            order_sequence: { required: 'Enter the order sequence' },
        },
        submitHandler: async (form) => {
            const formData = new URLSearchParams(new FormData(form));

            disableButton('submit-data');

            try {
                const response = await fetch(ROUTE, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Save app module failed with status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    setNotification(data.title, data.message, data.message_type);
                    window.location = `${page_link}&id=${data.app_module_id}`;
                }
                else{
                    showNotification(data.title, data.message, data.message_type);
                    enableButton('submit-data');
                }
            } catch (error) {
                enableButton('submit-data');
                handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
            }

        },
    });
});