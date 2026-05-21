@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;

        $shopRegister = DB::table('shop_register')
            ->where('id', $detailsId)
            ->first();
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="row mb-5">
                <div class="col">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" class="form-control form-control-solid w-100 ps-12" id="product_search" placeholder="Search products, SKU, barcode...">
                    </div>
                </div>
            </div>

            <div class="mb-4 overflow-auto">
                <div class="d-flex flex-nowrap gap-2 pb-1" id="shop-product-category-container"></div>
            </div>

            <div class="row g-3" id="product-container"></div>
        </div>
    
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-body">

            <!-- HEADER -->
            <div class="card-header border-0 pt-6 px-6">
                <div class="d-flex flex-column">
                    <h2 class="fw-bold mb-1 text-primary" id="order-details-title">
                        <i class="ki-duotone text-primary ki-handcart fs-3 me-2"></i>
                        Cart
                    </h2>
                    <div class="text-muted fw-semibold">
                        Order ID # <span id="order-id">--</span>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="card-toolbar register-action d-none">
                    <button
                        class="btn btn-icon btn-light btn-sm"
                        data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">

                        <i class="ki-outline ki-dots-vertical fs-2"></i>
                    </button>

                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                        data-kt-menu="true">

                        <div class="menu-item px-3">
                            <a href="javascript:void(0)"
                                class="menu-link px-3 text-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#cancel-order-modal">
                                Cancel Order
                            </a>
                        </div>

                        <div class="menu-item px-3">
                            <a href="javascript:void(0)"
                                class="menu-link px-3" id="new-order">
                                New Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BODY -->
            <div class="card-body px-6 pb-6">

                <!-- ORDER SETTINGS -->
                <div class="row g-3 mb-5 register-action d-none">
                    <div class="col">
                        <select
                            class="form-select form-select-solid fw-semibold"
                            id="order-type">

                            <option value="Walk-in">Walk-in</option>
                            @if($shopRegister->is_restaurant === 'Yes')
                                <option value="Dine-in">Dine-in</option>
                                <option value="Take-out">Take-out</option>
                            @endif
                            <option value="Delivery">Delivery</option>

                        </select>
                    </div>
                    @if($shopRegister->is_restaurant === 'Yes')
                        <div class="col d-none" id="set-table-column">
                            <button class="btn btn-light-success w-100 fw-bold" id="set-table" data-bs-toggle="modal" data-bs-target="#table-modal">
                                Table
                            </button>
                        </div>
                    @endif
                    <div class="col">
                       <button
                            class="btn btn-light-warning w-100 fw-bold"
                            data-bs-toggle="modal"
                            data-bs-target="#customer-modal">
                            Customer
                        </button>
                    </div>
                </div>

                <!-- ORDER BADGES -->
                <div class="d-flex flex-wrap gap-2 mb-5 register-action d-none">
                    @if($shopRegister->is_restaurant === 'Yes')
                        <span
                            class="badge badge-light-primary fw-bold"
                            id="badge-table">
                            No Table
                        </span>
                    @endif

                    <span
                        class="badge badge-light-warning fw-bold"
                        id="badge-payment-status">

                        Unpaid

                    </span>

                </div>

                <!-- LOADING -->
                <div
                    id="shop-order-loading"
                    class="d-none text-center py-10">

                    <div class="spinner-border text-primary mb-3"></div>

                    <div class="fw-semibold text-muted">
                        Loading cart...
                    </div>

                </div>

                <!-- EMPTY -->
                <div
                    id="shop-order-empty"
                    class="text-center py-10">

                    <div class="mb-4">
                        <i class="ki-duotone ki-handcart fs-5x text-muted">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>

                    <div class="fw-bold fs-3 text-gray-700 mb-2">
                        Empty Cart
                    </div>

                    <div class="text-muted fw-semibold">
                        Add products to start a new order.
                    </div>

                </div>

                <!-- ORDER ITEMS -->
                <div
                    class="pe-2 mb-5 d-none"
                    style="max-height: 420px; overflow-y: auto;"
                    id="shop-order-list">

                </div>

                <!-- SUMMARY -->
                <div
                    class="card border-0 bg-light mb-5 d-none"
                    id="shop-order-summary-card">

                    <div class="card-body p-5">

                        <div id="order-summary-list">

                            <!-- Dynamic summary -->

                        </div>

                    </div>

                </div>

                <!-- SECONDARY ACTIONS -->
                <div class="row g-3 mb-3 register-action d-none">

                    <div class="col">
                        <button class="btn btn-light w-100 py-3 fw-semibold" id="print-bill">
                            Print Bill
                        </button>
                    </div>

                    <div class="col">
                        <button
                            class="btn btn-light-success w-100 py-3 fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#discount-modal"
                             id="manage-discount-button">

                            Discounts

                        </button>
                    </div>

                    <div class="col">
                        <button
                            class="btn btn-light-danger w-100 py-3 fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#charges-modal"
                            id="manage-charge-button">

                            Charges

                        </button>
                    </div>

                </div>

                <!-- PRIMARY ACTIONS -->
                <div class="row g-3 register-action d-none">
                    @if($shopRegister->is_restaurant === 'Yes')
                        <div class="col">
                            <button
                                class="btn btn-warning w-100 py-4 fw-bold fs-5">

                                Send To Kitchen

                            </button>
                        </div>
                    @endif
                    

                    <div class="col">
                        <button
                            class="btn btn-success w-100 py-4 fw-bold fs-5"
                            data-bs-toggle="modal"
                            data-bs-target="#payment-modal"
                            id="manage-payment-button">

                            Payment

                        </button>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div id="shop-register-order-modal" class="modal fade" tabindex="-1" aria-labelledby="shop-register-order-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content rounded-4 shadow-lg border-0">

                <!-- HEADER -->
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h4 class="modal-title fw-bold mb-1">Add to Order</h4>
                        <div class="text-muted fs-7">
                            Select quantity and add note
                        </div>
                    </div>

                    <button type="button"
                            class="btn btn-icon btn-sm btn-light"
                            data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-2"></i>
                    </button>
                </div>

                <form id="product_form" method="post" action="#">

                    @csrf

                    <input type="hidden" id="modal_product_id" name="modal_product_id">
                    <input type="hidden" id="modal-product-base-price">

                    <div class="modal-body pt-4">

                        <!-- PRODUCT INFO CARD -->
                        <div class="card border-0 bg-light mb-4 rounded-3">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted fs-8">Product</div>
                                        <div class="fw-bold fs-6" id="modal-product-name">
                                            —
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <div class="text-muted fs-8">Price</div>
                                        <div class="fw-bold text-primary fs-5" id="modal-product-price">
                                            ₱0.00
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- QUANTITY CONTROL (UNCHANGED AS REQUESTED) -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold fs-7 text-muted">
                                Quantity
                            </label>

                            <div class="row align-items-center g-2">
                                <div class="col-8 col-md">
                                    <div class="position-relative d-flex align-items-center order-quantity flex-wrap flex-sm-nowrap gap-2"
                                        data-kt-dialer="true"
                                        data-kt-dialer-min="1"
                                        data-kt-dialer-step="1"
                                        data-kt-dialer-decimals="0">

                                        <button type="button"
                                                class="btn btn-icon btn-sm btn-light"
                                                data-kt-dialer-control="decrease">
                                            <i class="ki-outline ki-minus fs-2"></i>
                                        </button>

                                        <input type="text"
                                            class="form-control text-center fw-bold fs-7 border-0 bg-light rounded w-100"
                                            data-kt-dialer-control="input"
                                            id="order_qty_input"
                                            name="order_qty_input"
                                            value="1"
                                            readonly />

                                        <button type="button"
                                                class="btn btn-icon btn-sm btn-light"
                                                data-kt-dialer-control="increase">
                                            <i class="ki-outline ki-plus fs-2"></i>
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ORDER NOTE (NEW) -->
                        <div class="mb-3">
                            <label for="order_note" class="form-label fw-semibold fs-7 text-muted">
                                Order Note
                            </label>

                            <textarea id="order_note"
                                    name="order_note"
                                    class="form-control form-control-solid"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="e.g. No onions, extra crispy, add sauce..."></textarea>

                            <div class="form-text fs-8 text-muted">
                                Optional instructions for kitchen or preparation
                            </div>
                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="modal-footer border-0 pt-0">

                        <button type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit"
                                class="btn btn-primary fw-bold"
                                id="submit-product">
                            Add to Order
                        </button>

                    </div>

                </form>
            </div>
        </div>
    </div>

    <div
        id="table-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="table-modal"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-xl">

            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <!-- HEADER -->
                <div class="modal-header border-0 px-6 py-5 bg-light">

                    <div>

                        <div class="d-flex align-items-center gap-2 mb-1">

                            <div
                                class="symbol symbol-40px bg-success bg-opacity-10">

                                <span class="symbol-label">

                                    <i class="ki-duotone ki-element-11 fs-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>

                                </span>

                            </div>

                            <div>

                                <h2 class="fw-bold text-gray-900 mb-0">
                                    Select Table
                                </h2>

                                <div class="text-muted fs-7 fw-semibold">
                                    Assign this order to a dining table
                                </div>

                            </div>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn btn-icon btn-sm btn-light rounded-circle"
                        data-bs-dismiss="modal">

                        <i class="ki-outline ki-cross fs-2"></i>

                    </button>

                </div>

                <!-- BODY -->
                <div class="modal-body px-6 py-5">

                    <!-- TOP BAR -->
                    <div
                        class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-6">

                        <!-- FLOOR FILTERS -->
                        <div
                            class="d-flex flex-wrap gap-3"
                            id="shop-floor-plan-container">

                        </div>

                        <!-- LEGEND -->
                        <div class="d-flex flex-wrap align-items-center gap-5">

                            <div class="d-flex align-items-center gap-2">

                                <span
                                    class="w-12px h-12px rounded-circle bg-success">
                                </span>

                                <span class="fs-7 fw-semibold text-muted">
                                    Selected
                                </span>

                            </div>

                            <div class="d-flex align-items-center gap-2">

                                <span
                                    class="w-12px h-12px rounded-circle border border-gray-400 bg-white">
                                </span>

                                <span class="fs-7 fw-semibold text-muted">
                                    Available
                                </span>

                            </div>

                            <div class="d-flex align-items-center gap-2">

                                <span
                                    class="w-12px h-12px rounded-circle bg-light-secondary">
                                </span>

                                <span class="fs-7 fw-semibold text-muted">
                                    Occupied
                                </span>

                            </div>

                        </div>

                    </div>

                    <!-- TABLE GRID -->
                    <div
                        class="row g-4"
                        id="shop-floor-table-container">

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="discount-modal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">

                <!-- HEADER -->
                <div class="modal-header border-0 bg-light px-6 py-5">

                    <div>

                        <h2 class="fw-bold mb-1">
                            Manage Discounts
                        </h2>

                        <div class="text-muted fw-semibold fs-7">
                            Apply or remove discounts for this order
                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn btn-icon btn-sm btn-light rounded-circle"
                        data-bs-dismiss="modal">

                        <i class="ki-outline ki-cross fs-2"></i>

                    </button>

                </div>

                <!-- BODY -->
                <div class="modal-body px-6 py-5">

                    <!-- AVAILABLE DISCOUNTS -->
                    <div class="mb-6">

                        <label class="fw-bold fs-6 mb-3 d-block">
                            Available Discounts
                        </label>

                        <div
                            id="available-discount-list"
                            class="d-flex flex-column gap-3">

                        </div>

                    </div>

                    <!-- APPLIED -->
                    <div>

                        <label class="fw-bold fs-6 mb-3 d-block">
                            Applied Discounts
                        </label>

                        <div
                            id="applied-discount-list"
                            class="d-flex flex-column gap-3">

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="charges-modal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">

                <!-- HEADER -->
                <div class="modal-header border-0 bg-light px-6 py-5">

                    <div>

                        <h2 class="fw-bold mb-1">
                            Manage Charges
                        </h2>

                        <div class="text-muted fw-semibold fs-7">
                            Apply or remove charges for this order
                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn btn-icon btn-sm btn-light rounded-circle"
                        data-bs-dismiss="modal">

                        <i class="ki-outline ki-cross fs-2"></i>

                    </button>

                </div>

                <!-- BODY -->
                <div class="modal-body px-6 py-5">

                    <!-- AVAILABLE -->
                    <div class="mb-6">

                        <label class="fw-bold fs-6 mb-3 d-block">
                            Available Charges
                        </label>

                        <div
                            id="available-charge-list"
                            class="d-flex flex-column gap-3">

                        </div>

                    </div>

                    <!-- APPLIED -->
                    <div>

                        <label class="fw-bold fs-6 mb-3 d-block">
                            Applied Charges
                        </label>

                        <div
                            id="applied-charge-list"
                            class="d-flex flex-column gap-3">

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="payment-modal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <!-- HEADER -->
                <div class="modal-header border-0 bg-success text-white py-3">

                    <div>
                        <h5 class="modal-title fw-bold mb-0">
                            Complete Payment
                        </h5>

                        <div class="small opacity-75">
                            Split payments supported
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <!-- BODY -->
                <div class="modal-body p-4">

                    <!-- ORDER SUMMARY -->
                    <div class="card border-0 bg-light rounded-4 mb-4">

                        <div class="card-body p-4">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <div class="text-muted small">
                                        Outstanding Balance
                                    </div>

                                    <div
                                        class="fw-bold fs-1 text-success"
                                        id="payment-balance-display">

                                        ₱ 0.00

                                    </div>

                                </div>

                                <div class="text-end">

                                    <div class="small text-muted">
                                        Order No.
                                    </div>

                                    <div
                                        class="fw-semibold"
                                        id="payment-order-number">
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- PAYMENT METHODS -->
                    <div id="payment-method-list">

                        <!-- Dynamic -->

                    </div>

                    <!-- TOTAL SECTION -->
                    <div class="card border-0 bg-light rounded-4 mt-4">

                        <div class="card-body p-4">

                            <div class="d-flex justify-content-between mb-2">

                                <span class="text-muted">
                                    Total Payment
                                </span>

                                <span
                                    class="fw-bold"
                                    id="total-payment-display">

                                    ₱ 0.00

                                </span>

                            </div>

                            <div class="d-flex justify-content-between mb-2">

                                <span class="text-muted">
                                    Change
                                </span>

                                <span
                                    class="fw-bold text-primary"
                                    id="payment-change-display">

                                    ₱ 0.00

                                </span>

                            </div>

                            <div
                                class="alert alert-danger py-2 px-3 mb-0 d-none"
                                id="payment-validation-message">
                            </div>

                        </div>

                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer border-0 p-4">

                    <button
                        type="button"
                        class="btn btn-light px-4"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button
                        type="button"
                        class="btn btn-success px-5 fw-bold"
                        id="complete-payment-button">

                        Complete Payment

                    </button>

                </div>

            </div>

        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>
    
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush