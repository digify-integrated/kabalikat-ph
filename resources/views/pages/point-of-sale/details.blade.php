@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')

    <div class="row">
        <div class="col-lg-8" id="">
            <div class="row mb-5">
                <div class="col">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" class="form-control form-control-solid w-100 ps-12" placeholder="Search products, SKU, barcode...">
                    </div>
                </div>
            </div>

            <div class="mb-4 overflow-auto">
                <div class="d-flex flex-nowrap gap-2 pb-1" id="shop-product-category-container">

                    <button
                        type="button"
                        class="btn btn-primary rounded-pill px-4 py-2 product-category-filter active"
                        data-product-filter="all">
                        All
                    </button>

                    <button
                        type="button"
                        class="btn btn-light rounded-pill px-4 py-2 product-category-filter"
                        data-product-filter="1">
                        Appetizer
                    </button>

                    <button
                        type="button"
                        class="btn btn-light rounded-pill px-4 py-2 product-category-filter"
                        data-product-filter="2">
                        Fries
                    </button>

                    <button
                        type="button"
                        class="btn btn-light rounded-pill px-4 py-2 product-category-filter"
                        data-product-filter="3">
                        Drinks
                    </button>

                    <button
                        type="button"
                        class="btn btn-light rounded-pill px-4 py-2 product-category-filter"
                        data-product-filter="4">
                        Desserts
                    </button>

                </div>
            </div>

            <div class="row g-3">

                <!-- AVAILABLE -->
                <div class="col-6 col-md-4 col-xl-3">

                    <button
                        type="button"
                        class="card border-0 shadow-sm h-100 w-100 text-start product-card">

                        <div class="card-body">

                            <!-- STATUS -->
                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                                    Available
                                </span>

                                <i class="ki-duotone ki-basket fs-2 text-muted">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>

                            </div>

                            <!-- PRODUCT -->
                            <div class="mb-4">

                                <h5 class="fw-bold text-dark mb-1">
                                    Bacon Cheese Fries
                                </h5>

                                <div class="text-muted small">
                                    Fries Category
                                </div>

                            </div>

                            <!-- PRICE -->
                            <div class="d-flex align-items-center justify-content-between">

                                <div>

                                    <div class="small text-muted mb-1">
                                        Price
                                    </div>

                                    <div class="fw-bold fs-2 text-primary">
                                        ₱189
                                    </div>

                                </div>

                                <div class="text-primary">
                                    <i class="ki-duotone ki-arrow-right fs-2"></i>
                                </div>

                            </div>

                        </div>

                    </button>

                </div>

                <!-- OUT OF STOCK -->
                <div class="col-6 col-md-4 col-xl-3">

                    <button
                        type="button"
                        disabled
                        class="card border-0 shadow-sm h-100 w-100 text-start product-card product-card-disabled">

                        <div class="card-body">

                            <!-- STATUS -->
                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2">
                                    Out of Stock
                                </span>

                                <i class="ki-duotone ki-cross-circle fs-2 text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>

                            </div>

                            <!-- PRODUCT -->
                            <div class="mb-4">

                                <h5 class="fw-bold text-muted mb-1">
                                    Truffle Fries
                                </h5>

                                <div class="text-muted small">
                                    Fries Category
                                </div>

                            </div>

                            <!-- PRICE -->
                            <div class="d-flex align-items-center justify-content-between">

                                <div>

                                    <div class="small text-muted mb-1">
                                        Price
                                    </div>

                                    <div class="fw-bold fs-2 text-muted">
                                        ₱249
                                    </div>

                                </div>

                                <div class="text-danger">
                                    <i class="ki-duotone ki-information fs-2"></i>
                                </div>

                            </div>

                        </div>

                    </button>

                </div>

            </div>
        </div>
    
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-body">

            <!-- HEADER -->
            <div class="card-header border-0 pt-6 px-6">

                <div class="d-flex flex-column">

                    <h2 class="fw-bold mb-1 text-primary" id="order-details-title">
                        Cart
                    </h2>

                    <div class="text-muted fw-semibold">
                        Order ID #<span id="order-id">0004</span>
                    </div>

                </div>

                <!-- ACTIONS -->
                <div class="card-toolbar">

                    <button
                        class="btn btn-icon btn-light btn-sm"
                        data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">

                        <i class="ki-outline ki-dots-vertical fs-2"></i>

                    </button>

                    <!-- MENU -->
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
                                class="menu-link px-3">

                                New Order

                            </a>
                        </div>

                    </div>

                </div>

            </div>

            <!-- BODY -->
            <div class="card-body px-6 pb-6">

                <!-- ORDER SETTINGS -->
                <div class="row g-3 mb-5">
                    <div class="col">
                        <select
                            class="form-select form-select-solid fw-semibold"
                            id="order-preset"
                            data-control="select2"
                            data-hide-search="true">

                            <option value="Walk-in">Walk-in</option>
                            <option value="Dine-in">Dine-in</option>
                            <option value="Takeout">Takeout</option>
                            <option value="Delivery">Delivery</option>

                        </select>
                    </div>

                </div>

                <!-- ORDER ITEMS -->
                <div
                    class="pe-2 mb-5"
                    style="max-height: 420px; overflow-y: auto;"
                    id="shop-order-list">

                        <!-- ORDER ITEM -->
                        <div class="card border-0 bg-light mb-3">

                            <div class="card-body p-4">

                                <!-- TOP -->
                                <div class="d-flex justify-content-between align-items-start mb-4">

                                    <div class="me-3">

                                        <h5 class="fw-bold text-dark mb-1">
                                            Bacon Cheese Fries
                                        </h5>

                                        <div class="text-muted small">
                                            Fries Category
                                        </div>

                                    </div>

                                    <div class="fw-bold fs-4 text-primary text-nowrap">
                                        ₱ 20.00
                                    </div>

                                </div>

                                <!-- BOTTOM -->
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                                    <!-- QUANTITY -->
                                    <div
                                        class="d-flex align-items-center gap-2"
                                        data-kt-dialer="true"
                                        data-kt-dialer-min="1"
                                        data-kt-dialer-step="1">

                                        <button
                                            type="button"
                                            class="btn btn-icon btn-light btn-sm"
                                            data-kt-dialer-control="decrease">

                                            <i class="ki-outline ki-minus fs-3"></i>

                                        </button>

                                        <input
                                            type="text"
                                            class="form-control border-0 bg-white text-center fw-bold w-60px"
                                            value="2"
                                            readonly>

                                        <button
                                            type="button"
                                            class="btn btn-icon btn-light btn-sm"
                                            data-kt-dialer-control="increase">

                                            <i class="ki-outline ki-plus fs-3"></i>

                                        </button>

                                    </div>

                                    <!-- ACTIONS -->
                                    <div class="d-flex align-items-center gap-2">

                                        <button
                                            class="btn btn-icon btn-light-danger btn-sm">

                                            <i class="ki-outline ki-trash fs-3"></i>

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- SUMMARY -->
                    <div class="card border-0 bg-light mb-5">

                        <div class="card-body p-5">

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold">₱ 20.00</span>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">VAT Sales</span>
                                <span class="fw-semibold">₱ 17.86</span>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">VAT (12%)</span>
                                <span class="fw-semibold">₱ 2.14</span>
                            </div>

                            <div class="d-flex justify-content-between mb-4">
                                <span class="text-muted">Service Charge</span>
                                <span class="fw-semibold">₱ 3.00</span>
                            </div>

                            <div class="separator separator-dashed mb-4"></div>

                            <!-- TOTAL -->
                            <div class="d-flex justify-content-between align-items-center">

                                <span class="fw-bold fs-3">
                                    Total
                                </span>

                                <span
                                    class="fw-bolder fs-1 text-primary"
                                    id="shop-order-total">

                                    ₱ 23.00

                                </span>

                            </div>

                        </div>

                    </div>

                    <!-- SECONDARY ACTIONS -->
                    <div class="row g-3 mb-3">

                        <div class="col">
                            <button class="btn btn-light w-100 py-3 fw-semibold">
                                Print Bill
                            </button>
                        </div>

                        <div class="col">
                            <button
                                class="btn btn-light-success w-100 py-3 fw-semibold"
                                data-bs-toggle="modal"
                                data-bs-target="#discount-modal">

                                Discounts

                            </button>
                        </div>

                        <div class="col">
                            <button
                                class="btn btn-light-danger w-100 py-3 fw-semibold"
                                data-bs-toggle="modal"
                                data-bs-target="#charges-modal">

                                Charges

                            </button>
                        </div>

                    </div>

                    <!-- PRIMARY ACTIONS -->
                    <div class="row g-3">

                        <div class="col">
                            <button
                                class="btn btn-warning w-100 py-4 fw-bold fs-5">

                                Send To Kitchen

                            </button>
                        </div>

                        <div class="col">
                            <button
                                class="btn btn-success w-100 py-4 fw-bold fs-5"
                                data-bs-toggle="modal"
                                data-bs-target="#payment-modal">

                                Payment

                            </button>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>
    <script>
        $('.product-category-filter').on('click', function () {

    $('.product-category-filter')
        .removeClass('btn-primary active')
        .addClass('btn-light');

    $(this)
        .removeClass('btn-light')
        .addClass('btn-primary active');

});
        </script>


    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush