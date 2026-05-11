@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
    @endphp

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-5 mb-xl-8">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Warehouse</h2>
                    </div>
                </div>
                                
                <div class="card-body border-top p-9">
                    <div class="fv-row mb-0">
                        <select id="warehouse_id" name="warehouse_id[]" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false"  @disabled(!$canWrite)></select>
                    </div>
                </div>
            </div>

            <div class="card mb-5 mb-xl-8">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Payment Method</h2>
                    </div>
                </div>
                                
                <div class="card-body border-top p-9">
                    <div class="fv-row mb-0">
                        <select id="payment_method_id" name="payment_method_id[]" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false"  @disabled(!$canWrite)></select>
                    </div>
                </div>
            </div>

            <div class="card mb-5 mb-xl-8">
                <div class="card-header">
                    <div class="card-title">
                        <h2>Access</h2>
                    </div>
                </div>
                                
                <div class="card-body border-top p-9">
                    <div class="fv-row mb-0">
                        <select id="access" name="access[]" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false"  @disabled(!$canWrite)></select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-6" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#overview_tab" aria-selected="true" role="tab">Overview</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#floor_plan_tab" aria-selected="false" role="tab">Floor Plan</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#discounts_tab" aria-selected="false" role="tab">Discounts</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#charges_tab" aria-selected="false" role="tab">Charges</a>
                </li>
                <li class="nav-item ms-auto">
                    @if($canDelete)
                       <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            @if($canDelete)
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="delete-product">
                                        Delete
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-content" id="shop_register_tab_content">
                    <div class="tab-pane fade active show" id="overview_tab" role="tabpanel">
                        <div class="card mb-5">
                            <form id="shop_register_form" method="post" action="#" novalidate>
                                @csrf
                                <div class="card-header border-0">
                                    <div class="card-title m-0">
                                        <h3 class="fw-bold m-0">Shop Register Details</h3>
                                    </div>
                                </div>
                                <div class="card-body border-top p-9">
                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="shop_register_name">
                                            Shop Register Name
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" id="shop_register_name" name="shop_register_name" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                        </div>
                                    </div>
                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="company_id">
                                            Company
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="company_id" name="company_id" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                                <option value="">--</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="is_restaurant">
                                            Is Restaurant?
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="is_restaurant" name="is_restaurant" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="shop_register_status">
                                            Shop Register Status
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="shop_register_status" name="shop_register_status" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                @if($canWrite)
                                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                                        <button type="submit" class="btn btn-primary" id="submit-data">
                                            Save Changes
                                        </button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="floor_plan_tab" role="tabpanel">
                        <div class="card mb-5">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1 me-3">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="floor-plan-datatable-search" placeholder="Search..." autocomplete="off" />
                                    </div>
                                    <select id="floor-plan-datatable-length" class="form-select w-auto">
                                        <option value="-1">All</option>
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="card-toolbar">
                                    <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                                        @if($canWrite)
                                            <button type="button"
                                                    class="btn btn-light-primary me-3"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#floor-plan-modal"
                                                    id="add-attribute">
                                                <i class="ki-outline ki-plus fs-2"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-9">
                                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="floor-plan-table">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th>Floor Plan</th>
                                            <th>No. Tables</th>
                                            <th>Total Seats</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="discounts_tab" role="tabpanel">
                        <div class="card mb-5">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1 me-3">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="discount-datatable-search" placeholder="Search..." autocomplete="off" />
                                    </div>
                                    <select id="discount-datatable-length" class="form-select w-auto">
                                        <option value="-1">All</option>
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="card-toolbar">
                                    <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                                        @if($canWrite)
                                            <button type="button"
                                                    class="btn btn-light-primary me-3"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#discount-modal"
                                                    id="add-discount">
                                                <i class="ki-outline ki-plus fs-2"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-9">
                                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="discount-table">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th>Discount</th>
                                            <th>Value Type</th>
                                            <th>Is Variable</th>
                                            <th>Discount Value</th>
                                            <th>Automatic Application</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="charges_tab" role="tabpanel">
                        <div class="card mb-5">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1 me-3">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="charge-datatable-search" placeholder="Search..." autocomplete="off" />
                                    </div>
                                    <select id="charge-datatable-length" class="form-select w-auto">
                                        <option value="-1">All</option>
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <div class="card-toolbar">
                                    <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                                        @if($canWrite)
                                            <button type="button"
                                                    class="btn btn-light-primary me-3"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#charge-modal"
                                                    id="add-charge">
                                                <i class="ki-outline ki-plus fs-2"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-9">
                                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="charge-table">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th>Charge</th>
                                            <th>Value Type</th>
                                            <th>Is Variable</th>
                                            <th>Charge Value</th>
                                            <th>Automatic Application</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  
        </div>
    </div>

    <div id="floor-plan-modal" class="modal fade" tabindex="-1" aria-labelledby="floor-plan-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Attribute</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="attribute_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="attribute_id">
                                        Attribute
                                    </label>

                                    <select id="attribute_id" name="attribute_id[]" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false"></select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="attribute_form" class="btn btn-primary" id="submit-attribute">Add</button>
                </div>
            </div>
        </div>
    </div>

    <div id="discount-modal" class="modal fade" tabindex="-1" aria-labelledby="discount-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Bill of Materials</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="discount_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="discount_shop_register_id">
                                        Component Product
                                    </label>

                                    <select id="discount_shop_register_id" name="discount_shop_register_id" class="form-select" data-control="select2" data-allow-clear="false"></select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="quantity">
                                        Required Quantity
                                    </label>

                                    <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="fv-row">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="stock_policy">
                                        Stock Policy
                                    </label>

                                    <select id="stock_policy" name="stock_policy" class="form-select" data-control="select2" data-hide-search="true">
                                        <option value="">--</option>
                                        <option value="Strict">Strict</option>
                                        <option value="Allow Negative">Allow Negative</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="discount_form" class="btn btn-primary" id="submit-discount">Add</button>
                </div>
            </div>
        </div>
    </div>

    <div id="charge-modal" class="modal fade" tabindex="-1" aria-labelledby="charge-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add-ons</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="charge_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="charge_shop_register_id">
                                        Add-on Product
                                    </label>

                                    <select id="charge_shop_register_id" name="charge_shop_register_id" class="form-select" data-control="select2" data-allow-clear="false"></select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="fv-row">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="max_quantity">
                                        Max Quantity
                                    </label>

                                    <input type="number" class="form-control" id="max_quantity" name="max_quantity" min="0.01" step="0.01">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="charge_form" class="btn btn-primary" id="submit-charge">Add</button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.log-notes-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>


    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush