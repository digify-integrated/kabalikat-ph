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
            <div class="card card-flush mb-6">
                <div class="card-body text-center">
                    <div class="image-input image-input-outline" data-kt-image-input="true">
                        <div
                            class="image-input-wrapper w-125px h-125px"
                            id="product_image_thumbnail"
                            style="background-image: url('{{ asset('assets/media/default/upload-placeholder.png') }}')"
                        ></div>

                        @if($canWrite)
                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" aria-label="Change image" data-bs-original-title="Change image">
                                <i class="ki-outline ki-pencil fs-7"></i>
                                <input type="file" id="product_image" name="product_image" accept=".png, .jpg, .jpeg">
                            </label>
                        @endif
                    </div>

                    <div class="form-text mt-5">
                        Set the product image. Only *.png, *.jpg and *.jpeg image files are accepted.
                    </div>
                </div>
            </div>
            <div class="card mb-5 mb-xl-8">
                <div class="card-header border-0">
                    <div class="card-title">
                        <h3 class="fw-bold m-0">Product Settings</h3>
                    </div>
                </div>
                
                <div class="card-body pt-2">                
                    <div class="py-2">
                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Track Inventory</div>
                                    <div class="fs-7 fw-semibold text-muted">
                                        Enable this to automatically monitor stock levels and prevent overselling.
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input product-setting" data-setting="track-inventory" type="checkbox" id="track-inventory">
                                    <span class="form-check-label"></span>
                                </label>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-5"></div>

                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Add-On Item</div>
                                    <div class="fs-7 fw-semibold text-muted">
                                        Mark this if the product is an optional extra that can be added to a main item.
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input product-setting" data-setting="is-addon" type="checkbox" id="is-addon">
                                    <span class="form-check-label"></span>
                                </label>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-5"></div>

                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Batch Tracking</div>
                                    <div class="fs-7 fw-semibold text-muted">
                                        Enable this to track items by batch or lot number for better inventory control.
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input product-setting" data-setting="batch-tracking" type="checkbox" id="batch-tracking">
                                    <span class="form-check-label"></span>
                                </label>
                            </div>
                        </div>

                        <div class="separator separator-dashed my-5"></div>

                        <div class="d-flex flex-stack">
                            <div class="d-flex">
                                <div class="d-flex flex-column">
                                    <div class="fs-5 text-gray-900 fw-bold">Expiration Tracking</div>
                                    <div class="fs-7 fw-semibold text-muted">
                                        Enable this to monitor expiration dates and avoid selling expired products.
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                    <input class="form-check-input product-setting" data-setting="expiration-tracking" type="checkbox" id="expiration-tracking">
                                    <span class="form-check-label"></span>
                                </label>
                            </div>
                        </div>
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
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#product_attribute_tab" aria-selected="false" role="tab">Attributes & Variations</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#product_bom_tab" aria-selected="false" role="tab">Bill of Materials</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#product_addons_tab" aria-selected="false" role="tab">Add-Ons</a>
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
                <div class="tab-content" id="product_account_tab_content">
                    <div class="tab-pane fade active show" id="overview_tab" role="tabpanel">
                        <div class="card mb-5">
                            <form id="product_form" method="post" action="#" novalidate>
                                @csrf
                                <div class="card-header border-0">
                                    <div class="card-title m-0">
                                        <h3 class="fw-bold m-0">Product Details</h3>
                                    </div>
                                </div>
                                <div class="card-body border-top p-9">
                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="product_name">
                                            Product Name
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" id="product_name" name="product_name" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label fw-semibold fs-6" for="sku">
                                            SKU
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" id="sku" name="sku" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label fw-semibold fs-6" for="barcode">
                                            Barcode
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="text" class="form-control" id="barcode" name="barcode" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="product_type">
                                            Product Type
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="product_type" name="product_type" class="form-select" data-control="select2" data-hide-search="true" @disabled(!$canWrite)>
                                                <option value="Goods">Goods</option>
                                                <option value="Service">Service</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="product_status">
                                            Product Status
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="product_status" name="product_status" class="form-select" data-control="select2" data-hide-search="true" @disabled(!$canWrite)>
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="tax_classification">
                                            Tax Classification
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="tax_classification" name="tax_classification" class="form-select" data-control="select2" data-hide-search="true" @disabled(!$canWrite)>
                                                <option value="Vatable">Vatable</option>
                                                <option value="VAT Exempt">VAT Exempt</option>
                                                <option value="Zero Rated">Zero Rated</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="base_price">
                                            Base Price
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="number" class="form-control" id="base_price" name="base_price" min="0" step="0.01" @disabled(!$canWrite)>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="cost_price">
                                            Cost Price
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="number" class="form-control" id="cost_price" name="cost_price" min="0" step="0.01" @disabled(!$canWrite)>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="base_unit_id">
                                            Base Unit
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="base_unit_id" name="base_unit_id" class="form-select" data-control="select2" @disabled(!$canWrite)>
                                                <option>--</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="inventory_flow">
                                            Inventory Flow
                                        </label>
                                        <div class="col-lg-9">
                                            <select id="inventory_flow" name="inventory_flow" class="form-select" data-control="select2" data-hide-search="true" @disabled(!$canWrite)>
                                                <option value="FIFO">FIFO</option>
                                                <option value="FEFO">FEFO</option>
                                                <option value="LIFO">LIFO</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-6">
                                        <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="reorder_level">
                                            Reorder Level
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" step="0.01" @disabled(!$canWrite)>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <label class="col-lg-3 col-form-label fw-semibold fs-6" for="product_description">
                                            Description
                                        </label>
                                        <div class="col-lg-9">
                                            <textarea class="form-control" id="product_description" name="product_description" maxlength="200" rows="3" @disabled(!$canWrite)></textarea>
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
                    <div class="tab-pane fade" id="product_attribute_tab" role="tabpanel">
                        <div class="card mb-5">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1 me-3">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="attribute-datatable-search" placeholder="Search..." autocomplete="off" />
                                    </div>
                                    <select id="attribute-datatable-length" class="form-select w-auto">
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
                                                    data-bs-target="#attribute-modal"
                                                    id="add-attribute">
                                                <i class="ki-outline ki-plus fs-2"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-9">
                                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="attribute-table">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th>Attribute</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card mb-5">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1 me-3">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="variation-datatable-search" placeholder="Search..." autocomplete="off" />
                                    </div>
                                    <select id="variation-datatable-length" class="form-select w-auto">
                                        <option value="-1">All</option>
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body pt-9">
                                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="variation-table">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th>Variation</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="product_bom_tab" role="tabpanel">
                        <div class="card mb-5">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1 me-3">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="bom-datatable-search" placeholder="Search..." autocomplete="off" />
                                    </div>
                                    <select id="bom-datatable-length" class="form-select w-auto">
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
                                        @if(($canAssignUserAccount ?? false) === true)
                                            <button type="button"
                                                    class="btn btn-light-primary me-3"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#bom-modal"
                                                    id="add-bom">
                                                <i class="ki-outline ki-plus fs-2"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-9">
                                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="bom-table">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th>Component</th>
                                            <th>Quantity</th>
                                            <th>Stock Policy</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="fw-semibold text-gray-600"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="product_addons_tab" role="tabpanel">
                        <div class="card mb-5">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div class="d-flex align-items-center position-relative my-1 me-3">
                                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="addons-datatable-search" placeholder="Search..." autocomplete="off" />
                                    </div>
                                    <select id="addons-datatable-length" class="form-select w-auto">
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
                                        @if(($canAssignUserAccount ?? false) === true)
                                            <button type="button"
                                                    class="btn btn-light-primary me-3"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addons-modal"
                                                    id="add-addons">
                                                <i class="ki-outline ki-plus fs-2"></i> Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-9">
                                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="addons-table">
                                    <thead>
                                        <tr class="fw-semibold fs-6 text-gray-800">
                                            <th>Add-On</th>
                                            <th>Max Quantity</th>
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

    <div id="role-assignment-modal" class="modal fade" tabindex="-1" aria-labelledby="role-assignment-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Assign Role</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="role_assignment_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <select multiple="multiple" size="20" id="role_id" name="role_id[]"></select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="role_assignment_form" class="btn btn-primary" id="submit-assignment">Assign</button>
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