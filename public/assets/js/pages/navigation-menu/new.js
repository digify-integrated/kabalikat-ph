import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate } from '../../form/button.js';
import { generateDropdownOptions } from '../../form/field.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const FORM = '#navigation_menu_form';
    const ROUTE = '/save-navigation-menu';

    discardCreate();

    const dropdownConfigs = [
        { url: '/generate-app-options', dropdownSelector: '#app_id' },
        { url: '/generate-navigation-menu-options', dropdownSelector: '#parent_id' },
        { url: '/table-list', dropdownSelector: '#table_name' },
    ];

    dropdownConfigs.forEach(cfg => {
        generateDropdownOptions({
            url: cfg.url,
            dropdownSelector: cfg.dropdownSelector
        });
    });

    initValidation(FORM, {
        rules: {
            navigation_menu_name: { required: true },
            app_id: { required: true },
            order_sequence: { required: true }
        },
        messages: {
            navigation_menu_name: { required: 'Enter the display name' },
            app_id: { required: 'Choose the app' },
            order_sequence: { required: 'Enter the order sequence' }
        },
        submitHandler: async (form) => {
            const ctx = getPageContext();
            const formData = new URLSearchParams(new FormData(form));
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

            disableButton('submit-data');

            try {
                const response = await fetch(ROUTE, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Save navigation menu failed with status: ${response.status}`);
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
    });
});