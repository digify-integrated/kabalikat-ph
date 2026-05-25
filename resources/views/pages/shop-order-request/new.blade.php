@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Shop Order Request Details</h5>
        </div>
        <div class="card-body">
            <form id="shop_order_request_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="shop_order_id">
                                Shop Order
                            </label>

                            <select id="shop_order_id" name="shop_order_id" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="">--</option>
                            </select>
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="request_type">
                                Request Type
                            </label>

                            <select id="request_type" name="request_type" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="Void">Void</option>
                                <option value="Refund">Refund</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                     <div class="fv-row mb-4">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="request_type">
                            Request Reason
                        </label>
                        
                        <textarea id="request_reason" name="request_reason" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="shop_order_request_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush