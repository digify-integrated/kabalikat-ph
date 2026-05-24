@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
        
        $approveShopOrderRequest = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(10, Auth::id());

        $shopOrderRequest = DB::table('shop_order_request')
            ->where('id', $detailsId)
            ->first();            
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                <!-- HEADER -->
                <div id="request_header"
                    class="card-header border-0 py-5">

                    <div class="d-flex flex-wrap align-items-center justify-content-between w-100">

                        <div>

                            <div class="d-flex align-items-center mb-2">

                                <h2 class="fw-bolder mb-0 me-3">
                                    Order Request
                                </h2>

                                <span id="request_status_badge"
                                    class="badge badge-light-warning fw-bold px-4 py-2">

                                    Pending

                                </span>

                            </div>

                            <div class="text-muted fs-7">
                                Review and approve/reject order request
                            </div>

                        </div>

                        <div class="text-end">

                            <div class="text-muted fs-8">
                                Reference Number
                            </div>

                            <div id="order_number"
                                class="fw-bolder fs-3 text-gray-800">

                                -

                            </div>

                        </div>

                    </div>

                </div>

                <div class="card-body p-7">

                    <!-- SUMMARY -->
                    <div class="row g-5 mb-7">

                        <!-- REQUEST TYPE -->
                        <div class="col-md-4">

                            <div class="bg-light rounded-4 p-5 h-100">

                                <div class="text-muted fs-8 mb-2">
                                    Request Type
                                </div>

                                <div class="d-flex align-items-center">

                                    <div class="symbol symbol-45px me-4">

                                        <div id="request_type_icon_bg"
                                            class="symbol-label bg-light-danger">

                                            <i id="request_type_icon"
                                                class="ki-outline ki-cross-circle fs-2 text-danger"></i>

                                        </div>

                                    </div>

                                    <div>

                                        <div id="request_type"
                                            class="fw-bold fs-3">

                                            -

                                        </div>

                                        <div id="request_type_description"
                                            class="text-muted fs-7">

                                            -

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- REQUESTED BY -->
                        <div class="col-md-4">

                            <div class="bg-light rounded-4 p-5 h-100">

                                <div class="text-muted fs-8 mb-2">
                                    Requested By
                                </div>

                                <div id="requested_by_name"
                                    class="fw-bolder fs-4 text-gray-800">

                                    -

                                </div>

                                <div id="requested_at"
                                    class="text-muted fs-7 mt-1">

                                    -

                                </div>

                            </div>

                        </div>

                        <!-- ORDER -->
                        <div class="col-md-4">

                            <div class="bg-light rounded-4 p-5 h-100">

                                <div class="text-muted fs-8 mb-2">
                                    Related Order
                                </div>

                                <div id="related_order_number"
                                    class="fw-bolder fs-4 text-primary">

                                    -

                                </div>

                                <div class="text-muted fs-7 mt-1">
                                    Linked sales transaction
                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- REQUEST REASON -->
                    <div class="mb-7">

                        <label class="fw-bold fs-6 mb-3 d-block">
                            Request Reason
                        </label>

                        <div class="bg-light rounded-4 p-5 border border-gray-200">

                            <div id="request_reason"
                                class="fs-6 text-gray-800 lh-lg">

                                -

                            </div>

                        </div>

                    </div>

                    @if(($approveShopOrderRequest ?? false) === true  && $shopOrderRequest->request_status === 'Pending')
                        <!-- APPROVAL ACTIONS -->
                        <div id="approval_actions_container"
                            class="row g-6 d-none">

                            <!-- APPROVE -->
                            <div class="col-lg-4">

                                <div class="card border border-success border-dashed rounded-4 h-100">

                                    <div class="card-body p-6">

                                        <div class="d-flex align-items-center mb-5">

                                            <div class="symbol symbol-50px me-4">

                                                <div class="symbol-label bg-light-success">

                                                    <i class="ki-outline ki-check fs-2x text-success"></i>

                                                </div>

                                            </div>

                                            <div>

                                                <h3 class="fw-bold mb-1">
                                                    Approve Request
                                                </h3>

                                                <div class="text-muted fs-7">
                                                    Confirm and continue processing
                                                </div>

                                            </div>

                                        </div>

                                        <textarea id="approval_remarks"
                                            class="form-control form-control-solid mb-5"
                                            rows="4"
                                            placeholder="Optional approval remarks..."></textarea>

                                        <button type="button"
                                            id="approve_request_btn"
                                            class="btn btn-success fw-bold w-100 py-3 rounded-3">

                                            Approve Request

                                        </button>

                                    </div>

                                </div>

                            </div>

                            <!-- REJECT -->
                            <div class="col-lg-4">

                                <div class="card border border-danger border-dashed rounded-4 h-100">

                                    <div class="card-body p-6">

                                        <div class="d-flex align-items-center mb-5">

                                            <div class="symbol symbol-50px me-4">

                                                <div class="symbol-label bg-light-danger">

                                                    <i class="ki-outline ki-cross-circle fs-2x text-danger"></i>

                                                </div>

                                            </div>

                                            <div>

                                                <h3 class="fw-bold mb-1">
                                                    Reject Request
                                                </h3>

                                                <div class="text-muted fs-7">
                                                    Deny request with explanation
                                                </div>

                                            </div>

                                        </div>

                                        <textarea id="rejection_reason"
                                            class="form-control form-control-solid mb-5"
                                            rows="4"
                                            placeholder="Enter rejection reason..."></textarea>

                                        <button type="button"
                                            id="reject_request_btn"
                                            class="btn btn-danger fw-bold w-100 py-3 rounded-3">

                                            Reject Request

                                        </button>

                                    </div>

                                </div>

                            </div>

                            <!-- CANCEL -->
                            <div class="col-lg-4">

                                <div class="card border border-warning border-dashed rounded-4 h-100">

                                    <div class="card-body p-6">

                                        <div class="d-flex align-items-center mb-5">

                                            <div class="symbol symbol-50px me-4">

                                                <div class="symbol-label bg-light-warning">

                                                    <i class="ki-outline ki-cross fs-2x text-warning"></i>

                                                </div>

                                            </div>

                                            <div>

                                                <h3 class="fw-bold mb-1">
                                                    Cancel Request
                                                </h3>

                                                <div class="text-muted fs-7">
                                                    Cancel request with explanation
                                                </div>

                                            </div>

                                        </div>

                                        <textarea id="cancellation_reason"
                                            class="form-control form-control-solid mb-5"
                                            rows="4"
                                            placeholder="Enter cancellation reason..."></textarea>

                                        <button type="button"
                                            id="cancel_request_btn"
                                            class="btn btn-warning fw-bold w-100 py-3 rounded-3">

                                            Cancel Request

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>
                    @endif

                    <!-- DECISION SUMMARY -->
                    <div id="decision_summary_container"
                        class="mt-8 d-none">

                        <h3 class="fw-bold mb-5">
                            Decision Summary
                        </h3>

                        <div id="decision_summary_card"></div>

                    </div>

                </div>

            </div>
        </div>
    </div>

    @include('partials.log-notes-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>

    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush