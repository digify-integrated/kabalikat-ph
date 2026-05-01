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
                selector: '#stock_batch_form',
                rules: {
                    rules: {
                        reference_number: { required: true},
                        warehouse_id: { required: true},
                    },
                    messages: {
                        reference_number: { required: 'Enter the reference number' },
                        warehouse_id: { required: 'Choose the warehouse' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('stock_batch_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/stock-batch/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save stock batch failed with status: ${response.status}`);
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
                selector: '#stock_batch_items_form',
                rules: {
                    rules: {
                        product_id: { required: true},
                        batch_number: { required: true},
                        quantity: { required: true},
                        cost_per_unit: { required: true},
                        received_date: { required: true},
                    },
                    messages: {
                        product_id: { required: 'Choose the product' },
                        batch_number: { required: 'Enter the batch number' },
                        quantity: { required: 'Enter the quantity' },
                        cost_per_unit: { required: 'Enter the cost per unit' },
                        received_date: { required: 'Choose the received date' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('stock_batch_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-stock-batch-items');
            
                        try {
                            const response = await fetch('/stock-batch-items/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#stock-batch-items-table');
                                $('#stock-batch-items-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-stock-batch-items');
                        }
                    },
                }
            }
        ],
        table: [
            {
                url: '/stock-batch-items/generate-table',
                selector: '#stock-batch-items-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    stock_batch_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'PRODUCT' },
                    { data: 'BATCH_NUMBER' },
                    { data: 'QUANTITY' },
                    { data: 'COST_PER_UNIT' },
                    { data: 'BATCH_VALUE' },
                    { data: 'EXPIRATION_DATE' },
                    { data: 'RECEIVED_DATE' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', targets: 2, responsivePriority: 3 },
                    { width: 'auto', targets: 3, responsivePriority: 4 },
                    { width: 'auto', targets: 4, responsivePriority: 5 },
                    { width: 'auto', targets: 5, responsivePriority: 6 },
                    { width: 'auto', targets: 6, responsivePriority: 7 },
                    { width: 'auto', targets: 7, responsivePriority: 8 },
                    { width: 'auto', bSortable: false, targets: 7, responsivePriority: 9 },
                ],
                addons: {
                    subControls: {
                        searchSelector: '#stock-batch-items-datatable-search',
                        lengthSelector: '#stock-batch-items-datatable-length',
                    },
                },
            },
        ],
        details: [
            {
                url: '/stock-batch/fetch-details',
                formSelector: '#stock_batch_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('reference_number').value = data.referenceNumber || '';
                    document.getElementById('remarks').value = data.remarks || '';

                    await optionsPromise;

                    $('#warehouse_id').val(data.warehouseId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-stock-batch',
            url: '/stock-batch/delete',
            swalTitle: 'Confirm Stock Batch Deletion',
            swalText: 'Are you sure you want to delete this stock batch?',
            confirmButtonText: 'Delete',
        },
        table_action: [
            {
                trigger: '.delete-stock-batch-items',
                url: '/stock-batch-items/delete',
                table: '#stock-batch-items-table',
                swalTitle: 'Confirm Stock Batch Item Deletion',
                swalText: 'Are you sure you want to delete this stock batch item?',
                confirmButtonText: 'Delete'
            },
        ],
        action: [
            {
                trigger: '#for-approval-stock-batch',
                url: '/stock-batch/for-approval',
                swalTitle: 'Confirm Stock Batch Submission',
                confirmButtonClass : 'success',
                swalText: 'Are you sure you want to submit this stock batch for approval?',
                confirmButtonText: 'Submit for Approval',
            },
            {
                trigger: '#set-to-draft-stock-batch',
                url: '/stock-batch/set-to-draft',
                swalTitle: 'Confirm Stock Batch Set To Draft',
                swalText: 'Are you sure you want to set this stock batch to draft?',
                confirmButtonText: 'Set to Draft',
            },
            {
                trigger: '#cancel-stock-batch',
                url: '/stock-batch/cancel',
                swalTitle: 'Confirm Stock Batch Cancellation',
                swalText: 'Are you sure you want to cancel this stock batch?',
                confirmButtonText: 'Cancel',
            },
            {
                trigger: '#approve-stock-batch',
                url: '/stock-batch/approve',
                swalTitle: 'Confirm Stock Batch Approval',
                swalText: 'Are you sure you want to approve this stock batch?',
                confirmButtonText: 'Approve',
            },
        ],
        lognotes: [
            {
                trigger: '.view-stock-batch-items-log-notes',
                table: 'stock_batch_items',
            },
        ],
        dropdown: [
            { url: '/products/generate-product-batch-tracking-options', dropdownSelector: '#product_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#warehouse_id' },
        ],
        datepickers: [
            { selector: '#expiration_date' },
            { selector: '#received_date' },
        ]
    };

    (async () => {
        try {
            config.datepickers.map((cfg) => initializeDatePicker(cfg));

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
});
