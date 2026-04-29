@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Stock Batch Details</h5>
        </div>
        <div class="card-body">
            <form id="stock_batch_form" method="post" action="#" novalidate>
                @csrf

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="product_id">
                        Product
                    </label>
                    <div class="col-lg-10">
                        <select id="product_id" name="product_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="warehouse_id">
                        Warehouse
                    </label>
                    <div class="col-lg-10">
                        <select id="warehouse_id" name="warehouse_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="batch_number">
                        Batch / Lot Number
                    </label>
                    <div class="col-lg-10">
                        <input type="text" class="form-control" id="batch_number" name="batch_number" maxlength="100" autocomplete="off">
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="quantity">
                        Quantity
                    </label>
                    <div class="col-lg-10">
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01">
                    </div>
                </div>
                
                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="cost_per_unit">
                        Cost per Unit
                    </label>
                    <div class="col-lg-10">
                        <input type="number" class="form-control" id="cost_per_unit" name="cost_per_unit" min="0.01" step="0.01">
                    </div>
                </div>
                
                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label fw-semibold fs-6" for="expiration_date">
                        Expiration Date
                    </label>
                    <div class="col-lg-10">
                        <input type="text" class="form-control" id="expiration_date" name="expiration_date" autocomplete="off">
                    </div>
                </div>
                
                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="received_date">
                        Received Date
                    </label>
                    <div class="col-lg-10">
                        <input type="text" class="form-control" id="received_date" name="received_date" autocomplete="off">
                    </div>
                </div>

                <div class="row">
                    <label class="col-lg-2 col-form-label fw-semibold fs-6" for="remarks">
                        Remarks
                    </label>
                    <div class="col-lg-10">
                        <textarea class="form-control" id="remarks" name="remarks" maxlength="200" rows="3"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="stock_batch_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush