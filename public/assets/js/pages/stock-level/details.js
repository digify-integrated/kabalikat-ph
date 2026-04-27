import { initValidation } from '../../util/validation.js';
import { showNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton, detailsActionButton } from '../../form/button.js';
import { displayDetails, getPageContext } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';
import { generateDropdownOptions, initializeDatePicker  } from '../../form/field.js';

document.addEventListener('DOMContentLoaded', () => {
    let optionsPromise = Promise.resolve();

    const config = {
        forms: [
            {
                selector: '#stock_level_form',
                rules: {
                    rules: {
                        product_id: { required: true},
                        warehouse_id: { required: true},
                        batch_number: { required: true},
                        quantity: { required: true},
                        cost_per_unit: { required: true},
                        received_date: { required: true},
                    },
                    messages: {
                        product_id: { required: 'Choose the stock level' },
                        warehouse_id: { required: 'Choose the warehouse' },
                        batch_number: { required: 'Enter the batch number' },
                        quantity: { required: 'Enter the quantity' },
                        cost_per_unit: { required: 'Enter the cost per unit' },
                        received_date: { required: 'Enter the received date' },
                    },
                    submitHandler: async (form) => {
                        const ctx = getPageContext();
                        const formData = new URLSearchParams(new FormData(form));
                        formData.append('stock_level_id', ctx.detailId ?? '');
                        formData.append('appId', ctx.appId ?? '');
                        formData.append('navigationMenuId', ctx .navigationMenuId ?? '');

                        disableButton('submit-data');

                        try {
                            const response = await fetch('/stock-level/save', {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error(`Save stock level failed with status: ${response.status}`);
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
            }
        ],
        details: [
            {
                url: '/stock-level/fetch-details',
                formSelector: '#stock_level_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('quantity').value = data.quantity || '';
                    document.getElementById('batch_number').value = data.batchNumber || '';
                    document.getElementById('cost_per_unit').value = data.costPerUnit || '';
                    document.getElementById('remarks').value = data.remarks || '';
                    document.getElementById('expiration_date').value = data.expirationDate || '';
                    document.getElementById('received_date').value = data.receivedDate || '';

                    await optionsPromise;

                    $('#product_id').val(data.productId).trigger('change');
                    $('#warehouse_id').val(data.warehouseId).trigger('change');
                },
            }
        ],
        delete: {
            trigger: '#delete-stock-level',
            url: '/stock-level/delete',
            swalTitle: 'Confirm Stock Level Deletion',
            swalText: 'Are you sure you want to delete this stock level?',
            confirmButtonText: 'Delete',
        },
        action: [
            {
                trigger: '#for-approval-stock-level',
                url: '/stock-level/for-approval',
                swalTitle: 'Confirm Stock Level Submission',
                confirmButtonClass : 'success',
                swalText: 'Are you sure you want to submit this stock level for approval?',
                confirmButtonText: 'Submit for Approval',
            },
            {
                trigger: '#set-to-draft-stock-level',
                url: '/stock-level/set-to-draft',
                swalTitle: 'Confirm Stock Level Set To Draft',
                swalText: 'Are you sure you want to set this stock level to draft?',
                confirmButtonText: 'Set to Draft',
            },
            {
                trigger: '#cancel-stock-level',
                url: '/stock-level/cancel',
                swalTitle: 'Confirm Stock Level Cancellation',
                swalText: 'Are you sure you want to cancel this stock level?',
                confirmButtonText: 'Cancel',
            },
            {
                trigger: '#approve-stock-level',
                url: '/stock-level/approve',
                swalTitle: 'Confirm Stock Level Approval',
                swalText: 'Are you sure you want to approve this stock level?',
                confirmButtonText: 'Approve',
            },
        ],
        dropdown: [
            { url: '/products/generate-product-stock-level-options', dropdownSelector: '#product_id' },
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
    config.action.map((cfg) => detailsActionButton(cfg));

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
