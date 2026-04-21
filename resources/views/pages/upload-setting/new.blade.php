@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Upload Setting Details</h5>
        </div>
        <div class="card-body">
            <form id="upload_setting_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="upload_setting_name">
                                Upload Setting
                            </label>

                            <input type="text" class="form-control" id="upload_setting_name" name="upload_setting_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="upload_setting_description">
                                Description
                            </label>

                            <input type="text" class="form-control" id="upload_setting_description" name="upload_setting_description" maxlength="200" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="max_file_size">
                                Max File Size
                            </label>
                            
                            <div class="input-group mb-5">
                                <input type="number" class="form-control" id="max_file_size" name="max_file_size" min="1" step="1">
                                <span class="input-group-text">kb</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="upload_setting_name">
                                Allowed File Extension
                            </label>

                            <select id="file_extension_id" name="file_extension_id[]" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false"></select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="upload_setting_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush