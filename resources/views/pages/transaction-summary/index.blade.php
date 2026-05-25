@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                @include('partials.datatable-search')
            </div>

            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">

                    @if(($deletePermission ?? 0) > 0 || ($exportPermission ?? 0) > 0)
                        <a href="#"
                        class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown action-dropdown me-3 d-none"
                        data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>

                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                            data-kt-menu="true">

                            @if(($exportPermission ?? 0) > 0)
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);"
                                    class="menu-link px-3"
                                    id="export-data"
                                    data-bs-toggle="modal"
                                    data-bs-target="#export-modal">
                                        Export
                                    </a>
                                </div>
                            @endif

                            @if(($deletePermission ?? 0) > 0)
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);"
                                    class="menu-link px-3"
                                    id="delete-data">
                                        Delete
                                    </a>
                                </div>
                            @endif

                        </div>
                    @endif
                    
                    @include('partials.datatable-buttons')
                </div>
                <div>
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"><i class="ki-outline ki-filter fs-2"></i> Filter</button>
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-5">
                            <div class="mb-5">
                                <label class="form-label fw-semibold">
                                    Order Type
                                </label>

                                <select id="filter_order_type"
                                        class="form-select"
                                        multiple
                                        data-control="select2">

                                    <option value="Walk-in">Walk-in</option>
                                    <option value="Dine-in">Dine-in</option>
                                    <option value="Take-out">Take-out</option>
                                    <option value="Delivery">Delivery</option>
                                </select>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-semibold">
                                    Order Status
                                </label>

                                <select id="filter_order_status"
                                        class="form-select"
                                        multiple
                                        data-control="select2">

                                    <option value="Pending">Pending</option>
                                    <option value="Preparing">Preparing</option>
                                    <option value="Ready">Ready</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Voided">Voided</option>
                                </select>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-semibold">
                                    Payment Status
                                </label>

                                <select id="filter_payment_status"
                                        class="form-select"
                                        multiple
                                        data-control="select2">

                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Refunded">Refunded</option>
                                </select>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-semibold">
                                    Register
                                </label>

                                <select id="filter_register"
                                        class="form-select"
                                        multiple
                                        data-control="select2">
                                </select>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-semibold">
                                    Cashier
                                </label>

                                <select id="filter_cashier"
                                        class="form-select"
                                        multiple
                                        data-control="select2">
                                </select>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6" id="reset-filter" data-kt-menu-dismiss="true">Reset</button>
                                <button type="button" class="btn btn-primary fw-semibold px-6" id="apply-filter" data-kt-menu-dismiss="true">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body pt-9">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed text-wrap fs-6 gy-5"
                    id="transaction-summary-table">
                    <thead>
                        <tr>
                            <th class="min-w-200px">Order No</th>
                            <th class="min-w-150px">Register</th>
                            <th class="min-w-150px">Cashier</th>
                            <th class="min-w-150px">Order Type</th>
                            <th class="min-w-150px">Order Status</th>
                            <th class="min-w-150px">Payment Status</th>
                            <th class="min-w-100px">Items</th>
                            <th class="min-w-100px">Gross</th>
                            <th class="min-w-100px">Discount</th>
                            <th class="min-w-100px">Charges</th>
                            <th class="min-w-100px">Net</th>
                            <th class="min-w-100px">VAT</th>
                            <th class="min-w-100px">VATable</th>
                            <th class="min-w-150px">VAT Exempt</th>
                            <th class="min-w-150px">Zero Rated</th>
                            <th class="min-w-100px">Paid</th>
                            <th class="min-w-100px">Change</th>
                            <th class="min-w-100px">Balance</th>
                            <th class="min-w-200px">Transaction Date</th>
                        </tr>
                        </thead>

                    <tbody class="fw-semibold text-gray-800"></tbody>
                </table>
            </div>
        </div>
    </div>

    @if(($exportPermission ?? 0) > 0)
        @include('partials.export-modal')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>

    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush

