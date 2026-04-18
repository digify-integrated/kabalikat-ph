@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Attribute Details</h5>
        </div>
        <div class="card-body">
            <form id="attribute_form" method="post" action="#" novalidate>
                @csrf
                <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-2 row-cols-lg-2">
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="attribute_name">
                            Display Name
                        </label>

                        <input type="text" class="form-control" id="attribute_name" name="attribute_name" maxlength="100" autocomplete="off">
                    </div>
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="selection_type">
                            Selection Type
                        </label>

                        <select id="selection_type" name="selection_type" class="form-select" data-control="select2" data-allow-clear="false">
                            <option value="">--</option>
                            <option value="Single">Single</option>
                            <option value="Multiple">Multiple</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="attribute_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush