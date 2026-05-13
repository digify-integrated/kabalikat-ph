import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import {
  disableButton,
  enableButton,
  detailsDeleteButton,
  detailsTableActionButton,
} from '../../form/button.js';
import { displayDetails, handleActionFetch, getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { initializeDatatable, reloadDatatable } from '../../util/datatable.js';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = getPageContext();

    let optionsPromise = Promise.resolve();

    const config = {
        forms: [
            {
                selector: '#floor_plan_form',
                rules: {
                    rules: {
                        floor_plan_name: { required: true},
                    },
                    messages: {
                        floor_plan_name: { required: 'Enter the display name' },
                    },
                    submitHandler: async (form) => {
                        const ctx2 = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('floor_plan_id', ctx2.detailId ?? '');
                        formData.append('appId', ctx2.appId ?? '');
                        formData.append('navigationMenuId', ctx2.navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/floor-plan/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save floor plan failed with status: ${response.status}`);
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
                selector: '#floor_plan_tables_form',
                rules: {
                    rules: {
                        table_number: { required: true},
                        seats: { required: true},
                    },
                    messages: {
                        table_number: { required: 'Enter the table number' },
                        seats: { required: 'Enter the seats' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('floor_plan_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');

                        disableButton('submit-floor-plan-tables');

                        try {
                            const response = await fetch('/floor-plan-tables/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save floor plan table failed with status: ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.success) {
                                reloadDatatable('#floor-plan-tables-table');
                                $('#floor-plan-tables-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-floor-plan-tables');
                        }
                    },
                }
            },
        ],
        table: {
            url: '/floor-plan-tables/generate-table',
            selector: '#floor-plan-tables-table',
            serverSide: false,
            order: [[0, 'asc']],
            ajaxData: {
                floor_plan_id: ctx.detailId,
                page_navigation_menu_id: ctx.navigationMenuId,
            },
            columns: [
                { data: 'TABLE_NUMBER' },
                { data: 'SEATS' },
                { data: 'ACTION' },
            ],
            columnDefs: [
                { width: 'auto', targets: 0, responsivePriority: 1 },
                { width: 'auto', targets: 1, responsivePriority: 2 },
                { width: 'auto', bSortable: false, targets: 2, responsivePriority: 3 },
            ],
            addons: {
                subControls: {
                    searchSelector: '#floor-plan-tables-datatable-search',
                    lengthSelector: '#floor-plan-tables-datatable-length',
                },
            },
        },
        details: [
            {
                url: '/floor-plan/fetch-details',
                formSelector: '#floor_plan_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('floor_plan_name').value = data.floorPlanName || '';

                    await optionsPromise;
                },
            }
        ],
        delete: {
            trigger: '#delete-floor-plan',
            url: '/floor-plan/delete',
            swalTitle: 'Confirm Floor Plan Deletion',
            swalText: 'Are you sure you want to delete this floor plan?',
            confirmButtonText: 'Delete',
        },
        table_action: {
            trigger: '.delete-floor-plan-table',
            url: '/floor-plan-tables/delete',
            table: '#floor-plan-tables-table',
            swalTitle: 'Confirm Floor Plan Table Deletion',
            swalText: 'Are you sure you want to delete this floor plan table?',
            confirmButtonText: 'Delete'
        },
        lognotes: {
            trigger: '.view-floor-plan-table-log-notes',
            table: 'floor_plan_table'
        }
    };

    (async () => {
        try {
        const floorPlanTablesTablePromise = Promise.resolve().then(() =>
            initializeDatatable(config.table)
        );

        const fetchDetailsPromise = Promise.all(
            config.details.map((cfg) => displayDetails(cfg))
        );

        await Promise.all([
            fetchDetailsPromise,
            floorPlanTablesTablePromise,
        ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));

    attachLogNotesHandler();
    attachLogNotesClassHandler(config.lognotes.trigger, config.lognotes.table);

    detailsDeleteButton(config.delete);

    detailsTableActionButton(config.table_action);

    document.addEventListener('click', async (event) => {
        const target = event.target;

        const addFloorPlanTablesBtn = target.closest('#add-floor-plan-tables');
        if (addFloorPlanTablesBtn) {
            resetForm('floor_plan_tables_form');
        }

        const updateFloorPlanTable = target.closest('.update-floor-plan-table');
        if (updateFloorPlanTable) {
            const referenceId = updateFloorPlanTable.dataset.referenceId;
        
            await handleActionFetch({
                triggerElement: updateFloorPlanTable,
                url: '/floor-plan-tables/fetch-details',
                referenceKey: 'referenceId',
        
                onSuccess: (data) => {
                    const item = data.data;
        
                    document.getElementById('floor_plan_table_id').value = referenceId;
                    document.getElementById('table_number').value = data.tableNumber || '';
                    document.getElementById('seats').value = data.seats || '';
                }
            });
        }
    });
});
