import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, discardCreate, detailsDeleteButton, imageRealtimeUploadButton } from '../../form/button.js';
import { generateDropdownOptions } from '../../form/field.js';
import { displayDetails } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';

document.addEventListener('DOMContentLoaded', () => {
    const FORM = '#app_form';
    const FORM_URL = '/save-app';
    const DETAILS_URL = '/fetch-app-details';
    const DELETE_TRIGGER = '#delete-app-module';
    const DELETE_URL = '/delete-app';
    const IMAGE_TRIGGER = '#app_logo';
    const IMAGE_UPLOAD_URL = '/upload-app-logo';

    discardCreate();
    attachLogNotesHandler();

    (async () => {
        try {
            const optionsPromise = generateDropdownOptions({
                url: '/generate-navigation-menu-options',
                dropdownSelector: '#navigation_menu_id',
            });

            await displayDetails({
                url: DETAILS_URL,
                formSelector: FORM,
                onSuccess: async (data) => {
                    document.getElementById('app_name').value = data.appName || '';
                    document.getElementById('app_description').value = data.appDescription || '';
                    document.getElementById('app_version').value = data.appVersion || '';
                    document.getElementById('order_sequence').value = data.orderSequence || '';

                    const thumbnail = document.getElementById('app_thumbnail');
                    if (thumbnail) thumbnail.style.backgroundImage = `url(${data.appLogo || ''})`;

                    await optionsPromise;

                    $('#navigation_menu_id').val(data.navigationMenuId).trigger('change');
                },
            });

            await optionsPromise;
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    initValidation(FORM, {
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
                const response = await fetch(FORM_URL, {
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

    attachLogNotesHandler('#log-notes-main', '#details-id', 'app_module');

    detailsDeleteButton({
        'trigger' : DELETE_TRIGGER,
        'url' : DELETE_URL,
        'swalTitle' : 'Confirm App Deletion',
        'swalText' : 'Are you sure you want to delete this app?',
        'confirmButtonText' : 'Delete'
    });

    imageRealtimeUploadButton({
        'trigger' : IMAGE_TRIGGER,
        'url' : IMAGE_UPLOAD_URL
    });
});
