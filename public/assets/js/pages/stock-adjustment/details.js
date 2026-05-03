import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton, detailsActionButton, detailsTableActionButton } from '../../form/button.js';
import { displayDetails, getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { initializeDatatable, reloadDatatable } from '../../util/datatable.js';
import { generateDropdownOptions, initializeDatePicker } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    let optionsPromise = Promise.resolve();
    const ctx = getPageContext();

    const config = {
        forms: [
            {
                selector: '#stock_adjustment_form',
                rules: {
                    rules: {
                        reference_number: { required: true},
                        stock_adjustment_reason_id: { required: true},
                    },
                    messages: {
                        reference_number: { required: 'Enter the reference number' },
                        stock_adjustment_reason_id: { required: 'Choose the stock adjustment reason' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('stock_adjustment_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/stock-adjustment/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save stock adjustment failed with status: ${response.status}`);
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
                selector: '#stock_adjustment_items_form',
                rules: {
                    rules: {
                        stock_level_id: { required: true},
                        adjustment_type: { required: true},
                        adjustment_quantity: { required: true},
                    },
                    messages: {
                        stock_level_id: { required: 'Choose the product' },
                        adjustment_number: { required: 'Enter the adjustment number' },
                        adjustment_quantity: { required: 'Enter the adjustment quantity' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('stock_adjustment_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-stock-adjustment-items');
            
                        try {
                            const response = await fetch('/stock-adjustment-items/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#stock-adjustment-items-table');
                                $('#stock-adjustment-items-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-stock-adjustment-items');
                        }
                    },
                }
            }
        ],
        table: [
            {
                url: '/stock-adjustment-items/generate-table',
                selector: '#stock-adjustment-items-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    stock_adjustment_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'PRODUCT' },
                    { data: 'WAREHOUSE' },
                    { data: 'ADJUSTMENT_TYPE' },
                    { data: 'QUANTITY' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', targets: 2, responsivePriority: 3 },
                    { width: 'auto', targets: 3, responsivePriority: 4 },
                    { width: 'auto', bSortable: false, targets: 4, responsivePriority: 5 },
                ],
                addons: {
                    subControls: {
                        searchSelector: '#stock-adjustment-items-datatable-search',
                        lengthSelector: '#stock-adjustment-items-datatable-length',
                    },
                },
            },
        ],
        details: [
            {
                url: '/stock-adjustment/fetch-details',
                formSelector: '#stock_adjustment_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('reference_number').value = data.referenceNumber || '';
                    document.getElementById('remarks').value = data.remarks || '';

                    await optionsPromise;

                    $('#stock_adjustment_reason_id').val(data.stockAdjustmentReasonId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-stock-adjustment',
            url: '/stock-adjustment/delete',
            swalTitle: 'Confirm Stock Adjustment Deletion',
            swalText: 'Are you sure you want to delete this stock adjustment?',
            confirmButtonText: 'Delete',
        },
        table_action: [
            {
                trigger: '.delete-stock-adjustment-items',
                url: '/stock-adjustment-items/delete',
                table: '#stock-adjustment-items-table',
                swalTitle: 'Confirm Stock Adjustment Item Deletion',
                swalText: 'Are you sure you want to delete this stock adjustment item?',
                confirmButtonText: 'Delete'
            },
        ],
        action: [
            {
                trigger: '#for-approval-stock-adjustment',
                url: '/stock-adjustment/for-approval',
                swalTitle: 'Confirm Stock Adjustment Submission',
                confirmButtonClass : 'success',
                swalText: 'Are you sure you want to submit this stock adjustment for approval?',
                confirmButtonText: 'Submit for Approval',
            },
            {
                trigger: '#set-to-draft-stock-adjustment',
                url: '/stock-adjustment/set-to-draft',
                swalTitle: 'Confirm Stock Adjustment Set To Draft',
                swalText: 'Are you sure you want to set this stock adjustment to draft?',
                confirmButtonText: 'Set to Draft',
            },
            {
                trigger: '#cancel-stock-adjustment',
                url: '/stock-adjustment/cancel',
                swalTitle: 'Confirm Stock Adjustment Cancellation',
                swalText: 'Are you sure you want to cancel this stock adjustment?',
                confirmButtonText: 'Cancel',
            },
            {
                trigger: '#approve-stock-adjustment',
                url: '/stock-adjustment/approve',
                swalTitle: 'Confirm Stock Adjustment Approval',
                swalText: 'Are you sure you want to approve this stock adjustment?',
                confirmButtonText: 'Approve',
            },
        ],
        lognotes: [
            {
                trigger: '.view-stock-adjustment-items-log-notes',
                table: 'stock_adjustment_items',
            },
        ],
        dropdown: [
            { url: '/stock-adjustment-reason/generate-options', dropdownSelector: '#stock_adjustment_reason_id' },
            { url: '/stock-level/generate-options', dropdownSelector: '#stock_level_id' },
        ],
    };

    (async () => {
        try {
            optionsPromise = Promise.all(
                config.dropdown.map((cfg) => generateDropdownOptions(cfg))
            );

            const fetchDetailsPromise = Promise.all(
                config.details.map((cfg) => displayDetails(cfg))
            );

            await Promise.all([
                fetchDetailsPromise,
            ]);
        } catch (err) {
            handleSystemError(err, 'init_failed', `Initialization failed: ${err.message}`);
        }
    })();

    config.forms.map((cfg) => initValidation(cfg.selector, cfg.rules));
    config.table.map((cfg) => initializeDatatable(cfg));
    config.action.map((cfg) => detailsActionButton(cfg));
    config.table_action.map((cfg) => detailsTableActionButton(cfg));

    attachLogNotesHandler();
    config.lognotes.map((cfg) => attachLogNotesClassHandler(cfg.trigger, cfg.table));

    detailsDeleteButton(config.delete);

    document.addEventListener('click', async (event) => {
        const target = event.target;
    
        const addAddon = target.closest('#add-stock-adjustment-items');
        if (addAddon) {
            resetForm('stock_adjustment_items_form');
        }
    });
});
