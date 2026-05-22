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

            <div class="card border-0 shadow-sm h-100 overflow-hidden">

                <!-- ===================================================== -->
                <!-- STICKY TOP HEADER -->
                <!-- ===================================================== -->
                <div class="border-bottom px-5 py-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <!-- LEFT -->
                        <div>

                            <div class="d-flex align-items-center gap-2 mb-1">

                                <div class="symbol symbol-40px">

                                    <div class="symbol-label">

                                        <i class="ki-duotone ki-handcart fs-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>

                                    </div>

                                </div>

                                <div>

                                    <div class="fw-bold fs-3 text-gray-900">
                                        Cart
                                    </div>

                                    <div class="text-muted fw-semibold small">
                                        Order No: <span class="text-danger" id="order-id">No Active Order</span>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="dropdown always-visible-action">

                            <button
                                class="btn btn-icon btn-light btn-sm"
                                data-bs-toggle="dropdown">

                                <i class="ki-outline ki-dots-vertical fs-2"></i>

                            </button>

                            <div class="dropdown-menu dropdown-menu-end rounded-4 shadow-sm border-0 p-2 w-225px">

                                <button
                                    class="dropdown-item rounded-3 py-3 fw-semibold"
                                    id="new-order">

                                    <i class="ki-outline ki-plus fs-4 me-2 text-primary"></i>
                                    New Order

                                </button>

                                <button
                                    class="dropdown-item rounded-3 py-3 fw-semibold"
                                    data-bs-toggle="modal"
                                    data-bs-target="#order-history-modal"
                                    id="order-history-button">

                                    <i class="ki-outline ki-time fs-4 me-2 text-dark"></i>
                                    Order History

                                </button>

                                <div class="separator my-2"></div>

                                <button
                                    class="dropdown-item rounded-3 py-3 text-danger fw-bold"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cancel-order-modal"
                                    id="cancel-button">

                                    <i class="ki-outline ki-cross-circle fs-4 me-2 text-danger"></i>
                                    Cancel Order

                                </button>

                            </div>

                        </div>

                    </div>

                    <!-- QUICK STATUS -->
                    <div class="d-flex gap-2 flex-wrap mt-4 cart-action d-none">

                        <span
                            class="badge badge-light-success px-4 py-3 fw-bold fs-8"
                            id="badge-payment-status">

                            --

                        </span>

                        <span
                            class="badge badge-light-primary px-4 py-3 fw-bold fs-8"
                            id="badge-order-type">
                            Walk-in
                        </span>

                        <span
                            class="badge badge-light-warning px-4 py-3 fw-bold fs-8"
                            id="badge-customer-name">

                            Walk-in Customer

                        </span>

                        @if($shopRegister->is_restaurant === 'Yes')

                            <span
                                class="badge badge-light-info px-4 py-3 fw-bold fs-8"
                                id="badge-table">

                                No Table

                            </span>

                        @endif

                    </div>

                </div>

                <!-- ===================================================== -->
                <!-- MAIN BODY -->
                <!-- ===================================================== -->
                <div class="card-body p-0 d-flex flex-column">

                    <!-- ===================================================== -->
                    <!-- QUICK ACTION STRIP -->
                    <!-- ===================================================== -->
                    <div class="border-bottom px-4 py-3 cart-action d-none bg-light">

                        <div class="row g-2">

                            <div class="col-4">

                                <button
                                    class="btn btn-light-primary w-100 h-100 fw-bold py-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#customer-modal"
                                    id="customer-button">

                                    <i class="ki-outline ki-user fs-2 d-block mb-1"></i>

                                    <span class="small">
                                        Customer
                                    </span>

                                </button>

                            </div>

                            <div class="col-4">

                                <button
                                    class="btn btn-light-warning w-100 h-100 fw-bold py-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#discount-modal"
                                    id="manage-discount-button">

                                    <i class="ki-outline ki-discount fs-2 d-block mb-1"></i>

                                    <span class="small">
                                        Discount
                                    </span>

                                </button>

                            </div>

                            <div class="col-4">

                                <button
                                    class="btn btn-light-danger w-100 h-100 fw-bold py-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#charges-modal"
                                    id="manage-charge-button">

                                    <i class="ki-outline ki-dollar fs-2 d-block mb-1"></i>

                                    <span class="small">
                                        Charges
                                    </span>

                                </button>

                            </div>

                        </div>

                    </div>

                    <!-- ===================================================== -->
                    <!-- ORDER TYPE + TABLE -->
                    <!-- ===================================================== -->
                    <div class="px-5 pt-4 cart-action d-none">

                        <div class="row g-3">

                            <!-- ORDER TYPE -->
                            <div class="col">

                                <select
                                    class="form-select form-select-solid fw-bold fs-6 rounded-4"
                                    id="order-type">

                                    <option value="Walk-in">
                                        Walk-in
                                    </option>

                                    @if($shopRegister->is_restaurant === 'Yes')

                                        <option value="Dine-in">
                                            Dine-in
                                        </option>

                                        <option value="Take-out">
                                            Take-out
                                        </option>

                                    @endif

                                    <option value="Delivery">
                                        Delivery
                                    </option>

                                </select>

                            </div>

                            @if($shopRegister->is_restaurant === 'Yes')

                                <!-- TABLE -->
                                <div
                                    class="col d-none"
                                    id="set-table-column">

                                    <button
                                        class="btn btn-light-warning w-100 fw-bolder py-3 rounded-4 d-flex align-items-center justify-content-center gap-2"
                                        id="set-table"
                                        data-bs-toggle="modal"
                                        data-bs-target="#table-modal">

                                        <i class="ki-outline ki-element-11 fs-3"></i>

                                        <span id="selected-table-label">
                                            Select Table
                                        </span>

                                    </button>

                                </div>

                            @endif

                        </div>

                    </div>

                    <!-- ===================================================== -->
                    <!-- EMPTY -->
                    <!-- ===================================================== -->
                    <div
                        id="shop-order-empty"
                        class="flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center px-5 py-10">

                        <div class="mb-5">

                            <div class="symbol symbol-100px">

                                <div class="symbol-label bg-light-primary rounded-circle">

                                    <i class="ki-duotone ki-handcart fs-5x text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>

                                </div>

                            </div>

                        </div>

                        <div class="fw-bold fs-2 text-gray-800 mb-2">
                            Cart is Empty
                        </div>

                        <div class="text-muted fs-6 fw-semibold">
                            Scan or select products to begin.
                        </div>

                    </div>

                    <!-- ===================================================== -->
                    <!-- CART LIST -->
                    <!-- ===================================================== -->
                    <div
                        id="shop-order-list"
                        class="d-none flex-grow-1 px-5 py-4"
                        style="overflow-y:auto; max-height: 420px;">

                    </div>

                    <!-- ===================================================== -->
                    <!-- SUMMARY -->
                    <!-- ===================================================== -->
                    <div
                        id="shop-order-summary-card"
                        class="border-top bg-light d-none">

                        <div class="p-5">

                            <div id="order-summary-list">

                            </div>

                        </div>

                    </div>

                </div>

                <!-- ===================================================== -->
                <!-- STICKY BOTTOM ACTION BAR -->
                <!-- ===================================================== -->
                <div class="border-top p-4 cart-action d-none">

                    <!-- ===================================================== -->
                    <!-- ORDER UTILITIES -->
                    <!-- ===================================================== -->
                    <div class="row g-2 mb-3">
                        <div class="col-12 mb-2">

                            <button
                                class="btn btn-light w-100 fw-bold py-3 rounded-4"
                                id="print-bill">

                                <div class="d-flex align-items-center justify-content-center gap-2">

                                    <i class="ki-outline ki-cheque fs-2"></i>

                                    <span>
                                        Print Bill
                                    </span>

                                </div>

                            </button>

                        </div>

                        <!-- VOID -->
                        <div
                            class="col-6 d-none"
                            id="void-order-column">

                           <button
                                class="btn btn-danger w-100 py-4 fs-4 rounded-4"
                                id="void-order-button"
                                data-bs-toggle="modal"
                                data-bs-target="#void-order-modal">

                                <i class="ki-outline ki-cross-circle fs-2 me-2"></i>

                                Void

                            </button>

                        </div>

                        <!-- REFUND -->
                        <div
                            class="col-6 d-none"
                            id="refund-order-column">

                            <button
                                class="btn btn-warning w-100 py-4 fs-4 rounded-4"
                                id="refund-order-button"
                                data-bs-toggle="modal"
                                data-bs-target="#refund-order-modal">

                                <i class="ki-outline ki-arrow-circle-left fs-2 me-2"></i>

                                Refund

                            </button>

                        </div>

                    </div>

                    <!-- MAIN ACTIONS -->
                    <div class="row g-3">

                        @if($shopRegister->is_restaurant === 'Yes')

                            <div class="col">

                                <button
                                    class="btn btn-warning w-100 py-4 fs-4 rounded-4"
                                    id="kitchen-button">

                                    <i class="ki-outline ki-entrance-left fs-2 me-2"></i>

                                    Kitchen

                                </button>

                            </div>

                            <div class="col">

                                <button
                                    class="btn btn-success w-100 py-4 fs-4 rounded-4"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payment-modal"
                                    id="manage-payment-button">

                                    <i class="ki-outline ki-wallet fs-2 me-2"></i>

                                    Payment

                                </button>

                            </div>

                        @else

                            <div class="col-12">

                                <button
                                    class="btn btn-success w-100 py-4 fs-2 rounded-4 shadow-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payment-modal"
                                    id="manage-payment-button">

                                    <i class="ki-outline ki-wallet fs-1 me-3"></i>

                                    Payment

                                </button>

                            </div>

                        @endif

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

    <div class="modal fade" id="payment-modal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">

            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <!-- HEADER -->
                <div class="modal-header py-3">

                    <div>
                        <h5 class="modal-title fw-bold mb-0">
                            Complete Payment
                        </h5>
                        <div class="small opacity-75">
                            Split payments supported
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

                </div>

                <!-- BODY -->
                <div class="modal-body p-4">

                    <!-- TOP SUMMARY BAR -->
                    <div class="row g-3 mb-4">

                        <!-- DUE -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Amount Due</div>
                                    <div class="fs-1 fw-bold text-danger" id="payment-balance-display">₱ 0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- PAID -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Total Paid</div>
                                    <div class="fs-1 fw-bold text-dark" id="total-payment-display">₱ 0.00</div>
                                </div>
                            </div>
                        </div>

                        <!-- CHANGE -->
                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Change</div>
                                    <div class="fs-1 fw-bold text-success" id="payment-change-display">₱ 0.00</div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ORDER INFO -->
                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <div class="text-muted small">
                            Order No:
                            <span class="fw-bold text-dark" id="payment-order-number"></span>
                        </div>

                    </div>

                    <!-- PAYMENT METHODS -->
                    <div id="payment-method-list"></div>

                    <!-- VALIDATION -->
                    <div class="alert alert-danger d-none mt-3" id="payment-validation-message"></div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer border-0 p-4 d-flex justify-content-between">

                    <button class="btn btn-light px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button class="btn btn-success px-5 fw-bold" id="complete-payment-button">
                        Complete Payment
                    </button>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="order-history-modal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-xl">

            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">

                <!-- HEADER -->
                <div class="modal-header border-0 py-4 px-5">

                    <div>

                        <h2 class="fw-bold mb-1">
                            Order History
                        </h2>

                        <div class="text-muted fw-semibold">
                            Current register session orders
                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn btn-icon btn-sm btn-light"
                        data-bs-dismiss="modal">

                        <i class="ki-outline ki-cross fs-2"></i>

                    </button>

                </div>

                <div class="p-4">

                    <!-- SEARCH + FILTER -->
                    <div class="row g-2 mb-3">

                        <div class="col-8">
                            <input type="text"
                                class="form-control form-control-solid"
                                id="order-history-search"
                                placeholder="Search order number or customer...">
                        </div>

                        <div class="col-4">
                            <select class="form-select form-select-solid"
                                id="order-history-filter">

                                <option value="all">All</option>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Paid">Paid</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Voided">Voided</option>
                                <option value="Refunded">Refunded</option>

                            </select>
                        </div>

                    </div>

                    <!-- GRID -->
                    <div id="order-history-grid"
                        class="row g-3">

                    </div>

                    <!-- EMPTY -->
                    <div id="order-history-empty"
                        class="text-center text-muted py-10 d-none">
                        No orders found
                    </div>

                    <!-- LOADING -->
                    <div
                        id="order-history-loading"
                        class="text-center py-10 d-none">

                        <div class="spinner-border text-primary mb-3" role="status"></div>

                        <div class="fw-semibold text-muted">
                            Loading orders...
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="customer-modal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">

                <!-- HEADER -->
                <div class="modal-header border-0 px-5 py-4">

                    <div>

                        <h2 class="fw-bold mb-1">
                            Customer
                        </h2>

                        <div class="text-muted fw-semibold">
                            Assign customer to this order
                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn btn-icon btn-sm btn-light"
                        data-bs-dismiss="modal">

                        <i class="ki-outline ki-cross fs-2"></i>

                    </button>

                </div>

                <!-- BODY -->
                <div class="modal-body px-5 pb-5">

                    <!-- QUICK BUTTONS -->
                    <div class="mb-4">

                        <div class="fw-bold text-gray-700 mb-3">
                            Quick Select
                        </div>

                        <div class="d-flex flex-wrap gap-2">

                            <button
                                class="btn btn-light-primary rounded-pill customer-quick-select"
                                data-customer-name="Walk-in Customer">

                                Walk-in

                            </button>

                            <button
                                class="btn btn-light-info rounded-pill customer-quick-select"
                                data-customer-name="Guest">

                                Guest

                            </button>

                            <button
                                class="btn btn-light-success rounded-pill customer-quick-select"
                                data-customer-name="VIP Customer">

                                VIP

                            </button>

                        </div>

                    </div>

                    <!-- INPUT -->
                    <div class="mb-4">

                        <label class="form-label fw-bold text-gray-700">
                            Customer Name
                        </label>

                        <input
                            type="text"
                            class="form-control form-control-solid form-control-lg"
                            id="customer-name-input"
                            placeholder="Enter customer name">

                    </div>

                    <!-- CURRENT -->
                    <div
                        class="alert alert-light-primary border border-primary border-dashed rounded-4"
                        id="current-customer-container">

                        <div class="fw-bold mb-1">
                            Current Customer
                        </div>

                        <div
                            class="text-gray-700"
                            id="current-customer-name">

                            Walk-in Customer

                        </div>

                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer border-0 px-5 pb-5 pt-0">

                    <button
                        type="button"
                        class="btn btn-light-danger fw-bold"
                        id="remove-customer-button">

                        Remove Customer

                    </button>

                    <button
                        type="button"
                        class="btn btn-primary fw-bolder px-5"
                        id="save-customer-button">

                        Save Customer

                    </button>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="void-order-modal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content rounded-4 border-0 shadow-sm">

                <div class="modal-header border-0">

                    <div>

                        <h2 class="fw-bold mb-1">
                            Void Request
                        </h2>

                        <div class="text-muted small">
                            Submit request for manager approval
                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn btn-icon btn-sm btn-light"
                        data-bs-dismiss="modal">

                        <i class="ki-outline ki-cross fs-2"></i>

                    </button>

                </div>

                <div class="modal-body pt-0">

                    <div class="mb-5">

                        <label class="form-label fw-bold required">
                            Reason for Void
                        </label>

                        <textarea
                            class="form-control form-control-solid"
                            id="void-reason"
                            rows="5"
                            placeholder="Enter reason"></textarea>

                    </div>

                </div>

                <div class="modal-footer border-0">

                    <button
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Close

                    </button>

                    <button
                        class="btn btn-danger fw-bold"
                        id="submit-void-request">

                        Submit Void Request

                    </button>

                </div>

            </div>

        </div>

    </div>

    <div
        class="modal fade"
        id="refund-order-modal"
        tabindex="-1"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content rounded-4 border-0 shadow-sm">

                <div class="modal-header border-0">

                    <div>

                        <h2 class="fw-bold mb-1">
                            Refund Request
                        </h2>

                        <div class="text-muted small">
                            Submit request for manager approval
                        </div>

                    </div>

                    <button
                        type="button"
                        class="btn btn-icon btn-sm btn-light"
                        data-bs-dismiss="modal">

                        <i class="ki-outline ki-cross fs-2"></i>

                    </button>

                </div>

                <div class="modal-body pt-0">

                    <div class="mb-5">

                        <label class="form-label fw-bold required">
                            Reason for Refund
                        </label>

                        <textarea
                            class="form-control form-control-solid"
                            id="refund-reason"
                            rows="5"
                            placeholder="Enter reason"></textarea>

                    </div>

                </div>

                <div class="modal-footer border-0">

                    <button
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Close

                    </button>

                    <button
                        class="btn btn-warning fw-bold"
                        id="submit-refund-request">

                        Submit Refund Request

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