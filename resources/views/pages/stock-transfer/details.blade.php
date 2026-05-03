@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
        
        $approveStockTransfer = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(6, Auth::id());

        $stockTransfer = DB::table('stock_transfer')
            ->where('id', $detailsId)
            ->first();            
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-10">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Stock Transfer Details</h3>
                    </div>
                    @if($canDelete || (($approveStockTransfer ?? false) === true  && $stockTransfer->stock_transfer_status === 'For Approval') || $stockTransfer->stock_transfer_status === 'Draft')
                        <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            @if($canDelete)
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="delete-stock-transfer">
                                        Delete
                                    </a>
                                </div>
                            @endif

                            @if($stockTransfer->stock_transfer_status === 'Draft')
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="for-approval-stock-transfer">
                                        For Approval
                                    </a>
                                </div>
                            @endif

                            @if($stockTransfer->stock_transfer_status === 'Draft' || $stockTransfer->stock_transfer_status === 'For Approval')
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="cancel-stock-transfer">
                                        Cancel
                                    </a>
                                </div>
                            @endif

                            @if(($approveStockTransfer ?? false) === true && $stockTransfer->stock_transfer_status === 'For Approval')
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="approve-stock-transfer">
                                        Approve
                                    </a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="set-to-draft-stock-transfer">
                                        Set to Draft
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-body border-top p-9">
                    <form id="stock_transfer_form" method="post" action="#" novalidate>
                        @csrf

                        <div class="row mb-6">
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="reference_number">
                                Reference Number
                            </label>
                            <div class="col-lg-10">
                                <input type="text" class="form-control" id="reference_number" name="reference_number" maxlength="100" autocomplete="off" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="from_warehouse_id">
                                From Warehouse
                            </label>
                            <div class="col-lg-10">
                                <select id="from_warehouse_id" name="from_warehouse_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')>
                                    <option>--</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="to_warehouse_id">
                                To Warehouse
                            </label>
                            <div class="col-lg-10">
                                <select id="to_warehouse_id" name="to_warehouse_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')>
                                    <option>--</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_transfer_reason_id">
                                Stock Transfer Reason
                            </label>
                            <div class="col-lg-10">
                                <select id="stock_transfer_reason_id" name="stock_transfer_reason_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')>
                                    <option>--</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <label class="col-lg-2 col-form-label fw-semibold fs-6" for="remarks">
                                Remarks
                            </label>
                            <div class="col-lg-10">
                                <textarea class="form-control" id="remarks" name="remarks" maxlength="200" rows="3" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                @if($canWrite && $stockTransfer->stock_transfer_status === 'Draft')
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" form="stock_transfer_form" id="submit-data">
                            Save Changes
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-5">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1 me-3">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="stock-transfer-items-datatable-search" placeholder="Search..." autocomplete="off" />
                        </div>
                        <select id="stock-transfer-items-datatable-length" class="form-select w-auto">
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
                            @if($canWrite && $stockTransfer->stock_transfer_status === 'Draft')
                                <button type="button"
                                    class="btn btn-light-primary me-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#stock-transfer-items-modal"
                                    id="add-stock-transfer-items">
                                    <i class="ki-outline ki-plus fs-2"></i> Add
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body pt-9">
                    <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="stock-transfer-items-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Product</th>
                                <th>Qty</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="stock-transfer-items-modal" class="modal fade" tabindex="-1" aria-labelledby="stock-transfer-items" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Stock Transfer Item</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="stock_transfer_items_form" method="post" action="#">
                        @csrf
                        
                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="stock_level_id">
                                        Stock
                                    </label>

                                    <select id="stock_level_id" name="stock_level_id" class="form-select" data-control="select2" data-allow-clear="false"></select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="quantity">
                                        Transfer Quantity
                                    </label>

                                    <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="stock_transfer_items_form" class="btn btn-primary" id="submit-stock-transfer-items">Add</button>
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