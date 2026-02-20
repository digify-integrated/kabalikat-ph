@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">File Type Details</h5>
        </div>
        <div class="card-body">
            <form id="file_type_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="fv-row mb-4">
                    <label class="fs-6 fw-semibold required form-label mt-3" for="file_type_name">
                        File Type
                    </label>

                    <input type="text" class="form-control" id="file_type_name" name="file_type_name" maxlength="100" autocomplete="off">
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="file_type_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush