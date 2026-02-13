import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { disableButton, enableButton, discardCreate, detailsDeleteButton, imageRealtimeUploadButton } from '../../form/button.js';
import { generateDropdownOptions } from '../../form/field.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext, getCsrfToken } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const ROUTE = '/save-app';

    discardCreate();    

    disableButton('submit-data');

    const displayDetails = async () => {
        try {
            const csrf = getCsrfToken();
            const ctx = getPageContext();

            const formData = new URLSearchParams();
            formData.append('app_id', ctx.detailId ?? '');

            const response = await fetch('/fetch-app-details', {
                method: 'POST',
                body: formData,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    Accept: 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
            });

            if (!response.ok) {
                throw new Error(`Request failed with status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                document.getElementById('app_name').value = data.appName || '';
                document.getElementById('app_description').value = data.appDescription || '';
                document.getElementById('app_version').value = data.appVersion || '';
                document.getElementById('order_sequence').value = data.orderSequence || '';

                $('#navigation_menu_id').val(data.navigationMenuId).trigger('change');

                const thumbnail = document.getElementById('app_thumbnail');
                if (thumbnail) thumbnail.style.backgroundImage = `url(${data.appLogo || ''})`;
            } else if (data.notExist) {
                setNotification(data.message, 'error');
                window.location.href = page_link;
            } else {
                showNotification(data.message);
            }
        } catch (error) {
            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
        }
    };

    (async () => {
        try {
            await generateDropdownOptions({
                url: '/generate-navigation-menu-options',
                dropdownSelector: '#navigation_menu_id',
            });

            await displayDetails();
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        } finally {
            enableButton('submit-data');
        }
    })();

    initValidation('#app_form', {
        rules: {
            app_name: { required: true },
            app_description: { required: true },
            navigation_menu_id: { required: true },
            app_version: { required: true },
            order_sequence: { required: true },
        },
        messages: {
            app_name: { required: 'Enter the display name' },
            app_description: { required: 'Enter the description' },
            navigation_menu_id: { required: 'Select the default page' },
            app_version: { required: 'Enter the app version' },
            order_sequence: { required: 'Enter the order sequence' },
        },
        submitHandler: async (form) => {
            const ctx = getPageContext();
            const formData = new URLSearchParams(new FormData(form));
            formData.append('app_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

            disableButton('submit-data');

            try {
                const response = await fetch(ROUTE, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`Save app module failed with status: ${response.status}`);
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
    });

    detailsDeleteButton({
        'trigger' : '#delete-app-module',
        'url' : '/delete-app',
        'swalTitle' : 'Confirm App Deletion',
        'swalText' : 'Are you sure you want to delete this app?',
        'confirmButtonText' : 'Delete'
    });

    imageRealtimeUploadButton({
        'trigger' : '#app_logo',
        'url' : '/upload-app-logo',
    });
});
