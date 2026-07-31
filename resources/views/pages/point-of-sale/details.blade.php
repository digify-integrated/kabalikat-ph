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

    <div class="row g-5">
    <!-- LEFT PANE: PRODUCT CATALOG -->
    <div class="col-lg-8">
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-duotone ki-magnifier fs-2 position-absolute ms-4 text-gray-500"></i>
                    <input type="text" class="form-control form-control-solid form-control-lg w-100 ps-12 fs-6 rounded-3" id="product_search" placeholder="Search products, SKU, barcode...">
                </div>
            </div>
        </div>

        <div class="mb-4 overflow-auto">
            <div class="d-flex flex-nowrap gap-2 pb-1" id="shop-product-category-container"></div>
        </div>

        <div class="row g-3" id="product-container"></div>
    </div>

    <!-- RIGHT PANE: STANDARD POS CART TERMINAL -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            
            <!-- 1. Header & Order Badges -->
            <div class="card-header border-bottom px-5 py-4 bg-body min-h-auto d-block">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-45px">
                            <div class="symbol-label bg-light-primary rounded-3">
                                <i class="ki-duotone ki-handcart fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                        </div>
                        <div>
                            <h3 class="fw-bold fs-3 text-gray-900 mb-0">Active Cart</h3>
                            <div class="text-muted fs-7">
                                Order ID: <span class="text-danger fw-bold" id="order-id">No Active Order</span>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-icon btn-sm btn-light-active-primary" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ki-outline ki-dots-vertical fs-2"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end rounded-3 shadow-sm border-0 p-2 w-225px">
                            <button class="dropdown-item rounded-2 py-2.5 fw-semibold" id="print-order-summary">
                                <i class="ki-outline ki-printer fs-4 me-2 text-muted"></i>Print Order Summary
                            </button>
                            <button class="dropdown-item rounded-2 py-2.5 fw-semibold" id="print-payment-summary">
                                <i class="ki-outline ki-printer fs-4 me-2 text-muted"></i>Print Payment Summary
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-1.5 flex-wrap mt-3 cart-action d-none">
                    <span class="badge badge-light-primary fw-bold fs-8 px-3 py-2 rounded-2" id="badge-customer-name">Walk-in Customer</span>
                    <span class="badge badge-light-warning fw-bold fs-8 px-3 py-2 rounded-2" id="badge-order-type">Walk-in</span>
                    @if($shopRegister->is_restaurant === 'Yes')
                        <span class="badge badge-light-dark fw-bold fs-8 px-3 py-2 rounded-2" id="badge-table">No Table</span>
                    @endif
                    <span class="badge badge-light-success fw-bold fs-8 px-3 py-2 rounded-2" id="badge-order-status">Unpaid</span>
                </div>
            </div>

            <!-- 2. Barcode & Action Controls -->
            <div class="card-body p-5 bg-light-subtle border-bottom">
                <div class="mb-3">
                    <div class="input-group input-group-solid border border-gray-300 rounded-3 bg-white shadow-xs">
                        <span class="input-group-text bg-transparent border-0 pe-2">
                            <i class="ki-outline ki-barcode fs-1 text-primary"></i>
                        </span>
                        
                        <input type="text" 
                            id="pos-barcode-scanner-input" 
                            class="form-control form-control-solid fs-7 py-3 ps-1 bg-transparent border-0" 
                            placeholder="Ready to scan or type barcode..." 
                            autocomplete="off" />
                        
                        <span class="input-group-text bg-transparent border-0 pe-3">
                            <span class="badge badge-light-success d-flex align-items-center gap-1.5 py-1.5 px-2.5 rounded-2">
                                <span class="bullet bullet-dot bg-success h-6px w-6px animation-blink"></span>
                                <span class="fw-bold fs-9 text-success text-uppercase">Live</span>
                            </span>
                        </span>
                    </div>
                </div>
                
                <!-- Main Action Buttons -->
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <button class="btn btn-white btn-color-gray-800 btn-active-light-success border shadow-xs w-100 py-3 d-flex flex-column align-items-center gap-1 rounded-3" id="new-order">
                            <i class="ki-outline ki-plus fs-2 text-success p-0"></i>
                            <span class="fw-bold fs-7">New</span>
                        </button>
                    </div>
                    <div class="col-4">
                        <button class="btn btn-white btn-color-gray-800 btn-active-light-warning border shadow-xs w-100 py-3 d-flex flex-column align-items-center gap-1 rounded-3" data-bs-toggle="modal" data-bs-target="#order-history-modal" id="order-history-button">
                            <i class="ki-outline ki-time fs-2 text-warning p-0"></i>
                            <span class="fw-bold fs-7">Orders</span>
                        </button>
                    </div>
                    <div class="col-4">
                        <button class="btn btn-white btn-color-gray-800 btn-active-light-danger border shadow-xs w-100 py-3 d-flex flex-column align-items-center gap-1 rounded-3" data-bs-toggle="modal" data-bs-target="#cancel-order-modal" id="cancel-button">
                            <i class="ki-outline ki-cross-circle fs-2 text-danger p-0"></i>
                            <span class="fw-bold fs-7">Cancel</span>
                        </button>
                    </div>
                </div>
                
                <!-- Secondary Quick Modals -->
                <div class="row g-2 mb-3 cart-action d-none">
                    <div class="col-4">
                        <button class="btn btn-white btn-color-gray-800 btn-active-light-primary border shadow-xs w-100 py-2.5 d-flex flex-column align-items-center gap-1 rounded-3" data-bs-toggle="modal" data-bs-target="#customer-modal" id="customer-button">
                            <i class="ki-outline ki-user fs-2 text-primary p-0"></i>
                            <span class="fw-semibold fs-8">Customer</span>
                        </button>
                    </div>
                    <div class="col-4">
                        <button class="btn btn-white btn-color-gray-800 btn-active-light-warning border shadow-xs w-100 py-2.5 d-flex flex-column align-items-center gap-1 rounded-3" data-bs-toggle="modal" data-bs-target="#discount-modal" id="manage-discount-button">
                            <i class="ki-outline ki-discount fs-2 text-warning p-0"></i>
                            <span class="fw-semibold fs-8">Discount</span>
                        </button>
                    </div>
                    <div class="col-4">
                        <button class="btn btn-white btn-color-gray-800 btn-active-light-danger border shadow-xs w-100 py-2.5 d-flex flex-column align-items-center gap-1 rounded-3" data-bs-toggle="modal" data-bs-target="#charges-modal" id="manage-charge-button">
                            <i class="ki-outline ki-dollar fs-2 text-danger p-0"></i>
                            <span class="fw-semibold fs-8">Charges</span>
                        </button>
                    </div>
                </div>

                <!-- Order Type Options -->
                <div class="row g-2 cart-action d-none">
                    <div class="col">
                        <select class="form-select fw-bold fs-7 rounded-3 py-2.5" id="order-type">
                            <option value="Walk-in" {{ $shopRegister->is_restaurant !== 'Yes' ? 'selected' : '' }}>Walk-in</option>
                            @if($shopRegister->is_restaurant === 'Yes')
                                <option value="Dine-in" selected>Dine-in</option>
                                <option value="Take-out">Take-out</option>
                            @endif
                            <option value="Delivery">Delivery</option>
                        </select>
                    </div>

                    @if($shopRegister->is_restaurant === 'Yes')
                        <div class="col" id="set-table-column">
                            <button class="btn btn-light-warning border border-warning border-opacity-25 w-100 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 py-2.5" id="set-table" data-bs-toggle="modal" data-bs-target="#table-modal">
                                <i class="ki-outline ki-element-11 fs-4"></i>
                                <span id="selected-table-label" class="fs-7">Select Table</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. Dynamic Items List -->
            <div class="card-body p-5">
                <!-- Empty State -->
                <div id="shop-order-empty" class="text-center py-10">
                    <div class="symbol symbol-65px mb-3">
                        <div class="symbol-label bg-light-primary rounded-circle">
                            <i class="ki-duotone ki-handcart fs-2x text-primary">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </div>
                    </div>
                    <div class="fw-bold fs-5 text-gray-800 mb-1">Cart is empty</div>
                    <div class="text-muted fs-7 px-4">Select items from the catalog to build an order.</div>
                </div>

                <!-- Dynamic Item List -->
                <div id="shop-order-list" class="d-none min-h-150px"></div>
            </div>

            <!-- 4. Prominent Order Summary Block -->
            <div id="shop-order-summary-card" class="card-footer bg-light-subtle border-top px-5 py-4 d-none">
                <div class="bg-white p-4 rounded-3 border shadow-xs" id="order-summary-list"></div>
            </div>

            <!-- 5. Payment & Checkout Footer -->
            <div class="card-footer p-5 border-top">
                <div class="row g-2 mb-3 cart-action d-none">
                    <div class="col-12">
                        <button class="btn btn-light-primary btn-sm w-100 fw-bold py-2.5 rounded-3" id="print-bill">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <i class="ki-outline ki-cheque fs-4"></i><span>Print Bill</span>
                            </div>
                        </button>
                    </div>
                    <div class="col-6 d-none" id="void-order-column">
                        <button class="btn btn-light-danger btn-sm w-100 py-2 fs-6 rounded-3" id="void-order-button" data-bs-toggle="modal" data-bs-target="#void-order-modal">
                            <i class="ki-outline ki-cross-circle fs-5 me-1"></i>Void
                        </button>
                    </div>
                    <div class="col-6 d-none" id="refund-order-column">
                        <button class="btn btn-light-warning btn-sm w-100 py-2 fs-6 rounded-3" id="refund-order-button" data-bs-toggle="modal" data-bs-target="#refund-order-modal">
                            <i class="ki-outline ki-arrow-circle-left fs-5 me-1"></i>Refund
                        </button>
                    </div>
                </div>

                <div class="row g-2">
                    @if($shopRegister->is_restaurant === 'Yes')
                        <div class="col-6">
                            <button class="btn btn-light-warning w-100 py-3.5 fs-6 fw-bold rounded-3 border border-warning border-opacity-25" data-bs-toggle="modal" data-bs-target="#kitchen-send-modal" id="send-kitchen-ticket">
                                <i class="ki-outline ki-entrance-left fs-3 me-1"></i>Kitchen
                            </button>
                        </div>
                        <div class="col-6 cart-action d-none">
                            <button class="btn btn-success w-100 py-3.5 fs-6 fw-bold rounded-3 shadow-sm text-uppercase tracking-wide" data-bs-toggle="modal" data-bs-target="#payment-modal" id="manage-payment-button">
                                <i class="ki-outline ki-wallet fs-3 me-2"></i>Pay
                            </button>
                        </div>
                    @else
                        <div class="col-12 cart-action d-none">
                            <button class="btn btn-success w-100 py-3.5 fs-5 fw-bold rounded-3 shadow-sm text-uppercase tracking-wide" data-bs-toggle="modal" data-bs-target="#payment-modal" id="manage-payment-button">
                                <i class="ki-outline ki-wallet fs-3 me-2"></i>Pay
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
                        <div class="card border-0 bg-light mb-4 rounded-3">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted fs-8">Product</div>
                                        <div class="fw-bold fs-6" id="modal-product-name">—</div>
                                    </div>

                                    <div class="text-end">
                                        <div class="text-muted fs-8">Price</div>
                                        <div class="fw-bold text-primary fs-5" id="modal-product-price">₱ 0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold fs-7 text-muted">Quantity</label>

                            <div class="row align-items-center g-2">
                                <div class="col-8 col-md">
                                    <div class="position-relative d-flex align-items-center order-quantity flex-wrap flex-sm-nowrap gap-2" data-kt-dialer="true" data-kt-dialer-min="1" data-kt-dialer-step="1" data-kt-dialer-decimals="0">
                                        <button type="button" class="btn btn-icon btn-sm btn-light" data-kt-dialer-control="decrease">
                                            <i class="ki-outline ki-minus fs-2"></i>
                                        </button>

                                        <input type="text" class="form-control text-center fw-bold fs-7 border-0 bg-light rounded w-100" data-kt-dialer-control="input" id="order_qty_input" name="order_qty_input" value="1" readonly />

                                        <button type="button" class="btn btn-icon btn-sm btn-light" data-kt-dialer-control="increase">
                                            <i class="ki-outline ki-plus fs-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="order_note" class="form-label fw-semibold fs-7 text-muted">Order Note</label>

                            <textarea id="order_note" name="order_note" class="form-control form-control-solid" rows="3" maxlength="500" placeholder="e.g. No onions, extra crispy, add sauce..."></textarea>

                            <div class="form-text fs-8 text-muted">
                                Optional instructions for kitchen or preparation
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" accesskey=""data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary fw-bold" id="submit-product">
                            Add to Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="table-modal" class="modal fade" tabindex="-1" aria-labelledby="table-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 px-6 py-5 bg-light">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div class="symbol symbol-40px bg-success bg-opacity-10">
                                <span class="symbol-label">
                                    <i class="ki-duotone ki-element-11 fs-2 text-success"></i>
                                </span>
                            </div>

                            <div>
                                <h2 class="fw-bold text-gray-900 mb-0">Select Table</h2>
                                <div class="text-muted fs-7 fw-semibold">
                                    Assign this order to a dining table
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-icon btn-sm btn-light rounded-circle" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body px-6 py-5">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-6">
                        <div class="d-flex flex-wrap gap-3" id="shop-floor-plan-container"></div>
                    </div>
                    <div class="row g-4" id="shop-floor-table-container"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="discount-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-light px-6 py-5">
                    <div>
                        <h2 class="fw-bold mb-1">Manage Discounts</h2>

                        <div class="text-muted fw-semibold fs-7">
                            Apply or remove discounts for this order
                        </div>
                    </div>

                    <button type="button" class="btn btn-icon btn-sm btn-light rounded-circle" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body px-6 py-5">
                    <div class="mb-6">
                        <label class="fw-bold fs-6 mb-3 d-block">Available Discounts</label>

                        <div id="available-discount-list" class="d-flex flex-column gap-3"></div>
                    </div>
                    
                    <div>
                        <label class="fw-bold fs-6 mb-3 d-block">Applied Discounts</label>

                        <div id="applied-discount-list" class="d-flex flex-column gap-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="charges-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-light px-6 py-5">
                    <div>
                        <h2 class="fw-bold mb-1">Manage Charges</h2>

                        <div class="text-muted fw-semibold fs-7">
                            Apply or remove charges for this order
                        </div>
                    </div>

                    <button type="button" class="btn btn-icon btn-sm btn-light rounded-circle" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body px-6 py-5">
                    <div class="mb-6">
                        <label class="fw-bold fs-6 mb-3 d-block">Available Charges</label>

                        <div id="available-charge-list" class="d-flex flex-column gap-3"></div>
                    </div>

                    <div>
                        <label class="fw-bold fs-6 mb-3 d-block">Applied Charges</label>

                        <div id="applied-charge-list" class="d-flex flex-column gap-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="payment-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header py-3">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Complete Payment</h5>

                        <div class="small opacity-75">
                            Split payments supported
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Amount Due</div>
                                    <div class="fs-1 fw-bold text-danger" id="payment-balance-display">₱ 0.00</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Total Paid</div>
                                    <div class="fs-1 fw-bold text-dark" id="total-payment-display">₱ 0.00</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border-0 bg-light rounded-4">
                                <div class="card-body text-center">
                                    <div class="text-muted small">Change</div>
                                    <div class="fs-1 fw-bold text-success" id="payment-change-display">₱ 0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="text-muted small">
                            Order No:
                            <span class="fw-bold text-dark" id="payment-order-number"></span>
                        </div>
                    </div>

                    <div id="payment-method-list"></div>

                    <div class="alert alert-danger d-none mt-3" id="payment-validation-message"></div>
                </div>

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

   <div class="modal fade" id="order-history-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 py-4 px-5">
                    <div>
                        <h2 class="fw-bold mb-1">Order History</h2>

                        <div class="text-muted fw-semibold">
                            Current register session orders
                        </div>
                    </div>

                    <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="p-4">
                    <div class="card border-0 p-4 rounded-4 mb-4">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-6 col-lg-9">
                                <div class="input-group input-group-solid shadow-sm rounded-3">
                                    <span class="input-group-text bg-solid ps-4 border-0">
                                        <i class="ki-duotone ki-magnifier fs-4 text-muted"></i>
                                    </span>
                                    <input type="text" 
                                        class="form-control form-control-solid ps-2 py-3" 
                                        id="order-history-search" 
                                        placeholder="Search order #, customer, table, status (e.g., 'Paid', 'Dine-in')...">
                                </div>
                            </div>
                            <div class="col-6 col-lg-3 d-flex justify-content-lg-end gap-2">
                                <button type="button" class="btn btn-light-danger fw-bold w-100 w-lg-auto px-4" id="btn-reset-filters">
                                    <i class="ki-duotone ki-arrows-loop fs-5 me-1"></i> Clear Filters
                                </button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label fw-bold text-gray-700 fs-7 mb-1">Floor Plan & Table</label>
                                <select class="form-select form-select-solid shadow-sm" id="order-history-table-filter">
                                    <option value="all">All Tables / Location Types</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-4">
                                <label class="form-label fw-bold text-gray-700 fs-7 mb-1">Order Progress</label>
                                <select class="form-select form-select-solid shadow-sm" id="order-history-order-status-filter">
                                    <option value="all">All Order Statuses</option>
                                    <option value="Pending">Pending</option>
                                    
                                    @if($shopRegister->is_restaurant === 'Yes')
                                        <option value="Preparing">Preparing</option>
                                        <option value="Ready">Ready</option>
                                    @endif

                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Voided">Voided</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label fw-bold text-gray-700 fs-7 mb-1">Order Type</label>
                                <select class="form-select form-select-solid shadow-sm" id="order-history-order-type-filter">
                                    <option value="all">All Types</option>
                                    <option value="Walk-in">Walk-in</option>
                                    
                                    @if($shopRegister->is_restaurant === 'Yes')
                                        <option value="Dine-in">Dine-in</option>
                                        <option value="Take-out">Take-out</option>
                                    @endif

                                    <option value="Delivery">Delivery</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-lg-2">
                                <label class="form-label fw-bold text-gray-700 fs-7 mb-1">Payment Status</label>
                                <select class="form-select form-select-solid shadow-sm" id="order-history-payment-status-filter">
                                    <option value="all">All Payments</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Voided">Voided</option>
                                    <option value="Refunded">Refunded</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-y-auto px-2" style="max-height: 50vh;">
                        
                        <div id="order-history-grid" class="row g-3"></div>

                        <div id="order-history-empty" class="text-center text-muted py-10 d-none">
                            No orders found
                        </div>

                        <div id="order-history-loading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <div class="fw-semibold text-muted">
                                Loading orders...
                            </div>
                        </div>

                    </div>
                    </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customer-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 px-5 py-4">
                    <div>
                        <h2 class="fw-bold mb-1">Customer</h2>

                        <div class="text-muted fw-semibold">
                            Assign customer to this order
                        </div>
                    </div>

                    <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body px-5 pb-5">
                    <div class="mb-4">
                        <div class="fw-bold text-gray-700 mb-3">
                            Quick Select
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-light-primary rounded-pill customer-quick-select" data-customer-name="Walk-in Customer">
                                Walk-in
                            </button>

                            <button class="btn btn-light-info rounded-pill customer-quick-select" data-customer-name="Guest">
                                Guest
                            </button>

                            <button class="btn btn-light-success rounded-pill customer-quick-select" data-customer-name="VIP Customer">
                                VIP
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-gray-700">Customer Name</label>

                        <input type="text" class="form-control form-control-solid form-control-lg" id="customer-name-input" placeholder="Enter customer name">
                    </div>

                    <div class="alert alert-light-primary border border-primary border-dashed rounded-4" id="current-customer-container">
                        <div class="fw-bold mb-1">
                            Current Customer
                        </div>

                        <div class="text-gray-700" id="current-customer-name">
                            Walk-in Customer
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-5 pb-5 pt-0">
                    <button type="button" class="btn btn-light-danger fw-bold" id="remove-customer-button">
                        Remove Customer
                    </button>

                    <button type="button" class="btn btn-primary fw-bolder px-5" id="save-customer-button">
                        Save Customer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="void-order-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header border-0">
                    <div>
                        <h2 class="fw-bold mb-1">Void Request</h2>

                        <div class="text-muted small">
                            Submit request for manager approval
                        </div>
                    </div>

                    <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body pt-0">
                    <div class="mb-5">
                        <label class="form-label fw-bold required">Reason for Void</label>

                        <textarea class="form-control form-control-solid" id="void-reason" rows="5" placeholder="Enter reason"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button class="btn btn-light" data-bs-dismiss="modal">
                        Close
                    </button>

                    <button class="btn btn-danger fw-bold" id="submit-void-request">
                        Submit Void Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="refund-order-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-sm">
                <div class="modal-header border-0">
                    <div>
                        <h2 class="fw-bold mb-1">Refund Request</h2>

                        <div class="text-muted small">
                            Submit request for manager approval
                        </div>
                    </div>

                    <button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body pt-0">
                    <div class="mb-5">
                        <label class="form-label fw-bold required">Reason for Refund</label>

                        <textarea class="form-control form-control-solid" id="refund-reason" rows="5" placeholder="Enter reason"></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button class="btn btn-light" data-bs-dismiss="modal">
                        Close
                    </button>

                    <button class="btn btn-warning fw-bold" id="submit-refund-request">
                        Submit Refund Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kitchen-send-modal" tabindex="-1"aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 rounded-4 shadow-sm overflow-hidden">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h2 class="fw-bold mb-1">Send To Kitchen</h2>

                        <div class="text-muted fs-7">
                            Select items then choose station
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

               <div class="modal-body pt-5">
                    <div class="d-flex align-items-center justify-content-between mb-5 p-4 rounded-4 bg-light-primary">
                        <div>
                            <div class="fw-bold fs-3">
                                Kitchen Dispatch Center
                            </div>

                            <div class="text-muted fs-7">
                                Assign stations only for NEW items
                            </div>
                        </div>

                        <div class="text-end">
                            <div class="fw-bold fs-1" id="selected-kitchen-items-count">
                                0
                            </div>

                            <div class="text-muted fs-8">
                                Selected
                            </div>
                        </div>
                    </div>
                    
                    <div id="kitchen-items-container"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light"data-bs-dismiss="modal">
                        Close
                    </button>

                    <button type="button" class="btn btn-warning fw-bold px-8" id="confirm-send-kitchen-ticket">
                        <i class="ki-outline ki-entrance-left fs-2 me-1"></i>
                        Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancel-order-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header border-0 px-5 py-4 bg-light">
                    <div>
                        <h2 class="fw-bold mb-1 text-gray-900">Cancel Order</h2>
                        <div class="text-muted fw-semibold">Are you sure you want to cancel this transaction?</div>
                    </div>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-secondary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>

                <div class="modal-body px-5 py-4">
                    <div class="alert alert-dismissible bg-light-danger border border-danger border-dashed d-flex flex-column p-4 mb-4 rounded-3">
                        <div class="d-flex align-items-center mb-1 text-danger fw-bold fs-6">
                            <i class="ki-outline ki-information-5 fs-2 me-2 text-danger"></i>
                            Critical Action Notice
                        </div>
                        <span class="text-gray-700 fs-7">
                            Canceling this order will immediately void all tracked items. For restaurant terminals, cancellations are blocked if the kitchen has already begun cooking or serving the items.
                        </span>
                    </div>

                    <div class="mb-4">
                        <div class="fw-bold text-gray-700 mb-2 fs-7 text-uppercase tracking-wider">Quick Reasons</div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-light-sm btn-light rounded-pill reason-quick-select" data-reason="Customer Changed Mind">Customer Changed Mind</button>
                            <button type="button" class="btn btn-light-sm btn-light rounded-pill reason-quick-select" data-reason="Kitchen Out of Ingredients">Out of Stock</button>
                            <button type="button" class="btn btn-light-sm btn-light rounded-pill reason-quick-select" data-reason="Incorrect Order Entry">Entry Error</button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-gray-700 required">Reason for Cancellation</label>
                        <textarea 
                            class="form-control form-control-solid" 
                            id="order-cancellation-reason" 
                            rows="3" 
                            placeholder="Provide a mandatory reason why this ticket is being canceled..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 px-5 pb-5 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Keep Order</button>
                    <button type="button" class="btn btn-danger fw-bolder px-5" id="confirm-cancel-order-button">
                        <span class="indicator-label">Confirm Cancellation</span>
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