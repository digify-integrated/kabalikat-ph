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
                selector: '#stock_transfer_form',
                rules: {
                    rules: {
                        stock_level_from_id: { required: true},
                        stock_level_to_id: { required: true},
                        quantity: { required: true},
                        stock_transfer_reason_id: { required: true},
                    },
                    messages: {
                        stock_level_from_id: { required: 'Choose the stock' },
                        stock_level_to_id: { required: 'Choose the stock' },
                        quantity: { required: 'Enter the quantity' },
                        stock_transfer_reason_id: { required: 'Choose the transfer reason' },
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
            }
        ],
        details: [
            {
                url: '/stock-transfer/fetch-details',
                formSelector: '#stock_transfer_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('quantity').value = data.quantity || '0';
                    document.getElementById('remarks').value = data.remarks || '';

                    await optionsPromise;

                    $('#stock_level_from_id').val(data.stockLevelFromId).trigger('change');
                    $('#stock_level_to_id').val(data.stockLevelToId).trigger('change');
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
        dropdown: [
            { url: '/stock-level/generate-options', dropdownSelector: '#stock_level_from_id' },
            { url: '/stock-level/generate-options', dropdownSelector: '#stock_level_to_id' },
            { url: '/stock-transfer-reason/generate-options', dropdownSelector: '#stock_transfer_reason_id' },
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
    config.action.map((cfg) => detailsActionButton(cfg));

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);
});
