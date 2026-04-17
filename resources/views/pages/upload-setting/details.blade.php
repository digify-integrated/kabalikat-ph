@extends('layouts.module')

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-10">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Upload Setting Details</h3>
                    </div>
                    @if($canDelete)
                        <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="upload-setting px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-upload-setting">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-body border-top p-9">
                    <form id="upload_setting_form" method="post" action="#" novalidate>
                        @csrf

                        <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="upload_setting_name">
                                        Upload Setting
                                    </label>

                                    <input type="text" class="form-control" id="upload_setting_name" name="upload_setting_name" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="upload_setting_description">
                                        Description
                                    </label>

                                    <input type="text" class="form-control" id="upload_setting_description" name="upload_setting_description" maxlength="200" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="max_file_size">
                                        Max File Size
                                    </label>
                                    
                                    <div class="input-group mb-5">
                                        <input type="number" class="form-control" id="max_file_size" name="max_file_size" min="1" step="1" @disabled(!$canWrite)>
                                        <span class="input-group-text">kb</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                    <div class="col">
                        <div class="fv-row">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="upload_setting_name">
                                Allowed File Extension
                            </label>

                            <select id="file_extension_id" name="file_extension_id[]" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)></select>
                        </div>
                    </div>
                </div>
                    </form>
                </div>

                @if($canWrite)
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" form="upload_setting_form" id="submit-data">
                            Save Changes
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include('partials.log-notes-modal')
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush