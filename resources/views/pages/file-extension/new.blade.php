@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">File Extension Details</h5>
        </div>
        <div class="card-body">
            <form id="file_extension_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="file_extension_name">
                                File Extension
                            </label>

                            <input type="text" class="form-control" id="file_extension_name" name="file_extension_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="file_extension">
                                Extension
                            </label>

                            <input type="text" class="form-control" id="file_extension" name="file_extension" maxlength="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="file_type_id">
                                File Type
                            </label>

                            <select id="file_type_id" name="file_type_id" class="form-select" data-control="select2" data-allow-clear="false">
                                <option>--</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="file_extension_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush