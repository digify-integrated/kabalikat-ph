import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import {
  disableButton,
  enableButton,
  detailsDeleteButton,
  detailsTableActionButton,
  permissionToggle
} from '../../form/button.js';
import { generateDropdownOptions, generateDualListBox } from '../../form/field.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { initializeDatatable, reloadDatatable } from '../../util/datatable.js';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = getPageContext();

    let optionsPromise = Promise.resolve();

    const config = {
        forms: [
            {
                selector: '#navigation_menu_form',
                rules: {
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
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('navigation_menu_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/navigation-menu/save', {
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
                }
            },
            {
                selector: '#navigation_menu_route_form',
                rules: {
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('navigation_menu_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-route-data');

                        try {
                            const response = await fetch('/navigation-menu/save-route', {
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
                }
            },
            {
                selector: '#role_permission_assignment_form',
                rules: {
                submitHandler: async (form) => {
                    const formData = new URLSearchParams(new FormData(form));
                    formData.append('navigation_menu_id', ctx.detailId ?? '');
                    formData.append('appId', ctx.appId ?? '');
                    formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                    disableButton('submit-assignment');

                    try {
                        const response = await fetch('/role-permission/save-navigation-menu-role-assignment', {
                            method: 'POST',
                            body: formData,
                        });

                        if (!response.ok) {
                            throw new Error(`Save navigation menu role assignment failed with status: ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success) {
                            reloadDatatable('#role-permission-table');
                            $('#role-permission-assignment-modal').modal('hide');
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message);
                        }
                    } catch (error) {
                        handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                    } finally {
                        enableButton('submit-assignment');
                    }
                },
            }
            },
        ],
        table: {
            url: '/role-permission/generate-navigation-menu-role-permission-table',
            selector: '#role-permission-table',
            serverSide: false,
            order: [[0, 'asc']],
            ajaxData: {
                navigation_menu_id: ctx.detailId,
                page_navigation_menu_id: ctx.navigationMenuId,
            },
            columns: [
                { data: 'ROLE' },
                { data: 'READ_ACCESS' },
                { data: 'CREATE_ACCESS' },
                { data: 'WRITE_ACCESS' },
                { data: 'DELETE_ACCESS' },
                { data: 'IMPORT_ACCESS' },
                { data: 'EXPORT_ACCESS' },
                { data: 'LOGS_ACCESS' },
                { data: 'ACTION' },
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
                { width: 'auto', bSortable: false, targets: 8, responsivePriority: 1 },
            ],
            addons: {
                subControls: {
                    searchSelector: '#navigation-menu-permission-datatable-search',
                    lengthSelector: '#navigation-menu-permission-datatable-length',
                },
            },
        },
        dropdown: [
            { url: '/app/generate-options', dropdownSelector: '#app_id' },
            { url: '/navigation-menu/generate-options', dropdownSelector: '#parent_id' },
            { url: '/export/table-list', dropdownSelector: '#table_name' },
        ],
        detailsList: [
            {
                url: '/navigation-menu/fetch-details',
                formSelector: '#navigation_menu_form', // ✅ no self-reference
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
            },
            {
                url: '/navigation-menu/fetch-route-details',
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
            }
        ],
        delete: {
            trigger: '#delete-navigation-menu',
            url: '/navigation-menu/delete',
            swalTitle: 'Confirm Navigation Menu Deletion',
            swalText: 'Are you sure you want to delete this navigation menu?',
            confirmButtonText: 'Delete',
        },
        duallist: {
            trigger: '#assign-role-permission',
            url: '/role-permission/generate-navigation-menu-role-dual-listbox-options',
            selectSelector: 'role_id',
            data: {
                navigationMenuId: ctx.detailId ?? ''
            }
        },
        table_action: {
            trigger: '.delete-role-permission',
            url: '/role-permission/delete',
            table: '#role-permission-table',
            swalTitle: 'Confirm Role Permission Deletion',
            swalText: 'Are you sure you want to delete this role permission?',
            confirmButtonText: 'Delete'
        },
        permission_toggle: {
            trigger: '.update-role-permission',
            url: '/role-permission/update',
        },
        lognotes: {
            trigger: '.view-role-permission-log-notes',
            table: 'role_permission'
        }
    };

    ;(async () => {
        try {
        optionsPromise = Promise.all(
            config.dropdown.map((cfg) =>
            generateDropdownOptions({
                url: cfg.url,
                dropdownSelector: cfg.dropdownSelector,
            })
            )
        );

        const rolePermissionTablePromise = Promise.resolve().then(() =>
            initializeDatatable(config.table)
        );

        const fetchDetailsPromise = Promise.all(
            config.detailsList.map((cfg) => displayDetails(cfg))
        );

        await Promise.all([
            optionsPromise,
            fetchDetailsPromise,
            rolePermissionTablePromise,
        ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    config.forms.forEach((cfg) => {
        initValidation(cfg.selector, cfg.rules);
    });

    attachLogNotesHandler();
    attachLogNotesClassHandler(config.lognotes.trigger, config.lognotes.table);

    generateDualListBox(config.duallist);

    detailsDeleteButton(config.delete);

    detailsTableActionButton(config.table_action);

    permissionToggle(config.permission_toggle);
});
