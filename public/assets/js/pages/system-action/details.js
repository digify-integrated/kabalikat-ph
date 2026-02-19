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
                selector: '#system_action_form',
                rules: {
                    rules: {
                        system_action_name: { required: true},
                        system_action_description: { required: true },
                    },
                    messages: {
                        system_action_name: { required: 'Enter the display name' },
                        system_action_description: { required: 'Enter the description' },
                    },
                    submitHandler: async (form) => {
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('system_action_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/system-action/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save system action failed with status: ${response.status}`);
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
                selector: '#role_permission_assignment_form',
                rules: {
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('system_action_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-assignment');

                        try {
                            const response = await fetch('/role-system-action-permission/save-system-action-role-assignment', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save system action role assignment failed with status: ${response.status}`);
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
            url: '/role-system-action-permission/generate-system-action-role-permission-table',
            selector: '#role-permission-table',
            serverSide: false,
            order: [[0, 'asc']],
            ajaxData: {
                system_action_id: ctx.detailId,
                page_navigation_menu_id: ctx.navigationMenuId,
            },
            columns: [
                { data: 'ROLE' },
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
        detailsList: [
            {
                url: '/system-action/fetch-details',
                formSelector: '#system_action_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('system_action_name').value = data.systemActionName || '';
                    document.getElementById('system_action_description').value = data.systemActionDescription ?? '';

                    await optionsPromise;
                },
            }
        ],
        delete: {
            trigger: '#delete-system-action',
            url: '/system-action/delete',
            swalTitle: 'Confirm System Action Deletion',
            swalText: 'Are you sure you want to delete this system action?',
            confirmButtonText: 'Delete',
        },
        duallist: {
            trigger: '#assign-role-permission',
            url: '/role-system-action-permission/generate-system-action-role-dual-listbox-options',
            selectSelector: 'role_id',
            data: {
                systemActionId: ctx.detailId ?? ''
            }
        },
        table_action: {
            trigger: '.delete-role-permission',
            url: '/role-system-action-permission/delete',
            table: '#role-permission-table',
            swalTitle: 'Confirm Role Permission Deletion',
            swalText: 'Are you sure you want to delete this role permission?',
            confirmButtonText: 'Delete'
        },
        permission_toggle: {
            trigger: '.update-role-permission',
            url: '/role-system-action-permission/update',
        },
        lognotes: {
            trigger: '.view-role-permission-log-notes',
            table: 'role_system_action_permission'
        }
    };

    ;(async () => {
        try {
        const rolePermissionTablePromise = Promise.resolve().then(() =>
            initializeDatatable(config.table)
        );

        const fetchDetailsPromise = Promise.all(
            config.detailsList.map((cfg) => displayDetails(cfg))
        );

        await Promise.all([
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
