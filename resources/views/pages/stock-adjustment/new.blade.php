@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Stock Adjustment Details</h5>
        </div>
        <div class="card-body">
            <form id="stock_adjustment_form" method="post" action="#" novalidate>
                @csrf

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_level_id">
                        Stock
                    </label>
                    <div class="col-lg-10">
                        <select id="stock_level_id" name="stock_level_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="adjustment_type">
                        Adjustment Type
                    </label>
                    <div class="col-lg-10">
                        <select id="adjustment_type" name="adjustment_type" class="form-select" data-control="select2" data-allow-clear="false">
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
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01">
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_adjustment_reason_id">
                        Adjustment Reason
                    </label>
                    <div class="col-lg-10">
                        <select id="stock_adjustment_reason_id" name="stock_adjustment_reason_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
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
            <button type="submit" form="stock_adjustment_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush