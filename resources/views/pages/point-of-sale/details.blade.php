@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
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
                <div class="card-toolbar detailed-order d-none">

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
                <div class="row g-3 mb-5 d-none">
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
                                <span class="fw-semibold">₱ 0.00</span>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">VAT Sales</span>
                                <span class="fw-semibold">₱ 0.00</span>
                            </div>

                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">VAT (12%)</span>
                                <span class="fw-semibold">₱ 0.00</span>
                            </div>

                            <div class="d-flex justify-content-between mb-4">
                                <span class="text-muted">Service Charge</span>
                                <span class="fw-semibold">₱ 0.00</span>
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

                                    ₱ 0.00

                                </span>

                            </div>

                        </div>

                    </div>

                    <!-- SECONDARY ACTIONS -->
                    <div class="row g-3 mb-3 detailed-order d-none">

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
                    <div class="row g-3 detailed-order d-none">

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

                            <div class="row align-items-center mt-3 g-2">
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
                                            class="form-control text-center fw-bold fs-7 border-0 bg-light rounded w-75px"
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
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>
    
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush