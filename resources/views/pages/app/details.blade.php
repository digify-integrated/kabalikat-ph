@extends('layouts.module')

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
    @endphp

    <div class="d-flex flex-column flex-lg-row">
        <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
            <div class="card card-flush">
                <div class="card-body text-center">
                    <div class="image-input image-input-outline" data-kt-image-input="true">
                        <div
                            class="image-input-wrapper w-125px h-125px"
                            id="app_thumbnail"
                            style="background-image: url('{{ asset('assets/media/default/app-logo.png') }}')"
                        ></div>

                        @if($canWrite)
                            <label
                                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="change"
                                data-bs-toggle="tooltip"
                                aria-label="Change logo"
                                data-bs-original-title="Change logo"
                            >
                                <i class="ki-outline ki-pencil fs-7"></i>
                                <input type="file" id="app_logo" name="app_logo" accept=".png, .jpg, .jpeg">
                            </label>
                        @endif
                    </div>

                    <div class="form-text mt-5">
                        Set the app module image. Only *.png, *.jpg and *.jpeg image files are accepted.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <div class="card card-flush">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">App Module Details</h3>
                    </div>

                    @if($canDelete)
                       <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="menu-item px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-app-module">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <form id="app_form" method="post" action="#" novalidate>
                    @csrf
                    <div class="card-body border-top p-9">
                        <div class="row row-cols-1 row-cols-sm-4 rol-cols-md-3 row-cols-lg-4">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="app_name">
                                        Display Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="app_name"
                                        name="app_name"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="navigation_menu_id">
                                        Default Page
                                    </label>

                                    <select
                                        id="navigation_menu_id"
                                        name="navigation_menu_id"
                                        class="form-select"
                                        data-control="select2"
                                        data-allow-clear="false"
                                        @disabled(!$canWrite)
                                    >
                                        <option>--</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="app_version">
                                        App Version
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="app_version"
                                        name="app_version"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="order_sequence">
                                        Order Sequence
                                    </label>

                                    <input
                                        type="number"
                                        class="form-control"
                                        id="order_sequence"
                                        name="order_sequence"
                                        min="0"
                                        max="1000"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="app_description">
                                Description
                            </label>

                            <textarea
                                class="form-control"
                                id="app_description"
                                name="app_description"
                                maxlength="500"
                                rows="3"
                                @disabled(!$canWrite)
                            ></textarea>
                        </div>
                    </div>

                    @if($canWrite)
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">
                                Discard
                            </button>

                            <button type="submit" class="btn btn-primary" id="submit-data">
                                Save Changes
                            </button>
                        </div>
                    @endif
                </form>
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