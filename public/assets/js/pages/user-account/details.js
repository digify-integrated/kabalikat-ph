import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton, detailsActionButton, imageRealtimeUploadButton, passwordAddOn, detailsTableActionButton, } from '../../form/button.js';
import { generateDualListBox } from '../../form/field.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { initializeDatatable, reloadDatatable } from '../../util/datatable.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    let optionsPromise = Promise.resolve();
    const ctx = getPageContext();

    const config = {
        forms: [
            {
                selector: '#user_form',
                rules: {
                    rules: {
                        user_name: { required: true},
                        email: { 
                            required: true,
                            typeEmail: true
                        }
                    },
                    messages: {
                        user_name: { required: 'Enter the user name' },
                        email: { 
                            required: 'Enter the email',
                            typeEmail: 'Enter a valid email'
                        },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('user_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/user/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save user failed with status: ${response.status}`);
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
                },
            },
            {
                selector: '#role_assignment_form',
                rules: {
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('user_account_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-assignment');
            
                        try {
                            const response = await fetch('/role-user-account/save-user-account-role-assignment', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#role-table');
                                $('#role-assignment-modal').modal('hide');
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
        table: [
            {
                url: '/role-user-account/generate-user-account-role-table',
                selector: '#role-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    user_account_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'ROLE' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', bSortable: false, targets: 1, responsivePriority: 2 },
                ],
                addons: {
                    subControls: {
                        searchSelector: '#role-datatable-search',
                        lengthSelector: '#role-datatable-length',
                    },
                },
            },
        ],
        details: {
            url: '/user/fetch-details',
            formSelector: '#user_form',
            busyHideTargets: ['#submit-data'],
            onSuccess: async (data) => {
                document.getElementById('user_name').value = data.name || '';
                document.getElementById('email').value = data.email || '';
                
                const thumbnail = document.getElementById('profile_picture_image');
                if (thumbnail) thumbnail.style.backgroundImage = `url(${data.profilePicture || ''})`;

                await optionsPromise;
            },
        },
        duallist: [
            {
                trigger: '#assign-role',
                url: '/role-user-account/generate-user-account-role-dual-listbox-options',
                selectSelector: 'role_id',
                data: {
                    userAccountId: ctx.detailId ?? ''
                }
            },
        ],
        delete: {
            trigger: '#delete-user',
            url: '/user/delete',
            swalTitle: 'Confirm User Deletion',
            swalText: 'Are you sure you want to delete this user?',
            confirmButtonText: 'Delete',
        },
        action: [
            {
                trigger: '#activate-user',
                url: '/user/activate',
                swalTitle: 'Confirm User Activation',
                confirmButtonClass : 'success',
                swalText: 'Are you sure you want to activate this user?',
                confirmButtonText: 'Activate',
            },
            {
                trigger: '#deactivate-user',
                url: '/user/deactivate',
                swalTitle: 'Confirm User Deactivation',
                swalText: 'Are you sure you want to deactivate this user?',
                confirmButtonText: 'Deactivate',
            },
        ],
        table_action: [
            {
                trigger: '.delete-role-permission',
                url: '/role-user-account/delete',
                table: '#role-table',
                swalTitle: 'Confirm Role Deletion',
                swalText: 'Are you sure you want to delete this role?',
                confirmButtonText: 'Delete'
            },
        ],
        upload: {
            trigger: '#profile_picture',
            url: '/user/upload-user-profile-picture',
        },
        lognotes: [
            {
                trigger: '.view-role-permission-log-notes',
                table: 'role_user_account'
            },
        ]
    };

    ;(async () => {
        try {
            await displayDetails(config.details);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    passwordAddOn();

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
    config.table.map((cfg) => initializeDatatable(cfg))
    config.duallist.map((cfg) => generateDualListBox(cfg))

    attachLogNotesHandler();
    config.lognotes.map((cfg) => attachLogNotesClassHandler(cfg.trigger, cfg.table));

    detailsDeleteButton(config.delete);

    config.action.map((cfg) => detailsActionButton(cfg));
    config.table_action.map((cfg) => detailsTableActionButton(cfg));

    imageRealtimeUploadButton(config.upload);
});
