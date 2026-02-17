import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, discardCreate, detailsDeleteButton, imageRealtimeUploadButton } from '../../form/button.js';
import { generateDropdownOptions } from '../../form/field.js';
import { displayDetails } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { getPageContext } from '../../form/form.js';
import { initializeDatatable, initializeSubDatatableControls } from '../../util/datatable.js';

document.addEventListener('DOMContentLoaded', () => {
    const FORM = '#navigation_menu_form';
    const FORM_URL = '/save-navigation-menu';
    const DETAILS_URL = '/fetch-navigation-menu-details';
    const DELETE_TRIGGER = '#delete-navigation-menu';
    const DELETE_URL = '/delete-navigation-menu';
    const ctx = getPageContext();

    attachLogNotesHandler();

    (async () => {
        try {
            const dropdownConfigs = [
                { url: '/generate-app-options', dropdownSelector: '#app_id' },
                { url: '/generate-navigation-menu-options', dropdownSelector: '#parent_id' },
                { url: '/table-list', dropdownSelector: '#table_name' },
            ];

            const optionsPromise = Promise.all(
                dropdownConfigs.map((cfg) =>
                    generateDropdownOptions({
                        url: cfg.url,
                        dropdownSelector: cfg.dropdownSelector,
                    })
                )
            );

            const navDetailsPromise = displayDetails({
                url: DETAILS_URL,
                formSelector: FORM,
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('navigation_menu_name').value = data.navigationMenuName || '';
                    document.getElementById('order_sequence').value = data.orderSequence ?? '';

                    await optionsPromise;

                    $('#app_id').val(data.appId ?? '').trigger('change');
                    $('#navigation_menu_icon').val(data.navigationMenuIcon ?? '').trigger('change');
                    $('#parent_id').val(data.parentNavigationMenuId ?? '').trigger('change');
                    $('#table_name').val(data.databaseTable ?? '').trigger('change');
                },
            });

            const routeDetailsPromise = displayDetails({
                url: '/fetch-navigation-menu-route-details',
                formSelector: '#navigation_menu_route_form',
                busyHideTargets: ['#submit-route-data'],
                onSuccess: async (data) => {
                    document.getElementById('index_view_file').value = data.indexViewFile || '';
                    document.getElementById('index_js_file').value = data.indexJSFile ?? '';

                    document.getElementById('new_view_file').value = data.newViewFile || '';
                    document.getElementById('new_js_file').value = data.newJSFile ?? '';

                    document.getElementById('details_view_file').value = data.detailsViewFile || '';
                    document.getElementById('details_js_file').value = data.detailsJSFile ?? '';

                    document.getElementById('import_view_file').value = data.importViewFile || '';
                    document.getElementById('import_js_file').value = data.importJSFile ?? '';
                },
            });

            await Promise.all([optionsPromise, navDetailsPromise, routeDetailsPromise]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

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
            formData.append('navigation_menu_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

            disableButton('submit-data');

            try {
                const response = await fetch(FORM_URL, {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`Save navigation menu failed with status: ${response.status}`);
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

    initValidation('#navigation_menu_route_form', {
        submitHandler: async (form) => {
            const formData = new URLSearchParams(new FormData(form));
            formData.append('navigation_menu_id', ctx.detailId ?? '');
            formData.append('appId', ctx.appId ?? '');
            formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

            disableButton('submit-route-data');

            try {
                const response = await fetch('/save-navigation-menu-route', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`Save navigation menu route failed with status: ${response.status}`);
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
                enableButton('submit-route-data');
            }
        },
    });

    attachLogNotesHandler('#log-notes-main', '#details-id');

    initializeDatatable({
        url: '/generate-navigation-menu-role-permission-table',
        selector: '#role-permission-table',
        serverSide: false,
        ajaxData: {
            navigation_menu_id: ctx.detailId
        },
        columns: [
            { data: 'ROLE_NAME' },
            { data: 'READ_ACCESS' },
            { data: 'CREATE_ACCESS' },
            { data: 'WRITE_ACCESS' },
            { data: 'DELETE_ACCESS' },
            { data: 'IMPORT_ACCESS' },
            { data: 'EXPORT_ACCESS' },
            { data: 'LOG_NOTES_ACCESS' },
            { data: 'ACTION' }
        ],
        columnDefs: [
            { width: 'auto', targets: 0, responsivePriority: 1 },
            { width: 'auto', bSortable: false, targets: 1, responsivePriority: 2 },
            { width: 'auto', bSortable: false, targets: 2, responsivePriority: 3 },
            { width: 'auto', bSortable: false, targets: 3, responsivePriority: 4 },
            { width: 'auto', bSortable: false, targets: 4, responsivePriority: 5 },
            { width: 'auto', bSortable: false, targets: 5, responsivePriority: 6 },
            { width: 'auto', bSortable: false, targets: 6, responsivePriority: 7 },
            { width: 'auto', bSortable: false, targets: 7, responsivePriority: 8 },
            { width: 'auto', bSortable: false, targets: 8, responsivePriority: 1 }
        ],
        addons: {
            subControls: {
                searchSelector: '#navigation-menu-permission-datatable-search',
                lengthSelector: '#navigation-menu-permission-datatable-length',
            },
        },
    });


    detailsDeleteButton({
        'trigger' : DELETE_TRIGGER,
        'url' : DELETE_URL,
        'swalTitle' : 'Confirm Navigation Menu Deletion',
        'swalText' : 'Are you sure you want to delete this navigation menu?',
        'confirmButtonText' : 'Delete'
    });
});
