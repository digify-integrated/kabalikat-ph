@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Batch Tracking Details</h5>
        </div>
        <div class="card-body">
            <form id="batch_tracking_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="product_id">
                                Product
                            </label>

                            <select id="product_id" name="product_id" class="form-select" data-control="select2" data-allow-clear="false">
                                <option>--</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="warehouse_id">
                                Warehouse
                            </label>

                            <select id="warehouse_id" name="warehouse_id" class="form-select" data-control="select2" data-allow-clear="false">
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

                            <input type="text" class="form-control" id="batch_number" name="batch_number" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="quantity">
                                Quantity
                            </label>

                            <input type="number" class="form-control" id="quantity" name="quantity" min="0.01" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="cost_per_unit">
                                Cost per Unit
                            </label>

                            <input type="number" class="form-control" id="cost_per_unit" name="cost_per_unit" min="0.01" step="0.01">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold form-label mt-3" for="expiration_date">
                                Expiration Date
                            </label>

                            <input type="text" class="form-control" id="expiration_date" name="expiration_date" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="received_date">
                                Received Date
                            </label>

                            <input type="text" class="form-control" id="received_date" name="received_date" autocomplete="off">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="batch_tracking_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush