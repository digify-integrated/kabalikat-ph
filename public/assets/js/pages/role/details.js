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
import { generateDualListBox } from '../../form/field.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { initializeDatatable, reloadDatatable } from '../../util/datatable.js';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = getPageContext();

    let optionsPromise = Promise.resolve();

    const config = {
        forms: [
            {
                selector: '#role_form',
                rules: {
                    rules: {
                        role_name: { required: true},
                        role_description: { required: true },
                    },
                    messages: {
                        role_name: { required: 'Enter the display name' },
                        role_description: { required: 'Enter the description' },
                    },
                    submitHandler: async (form) => {
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('role_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/role/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save role failed with status: ${response.status}`);
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
                selector: '#navigation_menu_permission_assignment_form',
                rules: {
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('role_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-navigation-menu-assignment');

                        try {
                            const response = await fetch('/role-permission/save-role-navigation-menu-assignment', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save navigation menu assignment failed with status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                reloadDatatable('#navigation-menu-permission-table');
                                $('#navigation-menu-permission-assignment-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-navigation-menu-assignment');
                        }
                    },
                }
            },
            {
                selector: '#system_action_permission_assignment_form',
                rules: {
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('role_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-system-action-assignment');

                        try {
                            const response = await fetch('/role-system-action-permission/save-role-system-action-assignment', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save system action assignment failed with status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                reloadDatatable('#system-action-permission-table');
                                $('#system-action-permission-assignment-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-system-action-assignment');
                        }
                    },
                }
            },
            {
                selector: '#user_account_assignment_form',
                rules: {
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('role_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-user-account-assignment');

                        try {
                            const response = await fetch('/role-user-account/save-role-user-account-assignment', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save user account assignment failed with status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                reloadDatatable('#role-user-account-table');
                                $('#user-account-assignment-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-user-account-assignment');
                        }
                    },
                }
            },
        ],
        table: [
            {
                url: '/role-permission/generate-role-navigation-menu-permission-table',
                selector: '#navigation-menu-permission-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    role_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                columns: [
                    { data: 'NAVIGATION_MENU' },
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
            {
                url: '/role-system-action-permission/generate-role-system-action-permission-table',
                selector: '#system-action-permission-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    role_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'SYSTEM_ACTION' },
                    { data: 'SYSTEM_ACTION_ACCESS' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', bSortable: false, targets: 1, responsivePriority: 2 },
                    { width: 'auto', bSortable: false, targets: 2, responsivePriority: 3 },
                ],
                addons: {
                    subControls: {
                        searchSelector: '#system-action-permission-datatable-search',
                        lengthSelector: '#system-action-permission-datatable-length',
                    },
                },
            },
            {
                url: '/role-user-account/generate-role-user-account-table',
                selector: '#role-user-account-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    role_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'USER_ACCOUNT' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', bSortable: false, targets: 1, responsivePriority: 2 },
                ],
                addons: {
                    subControls: {
                        searchSelector: '#role-user-account-datatable-search',
                        lengthSelector: '#role-user-account-datatable-length',
                    },
                },
            },
        ],
        detailsList: [
            {
                url: '/role/fetch-details',
                formSelector: '#role_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('role_name').value = data.roleName || '';
                    document.getElementById('role_description').value = data.roleDescription ?? '';

                    await optionsPromise;
                },
            }
        ],
        delete: {
            trigger: '#delete-role',
            url: '/role/delete',
            swalTitle: 'Confirm Role Deletion',
            swalText: 'Are you sure you want to delete this role?',
            confirmButtonText: 'Delete',
        },
        duallist: [
            {
                trigger: '#assign-navigation-menu-permission',
                url: '/role-permission/generate-role-navigation-menu-dual-listbox-options',
                selectSelector: 'navigation_menu_id',
                data: {
                    roleId: ctx.detailId ?? ''
                }
            },
            {
                trigger: '#assign-system-action-permission',
                url: '/role-system-action-permission/generate-role-system-action-dual-listbox-options',
                selectSelector: 'system_action_id',
                data: {
                    roleId: ctx.detailId ?? ''
                }
            },
            {
                trigger: '#assign-user-account',
                url: '/role-user-account/generate-role-user-account-dual-listbox-options',
                selectSelector: 'user_account_id',
                data: {
                    roleId: ctx.detailId ?? ''
                }
            },
        ],
        table_action: [
            {
                trigger: '.delete-role-navigation-menu-permission',
                url: '/role-permission/delete',
                table: '#navigation-menu-permission-table',
                swalTitle: 'Confirm Navigation Menu Permission Deletion',
                swalText: 'Are you sure you want to delete this navigation menu permission?',
                confirmButtonText: 'Delete'
            },
            {
                trigger: '.delete-role-system-action-permission',
                url: '/role-system-action-permission/delete',
                table: '#system-action-permission-table',
                swalTitle: 'Confirm System Action Permission Deletion',
                swalText: 'Are you sure you want to delete this system action permission?',
                confirmButtonText: 'Delete'
            },
            {
                trigger: '.delete-role-user-account',
                url: '/role-user-account/delete',
                table: '#role-user-account-table',
                swalTitle: 'Confirm User Account Deletion',
                swalText: 'Are you sure you want to delete this user account?',
                confirmButtonText: 'Delete'
            },
        ],
        permission_toggle: [
            {
                trigger: '.update-role-navigation-menu-permission',
                url: '/role-permission/update',
            },
            {
                trigger: '.update-role-system-action-permission',
                url: '/role-system-action-permission/update',
            },
        ],
        lognotes: [
            {
                trigger: '.view-role-navigation-menu-permission-log-notes',
                table: 'role_permission'
            },
            {
                trigger: '.view-role-system-action-permission-log-notes',
                table: 'role_system_action_permission'
            },
            {
                trigger: '.view-role-user-account-log-notes',
                table: 'role_user_account'
            },
        ]
    };

    ;(async () => {
        try {
        const permissionTablePromise = Promise.all(
            config.table.map((cfg) => initializeDatatable(cfg))
        );

        const fetchDetailsPromise = Promise.all(
            config.detailsList.map((cfg) => displayDetails(cfg))
        );

        await Promise.all([
            fetchDetailsPromise,
            permissionTablePromise,
        ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));

    attachLogNotesHandler();
    
    config.lognotes.map((cfg) => attachLogNotesClassHandler(cfg.trigger, cfg.table));    

    config.duallist.map((cfg) => generateDualListBox(cfg))

    detailsDeleteButton(config.delete);

    config.table_action.map((cfg) => detailsTableActionButton(cfg));
    config.permission_toggle.map((cfg) => permissionToggle(cfg));
});
