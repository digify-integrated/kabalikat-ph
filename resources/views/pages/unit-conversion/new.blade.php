@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Unit Conversion Details</h5>
        </div>
        <div class="card-body">
            <form id="unit_conversion_form" method="post" action="#" novalidate>
                @csrf
                <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="from_unit_id">
                            From
                        </label>

                        <select id="from_unit_id" name="from_unit_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="to_unit_id">
                            To
                        </label>

                        <select id="to_unit_id" name="to_unit_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="conversion_factor">
                            Conversion Factor
                        </label>

                        <input type="number" class="form-control" id="conversion_factor" name="conversion_factor" min="0.01" step="0.01">
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="unit_conversion_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush