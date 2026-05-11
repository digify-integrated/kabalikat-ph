@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Shop Register Details</h5>
        </div>
        <div class="card-body">
            <form id="shop_register_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="shop_register_name">
                                Shop Register Name
                            </label>

                            <input type="text" class="form-control" id="shop_register_name" name="shop_register_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="company_id">
                                Company
                            </label>

                            <select id="company_id" name="company_id" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="">--</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="is_restaurant">
                                Is Restaurant?
                            </label>

                            <select id="is_restaurant" name="is_restaurant" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="shop_register_status">
                                Shop Register Status
                            </label>

                            <select id="shop_register_status" name="shop_register_status" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="shop_register_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush