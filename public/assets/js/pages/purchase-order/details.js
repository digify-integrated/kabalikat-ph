import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler, attachLogNotesClassHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton, detailsActionButton, detailsTableActionButton } from '../../form/button.js';
import { displayDetails, handleActionFetch, getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
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
                        supplier_id: { required: true},
                        warehouse_id: { required: true},
                        order_date: { required: true},
                        expected_delivery_date: { required: true},
                    },
                    messages: {
                        reference_number: { required: 'Enter the reference number' },
                        supplier_id: { required: 'Choose the supplier' },
                        warehouse_id: { required: 'Choose the warehouse' },
                        order_date: { required: 'Choose the order date' },
                        expected_delivery_date: { required: 'Choose the expected delivery date' },
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
                        ordered_quantity: { required: true},
                        estimated_cost: { required: true},
                    },
                    messages: {
                        product_id: { required: 'Choose the product' },
                        ordered_quantity: { required: 'Enter the order quantity' },
                        estimated_cost: { required: 'Enter the estimated cost' },
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
                    { data: 'ORDERED_QUANTITY' },
                    { data: 'RECEIVED_QUANTITY' },
                    { data: 'CANCELLED_QUANTITY' },
                    { data: 'REMAINING_QUANTITY' },
                    { data: 'ESTIMATED_COST' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', targets: 2, responsivePriority: 3 },
                    { width: 'auto', targets: 3, responsivePriority: 4 },
                    { width: 'auto', targets: 4, responsivePriority: 5 },
                    { width: 'auto', targets: 5, responsivePriority: 6 },
                    { width: 'auto', bSortable: false, targets: 6, responsivePriority: 7 },
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
                    document.getElementById('order_date').value = data.orderDate || '';
                    document.getElementById('expected_delivery_date').value = data.expectedDeliveryDate || '';
                    document.getElementById('remarks').value = data.remarks || '';

                    await optionsPromise;

                    $('#supplier_id').val(data.supplierId).trigger('change');
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
            {
                trigger: '#on-process-purchase-order',
                url: '/purchase-order/on-process',
                swalTitle: 'Confirm Purchase Order On-Process',
                swalText: 'Are you sure you want to tag this purchase order as on-process?',
                confirmButtonText: 'On-Process',
            },
        ],
        lognotes: [
            {
                trigger: '.view-purchase-order-items-log-notes',
                table: 'purchase_order_items',
            },
        ],
        dropdown: [
            { url: '/products/generate-active-product-options', dropdownSelector: '#product_id' },
            { url: '/supplier/generate-options', dropdownSelector: '#supplier_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#warehouse_id' },
        ],
        datepickers: [
            { selector: '#order_date' },
            { selector: '#expected_delivery_date' },
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
    
        const updatePurchaseOrderItem = target.closest('.update-purchase-order-items');
        if (updatePurchaseOrderItem) {
            const referenceId = updatePurchaseOrderItem.dataset.referenceId;

            await handleActionFetch({
                triggerElement: updatePurchaseOrderItem,
                url: '/purchase-order-items/fetch-details',
                referenceKey: 'referenceId',

                onSuccess: (data) => {
                    const item = data.data;

                    document.getElementById('purchase_order_items_id').value = referenceId;
                    document.getElementById('ordered_quantity').value = data.orderedQuantity || '0.01';
                    document.getElementById('estimated_cost').value = data.estimatedCost || '0.01';

                    $('#product_id').val(data.productId).trigger('change');
                }
            });
        }
    });
});
