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
                selector: '#purchase_order_form',
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
                        formData.append('purchase_order_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/purchase-order/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save purchase order failed with status: ${response.status}`);
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
                selector: '#purchase_order_items_form',
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
                        formData.append('purchase_order_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-purchase-order-items');
            
                        try {
                            const response = await fetch('/purchase-order-items/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#purchase-order-items-table');
                                $('#purchase-order-items-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-purchase-order-items');
                        }
                    },
                }
            }
        ],
        table: [
            {
                url: '/purchase-order-items/generate-table',
                selector: '#purchase-order-items-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    purchase_order_id: ctx.detailId,
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
                        searchSelector: '#purchase-order-items-datatable-search',
                        lengthSelector: '#purchase-order-items-datatable-length',
                    },
                },
            },
        ],
        details: [
            {
                url: '/purchase-order/fetch-details',
                formSelector: '#purchase_order_form',
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
            trigger: '#delete-purchase-order',
            url: '/purchase-order/delete',
            swalTitle: 'Confirm Purchase Order Deletion',
            swalText: 'Are you sure you want to delete this purchase order?',
            confirmButtonText: 'Delete',
        },
        table_action: [
            {
                trigger: '.delete-purchase-order-items',
                url: '/purchase-order-items/delete',
                table: '#purchase-order-items-table',
                swalTitle: 'Confirm Purchase Order Item Deletion',
                swalText: 'Are you sure you want to delete this purchase order item?',
                confirmButtonText: 'Delete'
            },
        ],
        action: [
            {
                trigger: '#for-approval-purchase-order',
                url: '/purchase-order/for-approval',
                swalTitle: 'Confirm Purchase Order Submission',
                confirmButtonClass : 'success',
                swalText: 'Are you sure you want to submit this purchase order for approval?',
                confirmButtonText: 'Submit for Approval',
            },
            {
                trigger: '#set-to-draft-purchase-order',
                url: '/purchase-order/set-to-draft',
                swalTitle: 'Confirm Purchase Order Set To Draft',
                swalText: 'Are you sure you want to set this purchase order to draft?',
                confirmButtonText: 'Set to Draft',
            },
            {
                trigger: '#cancel-purchase-order',
                url: '/purchase-order/cancel',
                swalTitle: 'Confirm Purchase Order Cancellation',
                swalText: 'Are you sure you want to cancel this purchase order?',
                confirmButtonText: 'Cancel',
            },
            {
                trigger: '#approve-purchase-order',
                url: '/purchase-order/approve',
                swalTitle: 'Confirm Purchase Order Approval',
                swalText: 'Are you sure you want to approve this purchase order?',
                confirmButtonText: 'Approve',
            },
        ],
        lognotes: [
            {
                trigger: '.view-purchase-order-items-log-notes',
                table: 'purchase_order_items',
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

    document.addEventListener('click', async (event) => {
        const target = event.target;
    
        const addAddon = target.closest('#add-purchase-order-items');
        if (addAddon) {
            resetForm('purchase_order_items_form');
        }
    });
});
