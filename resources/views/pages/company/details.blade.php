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
                            id="company_thumbnail"
                            style="background-image: url('{{ asset('assets/media/default/default-company-logo.png') }}')"
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
                                <input type="file" id="company_logo" name="company_logo" accept=".png, .jpg, .jpeg">
                            </label>
                        @endif
                    </div>

                    <div class="form-text mt-5">
                        Set the company image. Only *.png, *.jpg and *.jpeg image files are accepted.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <div class="card card-flush">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Company Details</h3>
                    </div>

                    @if($canDelete)
                       <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="menu-item px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-company">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <form id="company_form" method="post" action="#" novalidate>
                    @csrf
                    <div class="card-body border-top p-9">
                        <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="company_name">
                                        Display Name
                                    </label>

                                    <input type="text" class="form-control" id="company_name" name="company_name" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="address">
                                        Address
                                    </label>

                                    <input type="text" class="form-control" id="address" name="address" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="city_id">
                                        City
                                    </label>

                                    <select id="city_id" name="city_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option>--</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="tax_id">
                                        Tax ID
                                    </label>

                                    <input type="text" class="form-control" id="tax_id" name="tax_id" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="currency_id">
                                        Currency
                                    </label>

                                    <select id="currency_id" name="currency_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option>--</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="phone">
                                        Phone
                                    </label>

                                    <input type="text" class="form-control" id="phone" name="phone" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                        </div>
                        <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="telephone">
                                        Telephone
                                    </label>

                                    <input type="text" class="form-control" id="telephone" name="telephone" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="email">
                                        Email
                                    </label>

                                    <input type="email" class="form-control" id="email" name="email" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="website">
                                        Website
                                    </label>

                                    <input type="text" class="form-control" id="website" name="website" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($canWrite)
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
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