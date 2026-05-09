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
                        <h3 class="fw-bold m-0">Discount Type Details</h3>
                    </div>
                    @if($canDelete)
                        <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="menu-item px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-discount-type">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-body border-top p-9">
                    <form id="discount_type_form" method="post" action="#" novalidate>
                        @csrf

                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="discount_type_name">
                                        Discount Type
                                    </label>

                                    <input type="text" class="form-control" id="discount_type_name" name="discount_type_name" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="value_type">
                                        Value Type
                                    </label>

                                    <select id="value_type" name="value_type" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option value="">--</option>
                                        <option value="Percentage">Percentage</option>
                                        <option value="Fixed Amount">Fixed Amount</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="discount_value">
                                        Discount Value
                                    </label>

                                    <input type="number" class="form-control" id="discount_value" name="discount_value" min="0" step="0.01" @disabled(!$canWrite)>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="is_variable">
                                        Is Variable?
                                    </label>

                                    <select id="is_variable" name="is_variable" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option value="">--</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="application_order">
                                        Application Order
                                    </label>

                                    <select id="application_order" name="application_order" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option value="">--</option>
                                        <option value="Before Tax">Before Tax</option>
                                        <option value="After Tax">After Tax</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="fv-row mb-4">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="is_vat_exempt">
                                        Is VAT Exempt
                                    </label>

                                    <select id="is_vat_exempt" name="is_vat_exempt" class="form-select" data-control="select2" data-allow-clear="false" @disabled(!$canWrite)>
                                        <option value="">--</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if($canWrite)
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" form="discount_type_form" id="submit-data">
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