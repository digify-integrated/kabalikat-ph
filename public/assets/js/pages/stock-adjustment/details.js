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
                selector: '#stock_adjustment_form',
                rules: {
                    rules: {
                        stock_level_id: { required: true},
                        adjustment_type: { required: true},
                        quantity: { required: true},
                        stock_adjustment_reason_id: { required: true},
                    },
                    messages: {
                        stock_level_id: { required: 'Choose the stock' },
                        adjustment_type: { required: 'Choose the adjustment type' },
                        quantity: { required: 'Enter the quantity' },
                        stock_adjustment_reason_id: { required: 'Choose the adjustment reason' },
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
            }
        ],
        details: [
            {
                url: '/stock-adjustment/fetch-details',
                formSelector: '#stock_adjustment_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    document.getElementById('quantity').value = data.quantity || '';
                    document.getElementById('remarks').value = data.remarks || '';

                    await optionsPromise;

                    $('#stock_level_id').val(data.stockLevelId).trigger('change');
                    $('#adjustment_type').val(data.adjustmentType).trigger('change');
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
        dropdown: [
            { url: '/stock-level/generate-options', dropdownSelector: '#stock_level_id' },
            { url: '/stock-adjustment-reason/generate-options', dropdownSelector: '#stock_adjustment_reason_id' },
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
