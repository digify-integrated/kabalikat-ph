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
                selector: '#stock_transfer_form',
                rules: {
                    rules: {
                        reference_number: { required: true},
                        from_warehouse_id: { required: true},
                        to_warehouse_id: { required: true},
                        stock_transfer_reason_id: { required: true},
                    },
                    messages: {
                        reference_number: { required: 'Enter the reference number' },
                        from_warehouse_id: { required: 'Choose the from' },
                        to_warehouse_id: { required: 'Choose the to' },
                        stock_transfer_reason_id: { required: 'Choose the stock transfer reason' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('stock_transfer_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/stock-transfer/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save stock transfer failed with status: ${response.status}`);
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
                selector: '#stock_transfer_items_form',
                rules: {
                    rules: {
                        stock_level_id: { required: true},
                        transfer_quantity: { required: true},
                    },
                    messages: {
                        stock_level_id: { required: 'Choose the stock' },
                        transfer_quantity: { required: 'Enter the transfer quantity' },
                    },
                    submitHandler: async (form) => {
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('stock_transfer_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx.navigationMenuId ?? '');
            
                        disableButton('submit-stock-transfer-items');
            
                        try {
                            const response = await fetch('/stock-transfer-items/save', {
                                method: 'POST',
                                body: formData,
                            });
            
                            if (!response.ok) {
                                throw new Error(`Save role assignment failed with status: ${response.status}`);
                            }
            
                            const data = await response.json();
            
                            if (data.success) {
                                reloadDatatable('#stock-transfer-items-table');
                                $('#stock-transfer-items-modal').modal('hide');
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message);
                            }
                        } catch (error) {
                            handleSystemError(error, 'fetch_failed', `Fetch request failed: ${error.message}`);
                        } finally {
                            enableButton('submit-stock-transfer-items');
                        }
                    },
                }
            }
        ],
        table: [
            {
                url: '/stock-transfer-items/generate-table',
                selector: '#stock-transfer-items-table',
                serverSide: false,
                order: [[0, 'asc']],
                ajaxData: {
                    stock_transfer_id: ctx.detailId,
                    page_navigation_menu_id: ctx.navigationMenuId,
                },
                 columns: [
                    { data: 'PRODUCT' },
                    { data: 'QUANTITY' },
                    { data: 'ACTION' },
                ],
                columnDefs: [
                    { width: 'auto', targets: 0, responsivePriority: 1 },
                    { width: 'auto', targets: 1, responsivePriority: 2 },
                    { width: 'auto', bSortable: false, targets: 2, responsivePriority: 3 },
                ],
                addons: {
                    subControls: {
                        searchSelector: '#stock-transfer-items-datatable-search',
                        lengthSelector: '#stock-transfer-items-datatable-length',
                    },
                },
            },
        ],
        details: [
            {
                url: '/stock-transfer/fetch-details',
                formSelector: '#stock_transfer_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('reference_number').value = data.referenceNumber || '';
                    document.getElementById('remarks').value = data.remarks || '';

                    await optionsPromise;

                    $('#from_warehouse_id').val(data.fromWarehouseId).trigger('change');
                    $('#to_warehouse_id').val(data.toWarehouseId).trigger('change');
                    $('#stock_transfer_reason_id').val(data.stockTransferReasonId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-stock-transfer',
            url: '/stock-transfer/delete',
            swalTitle: 'Confirm Stock Transfer Deletion',
            swalText: 'Are you sure you want to delete this stock transfer?',
            confirmButtonText: 'Delete',
        },
        table_action: [
            {
                trigger: '.delete-stock-transfer-items',
                url: '/stock-transfer-items/delete',
                table: '#stock-transfer-items-table',
                swalTitle: 'Confirm Stock Transfer Item Deletion',
                swalText: 'Are you sure you want to delete this stock transfer item?',
                confirmButtonText: 'Delete'
            },
        ],
        action: [
            {
                trigger: '#for-approval-stock-transfer',
                url: '/stock-transfer/for-approval',
                swalTitle: 'Confirm Stock Transfer Submission',
                confirmButtonClass : 'success',
                swalText: 'Are you sure you want to submit this stock transfer for approval?',
                confirmButtonText: 'Submit for Approval',
            },
            {
                trigger: '#set-to-draft-stock-transfer',
                url: '/stock-transfer/set-to-draft',
                swalTitle: 'Confirm Stock Transfer Set To Draft',
                swalText: 'Are you sure you want to set this stock transfer to draft?',
                confirmButtonText: 'Set to Draft',
            },
            {
                trigger: '#cancel-stock-transfer',
                url: '/stock-transfer/cancel',
                swalTitle: 'Confirm Stock Transfer Cancellation',
                swalText: 'Are you sure you want to cancel this stock transfer?',
                confirmButtonText: 'Cancel',
            },
            {
                trigger: '#approve-stock-transfer',
                url: '/stock-transfer/approve',
                swalTitle: 'Confirm Stock Transfer Approval',
                swalText: 'Are you sure you want to approve this stock transfer?',
                confirmButtonText: 'Approve',
            },
        ],
        lognotes: [
            {
                trigger: '.view-stock-transfer-items-log-notes',
                table: 'stock_transfer_items',
            },
        ],
        dropdown: [
            { url: '/stock-transfer-reason/generate-options', dropdownSelector: '#stock_transfer_reason_id' },
            { url: '/stock-level/generate-options', dropdownSelector: '#stock_level_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#from_warehouse_id' },
            { url: '/warehouse/generate-options', dropdownSelector: '#to_warehouse_id' },
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
    
        const addAddon = target.closest('#add-stock-transfer-items');
        if (addAddon) {
            resetForm('stock_transfer_items_form');
        }
    });
});
