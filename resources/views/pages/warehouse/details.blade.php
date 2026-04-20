@extends('layouts.module')

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
    @endphp

    <div class="d-flex flex-column flex-lg-row">
        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <div class="card card-flush">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Warehouse Details</h3>
                    </div>

                    @if($canDelete)
                       <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="menu-item px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-warehouse">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <form id="warehouse_form" method="post" action="#" novalidate>
                    @csrf
                    <div class="card-body border-top p-9">
                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="warehouse_name">
                                Display Name
                            </label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="warehouse_name" name="warehouse_name" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-semibold fs-6" for="contact_person">
                                Contact Person
                            </label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="contact_person" name="contact_person" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-semibold fs-6" for="warehouse_type_id">
                                Warehouse Type
                            </label>
                            <div class="col-lg-9">
                                <select id="warehouse_type_id" name="warehouse_type_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                    <option>--</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="warehouse_name">
                                Address
                            </label>
                            <div class="col-lg-9">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <input type="text" class="form-control" id="address" name="address" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                    </div>
                                    <div class="col-lg-6">
                                        <select id="city_id" name="city_id" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                            <option>--</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="warehouse_status">
                                Status
                            </label>
                            <div class="col-lg-9">
                                <select id="warehouse_status" name="warehouse_status" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                    <option value="">--</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-semibold fs-6" for="phone">
                                Phone
                            </label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="phone" name="phone" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-semibold fs-6" for="telephone">
                                Telephone
                            </label>
                            <div class="col-lg-9">
                                <input type="text" class="form-control" id="telephone" name="telephone" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label fw-semibold fs-6" for="email">
                                Email
                            </label>
                            <div class="col-lg-9">
                                <input type="email" class="form-control" id="email" name="email" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
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