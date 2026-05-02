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
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="reference_number">
                        Reference Number
                    </label>
                    <div class="col-lg-10">
                        <input type="text" class="form-control" id="reference_number" name="reference_number" maxlength="100" autocomplete="off">
                    </div>
                </div>

                <div class="row mb-6">
                    <label class="col-lg-2 col-form-label required fw-semibold fs-6" for="stock_adjustment_reason_id">
                        Stock Adjustment Reason
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