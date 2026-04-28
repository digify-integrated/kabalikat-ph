@extends('layouts.module')

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
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_level_id">
                                Stock
                            </label>
                            <div class="col-lg-10">
                                <select id="stock_level_id" name="stock_level_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')>
                                    <option>--</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="transfer_type">
                                Transfer Type
                            </label>
                            <div class="col-lg-10">
                                <select id="transfer_type" name="transfer_type" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')>
                                    <option value="">--</option>
                                    <option value="Add Stock">Add Stock</option>
                                    <option value="Remove Stock">Remove Stock</option>
                                    <option value="Set Exact Stock">Set Exact Stock</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="quantity">
                                Quantity
                            </label>
                            <div class="col-lg-10">
                                <input type="number" class="form-control" id="quantity" name="quantity" step="0.01" @disabled(!$canWrite || $stockTransfer->stock_transfer_status !== 'Draft')>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_transfer_reason_id">
                                Transfer Reason
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

    @include('partials.log-notes-modal')
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush