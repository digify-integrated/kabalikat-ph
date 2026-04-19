@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Unit Details</h5>
        </div>
        <div class="card-body">
            <form id="unit_form" method="post" action="#" novalidate>
                @csrf
                <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="unit_name">
                            Unit
                        </label>

                        <input type="text" class="form-control" id="unit_name" name="unit_name" maxlength="100" autocomplete="off">
                    </div>
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="abbreviation">
                            Abbreviation
                        </label>

                        <input type="text" class="form-control" id="abbreviation" name="abbreviation" maxlength="50" autocomplete="off">
                    </div>
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="unit_type_id">
                            Unit Type
                        </label>

                        <select id="unit_type_id" name="unit_type_id" class="form-select" data-control="select2" data-allow-clear="false">
                            <option>--</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="unit_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush