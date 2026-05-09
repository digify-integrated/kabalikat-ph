@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Discount Type Details</h5>
        </div>
        <div class="card-body">
            <form id="discount_type_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="discount_type_name">
                                Discount Type
                            </label>

                            <input type="text" class="form-control" id="discount_type_name" name="discount_type_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="value_type">
                                Value Type
                            </label>

                            <select id="value_type" name="value_type" class="form-select" data-control="select2" data-allow-clear="false">
                                <option value="">--</option>
                                <option value="Percentage">Percentage</option>
                                <option value="Fixed Amount">Fixed Amount</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="discount_value">
                                Discount Value
                            </label>

                            <input type="number" class="form-control" id="discount_value" name="discount_value" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="is_variable">
                                Is Variable?
                            </label>

                            <select id="is_variable" name="is_variable" class="form-select" data-control="select2" data-allow-clear="false">
                                <option value="">--</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="application_order">
                                Application Order
                            </label>

                            <select id="application_order" name="application_order" class="form-select" data-control="select2" data-allow-clear="false">
                                <option value="">--</option>
                                <option value="Before Tax">Before Tax</option>
                                <option value="After Tax">After Tax</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="is_vat_exempt">
                                Is VAT Exempt
                            </label>

                            <select id="is_vat_exempt" name="is_vat_exempt" class="form-select" data-control="select2" data-allow-clear="false">
                                <option value="">--</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="discount_type_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush