@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Stock Transfer Details</h5>
        </div>
        <div class="card-body">
            <form id="stock_transfer_form" method="post" action="#" novalidate>
                @csrf

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_level_from_id">
                        Stock From
                    </label>
                    <div class="col-lg-10">
                        <select id="stock_level_from_id" name="stock_level_from_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_level_to_id">
                        Stock To
                    </label>
                    <div class="col-lg-10">
                        <select id="stock_level_to_id" name="stock_level_to_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="quantity">
                        Quantity
                    </label>
                    <div class="col-lg-10">
                        <input type="number" class="form-control" id="quantity" name="quantity" step="0.01">
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_transfer_reason_id">
                        Transfer Reason
                    </label>
                    <div class="col-lg-10">
                        <select id="stock_transfer_reason_id" name="stock_transfer_reason_id" class="form-select" data-control="select2" data-allow-clear="false">
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
            <button type="submit" form="stock_transfer_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush