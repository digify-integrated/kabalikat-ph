import { initValidation } from '../../util/validation.js';
import { showNotification, setNotification } from '../../util/notifications.js';
import { attachLogNotesHandler } from '../../util/log-notes.js';
import { disableButton, enableButton, detailsDeleteButton } from '../../form/button.js';
import { displayDetails, handleActionFetch, getPageContext, getCsrfToken, resetForm } from '../../form/form.js';
import { handleSystemError } from '../../util/system-errors.js';

document.addEventListener('DOMContentLoaded', () => {
    const ctx = getPageContext();

    const fillShopOrderRequestDetails = (data) => {

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        $('#order_number').text(data.orderNumber || '-');

        /*
        |--------------------------------------------------------------------------
        | STATUS BADGE
        |--------------------------------------------------------------------------
        */

        const statusBadge = $('#request_status_badge');

        statusBadge.text(data.requestStatus || 'Pending');

        statusBadge.removeClass(
            'badge-light-warning badge-light-success badge-light-danger badge-light-warning'
        );

        switch (data.requestStatus) {

            case 'Approved':
                statusBadge.addClass('badge-light-success');
                break;

            case 'Rejected':
                statusBadge.addClass('badge-light-danger');
                break;

            case 'Cancelled':
                statusBadge.addClass('badge-light-warning');
                break;

            default:
                statusBadge.addClass('badge-light-warning');
                break;

        }

        /*
        |--------------------------------------------------------------------------
        | REQUEST TYPE
        |--------------------------------------------------------------------------
        */

        $('#request_type').text(data.requestType || '-');

        if (data.requestType === 'Void') {

            $('#request_type_description').text(
                'Transaction cancellation request'
            );

            $('#request_type_icon_bg')
                .removeClass()
                .addClass('symbol-label bg-light-danger');

            $('#request_type')
                .removeClass()
                .addClass('fw-bold fs-3 text-danger');

            $('#request_type_icon')
                .removeClass()
                .addClass('ki-outline ki-cross-circle fs-2 text-danger');

        }
        else if (data.requestType === 'Refund') {

            $('#request_type_description').text(
                'Customer refund request'
            );

            $('#request_type_icon_bg')
                .removeClass()
                .addClass('symbol-label bg-light-warning');

            $('#request_type')
                .removeClass()
                .addClass('fw-bold fs-3 text-warning');

            $('#request_type_icon')
                .removeClass()
                .addClass('ki-outline ki-wallet fs-2 text-warning');

        }

        /*
        |--------------------------------------------------------------------------
        | REQUEST DETAILS
        |--------------------------------------------------------------------------
        */

        $('#requested_by_name').text(data.requestedByName || '-');
        $('#requested_at').text(data.requestedAt || '-');

        $('#related_order_number').text(data.orderNumber || '-');

        $('#request_reason').text(data.requestReason || '-');

        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */

        if (data.requestStatus === 'Pending') {

            $('#approval_actions_container').removeClass('d-none');

            $('#decision_summary_container').addClass('d-none');

        }
        else {

            $('#approval_actions_container').addClass('d-none');

            $('#decision_summary_container').removeClass('d-none');

        }

        /*
        |--------------------------------------------------------------------------
        | DECISION SUMMARY
        |--------------------------------------------------------------------------
        */

        let summaryHtml = '';

        if (data.requestStatus === 'Approved') {

            summaryHtml = `
                <div class="card border border-success border-dashed rounded-4 overflow-hidden">

                    <div class="bg-light-success px-6 py-5 border-bottom border-success border-dashed">

                        <div class="d-flex align-items-center justify-content-between flex-wrap">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-50px me-4">

                                    <div class="symbol-label bg-success">

                                        <i class="ki-outline ki-check fs-2x text-white"></i>

                                    </div>

                                </div>

                                <div>

                                    <h3 class="fw-bolder text-success mb-1">
                                        Request Approved
                                    </h3>

                                    <div class="text-muted fs-7">
                                        This request has been approved and finalized
                                    </div>

                                </div>

                            </div>

                            <div class="text-end">

                                <div class="text-muted fs-8">
                                    Approved By
                                </div>

                                <div class="fw-bolder fs-5 text-gray-800">
                                    ${data.approvedByName || '-'}
                                </div>

                                <div class="text-muted fs-8">
                                    ${data.approvedAt || '-'}
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="card-body p-6">

                        <label class="fw-bold fs-6 text-gray-700 mb-3 d-block">
                            Approval Remarks
                        </label>

                        <div class="bg-light-success rounded-4 p-5 border border-success border-dashed">

                            <div class="fs-6 text-gray-800 lh-lg">
                                ${data.approvalRemarks || 'No remarks provided.'}
                            </div>

                        </div>

                    </div>

                </div>
            `;
        }

        else if (data.requestStatus === 'Rejected') {

            summaryHtml = `
                <div class="card border border-danger border-dashed rounded-4 overflow-hidden">

                    <div class="bg-light-danger px-6 py-5 border-bottom border-danger border-dashed">

                        <div class="d-flex align-items-center justify-content-between flex-wrap">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-50px me-4">

                                    <div class="symbol-label bg-danger">

                                        <i class="ki-outline ki-cross-circle fs-2x text-white"></i>

                                    </div>

                                </div>

                                <div>

                                    <h3 class="fw-bolder text-danger mb-1">
                                        Request Rejected
                                    </h3>

                                </div>

                            </div>

                            <div class="text-end">

                                <div class="fw-bolder fs-5 text-gray-800">
                                    ${data.rejectedByName || '-'}
                                </div>

                                <div class="text-muted fs-8">
                                    ${data.rejectedAt || '-'}
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="card-body p-6">

                        <label class="fw-bold fs-6 text-gray-700 mb-3 d-block">
                            Rejection Reason
                        </label>

                        <div class="bg-light-danger rounded-4 p-5 border border-danger border-dashed">

                            <div class="fs-6 text-gray-800 lh-lg">
                                ${data.rejectionReason || 'No rejection reason provided.'}
                            </div>

                        </div>

                    </div>

                </div>
            `;
        }

        else if (data.requestStatus === 'Cancelled') {

            summaryHtml = `
                <div class="card border border-warning border-dashed rounded-4 overflow-hidden">

                    <div class="bg-light px-6 py-5 border-bottom border-warning border-dashed">

                        <div class="d-flex align-items-center justify-content-between flex-wrap">

                            <div class="d-flex align-items-center">

                                <div class="symbol symbol-50px me-4">

                                    <div class="symbol-label bg-warning">

                                        <i class="ki-outline ki-cross fs-2x text-white"></i>

                                    </div>

                                </div>

                                <div>

                                    <h3 class="fw-bolder text-gray-800 mb-1">
                                        Request Cancelled
                                    </h3>

                                </div>

                            </div>

                            <div class="text-end">

                                <div class="fw-bolder fs-5 text-gray-800">
                                    ${data.cancelledByName || '-'}
                                </div>

                                <div class="text-muted fs-8">
                                    ${data.cancelledAt || '-'}
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="card-body p-6">

                        <label class="fw-bold fs-6 text-gray-700 mb-3 d-block">
                            Cancellation Remarks
                        </label>

                        <div class="bg-light rounded-4 p-5 border border-warning border-dashed">

                            <div class="fs-6 text-gray-800 lh-lg">
                                ${data.cancellationReason || 'No remarks provided.'}
                            </div>

                        </div>

                    </div>

                </div>
            `;
        }

        $('#decision_summary_card').html(summaryHtml);

    };

    const config = {
        details: [
            {
                url: '/shop-order-request/fetch-details',
                formSelector: '#shop_order_request_form',
                busyHideTargets: ['#submit-data'],
                onSuccess: async (data) => {
                    fillShopOrderRequestDetails(data);
                },
            }
        ],
        delete: {
            trigger: '#delete-shop-order-request',
            url: '/shop-order-request/delete',
            swalTitle: 'Confirm Shop Order Request Deletion',
            swalText: 'Are you sure you want to delete this shop order request?',
            confirmButtonText: 'Delete',
        },
    };

    (async () => {
        try {
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

    attachLogNotesHandler();

    detailsDeleteButton(config.delete);

    /*
    |--------------------------------------------------------------------------
    | APPROVE REQUEST
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '#approve_request_btn',
        async function () {

            try {
                const remarks =
                    $('#approval_remarks').val().trim();

                disableButton('approve_request_btn');

                const csrf = getCsrfToken();

                const formData = new URLSearchParams();

                formData.append(
                    'shop_order_request_id',
                    ctx.detailId
                );

                formData.append(
                    'approval_remarks',
                    remarks
                );

                const response = await fetch(
                    '/shop-order-request/approve',
                    {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',

                            Accept: 'application/json',

                            ...(csrf
                                ? { 'X-CSRF-TOKEN': csrf }
                                : {}),
                        },
                    }
                );

                const data = await response.json();

                enableButton('approve_request_btn');

                if (!data.success) {

                    showNotification(data.message);

                    return;
                }

                setNotification(data.message);

                /*
                |--------------------------------------------------------------------------
                | OPTIONAL UI REFRESH
                |--------------------------------------------------------------------------
                */
                location.reload();

            } catch (error) {

                enableButton('approve_request_btn');

                handleSystemError(
                    error,
                    'approve_request_failed',
                    error.message
                );
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | REJECT REQUEST
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '#reject_request_btn',
        async function () {

            try {
                const reason =
                    $('#rejection_reason').val().trim();

                /*
                |--------------------------------------------------------------------------
                | VALIDATION
                |--------------------------------------------------------------------------
                */
                if (!reason) {

                    showNotification(
                        'Rejection reason is required.'
                    );

                    $('#rejection_reason').focus();

                    return;
                }

                disableButton('reject_request_btn');

                const csrf = getCsrfToken();

                const formData = new URLSearchParams();

                formData.append(
                    'shop_order_request_id',
                    ctx.detailId
                );

                formData.append(
                    'rejection_reason',
                    reason
                );

                const response = await fetch(
                    '/shop-order-request/reject',
                    {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',

                            Accept: 'application/json',

                            ...(csrf
                                ? { 'X-CSRF-TOKEN': csrf }
                                : {}),
                        },
                    }
                );

                const data = await response.json();

                enableButton('reject_request_btn');

                if (!data.success) {

                    showNotification(data.message, 'danger');

                    return;
                }

                setNotification(data.message, 'success');

                /*
                |--------------------------------------------------------------------------
                | OPTIONAL UI REFRESH
                |--------------------------------------------------------------------------
                */
                location.reload();

            } catch (error) {

                enableButton('reject_request_btn');

                handleSystemError(
                    error,
                    'reject_request_failed',
                    error.message
                );
            }
        }
    );

    /*
    |--------------------------------------------------------------------------
    | CANCEL REQUEST
    |--------------------------------------------------------------------------
    */
    $(document).on(
        'click',
        '#cancel_request_btn',
        async function () {

            try {
                const reason =
                    $('#cancellation_reason').val().trim();

                /*
                |--------------------------------------------------------------------------
                | VALIDATION
                |--------------------------------------------------------------------------
                */
                if (!reason) {

                    showNotification(
                        'Cancellation reason is required.'
                    );

                    $('#cancellation_reason').focus();

                    return;
                }

                disableButton('cancel_request_btn');

                const csrf = getCsrfToken();

                const formData = new URLSearchParams();

                formData.append(
                    'shop_order_request_id',
                    ctx.detailId
                );

                formData.append(
                    'cancellation_reason',
                    reason
                );

                const response = await fetch(
                    '/shop-order-request/cancel',
                    {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded; charset=UTF-8',

                            Accept: 'application/json',

                            ...(csrf
                                ? { 'X-CSRF-TOKEN': csrf }
                                : {}),
                        },
                    }
                );

                const data = await response.json();

                enableButton('cancel_request_btn');

                if (!data.success) {

                    showNotification(data.message, 'danger');

                    return;
                }

                setNotification(data.message, 'success');

                /*
                |--------------------------------------------------------------------------
                | OPTIONAL UI REFRESH
                |--------------------------------------------------------------------------
                */
                location.reload();

            } catch (error) {

                enableButton('cancel_request_btn');

                handleSystemError(
                    error,
                    'cancel_request_failed',
                    error.message
                );
            }
        }
    );
});
