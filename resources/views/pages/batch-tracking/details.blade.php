@extends('layouts.module')

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-10">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Batch Tracking Details</h3>
                    </div>
                    @if($canDelete)
                        <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="batch-tracking px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-batch-tracking">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-body border-top p-9">
                    <form id="batch_tracking_form" method="post" action="#" novalidate>
                        @csrf

                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="product_id">
                                        Product
                                    </label>

                                    <select id="product_id" name="product_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option>--</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="warehouse_id">
                                        Warehouse
                                    </label>

                                    <select id="warehouse_id" name="warehouse_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option>--</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="batch_number">
                                        Batch / Lot Number
                                    </label>

                                    <input type="text" class="form-control" id="batch_number" name="batch_number" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="quantity">
                                        Quantity
                                    </label>

                                    <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01" @disabled(!$canWrite)>
                                </div>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="cost_per_unit">
                                        Cost per Unit
                                    </label>

                                    <input type="number" class="form-control" id="cost_per_unit" name="cost_per_unit" min="0.01" step="0.01" @disabled(!$canWrite)>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="expiration_date">
                                        Expiration Date
                                    </label>

                                    <input type="text" class="form-control" id="expiration_date" name="expiration_date" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="received_date">
                                        Received Date
                                    </label>

                                    <input type="text" class="form-control" id="received_date" name="received_date" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if($canWrite)
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" form="batch_tracking_form" id="submit-data">
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