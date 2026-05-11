@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
    @endphp

    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#overview_tab" aria-selected="true" role="tab">Overview</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#floor_plan_tables_tab" aria-selected="false" role="tab">Tables</a>
            </li>
            <li class="nav-item ms-auto">
                @if($canDelete)
                    <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        Actions
                        <i class="ki-outline ki-down fs-5 ms-1"></i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                        <div class="menu-item px-3">
                            <a href="javascript:void(0);" class="menu-link px-3" id="delete-floor-plan">
                                Delete
                            </a>
                        </div>
                    </div>
                @endif
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-content" id="user_account_tab_content">
                <div class="tab-pane fade active show" id="overview_tab" role="tabpanel">
                    <div class="card mb-10">
                        <div class="card-header border-0">
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">Floor Plan Details</h3>
                            </div>                            
                        </div>
                        <div class="card-body border-top p-9">
                            <form id="floor_plan_form" method="post" action="#" novalidate>
                                @csrf

                                <div class="row mb-6">
                                    <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="floor_plan_name">
                                        Display Name
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="text" class="form-control" id="floor_plan_name" name="floor_plan_name" maxlength="100" autocomplete="off" @disabled(!$canWrite)>
                                    </div>
                                </div>
                            </form>
                        </div>

                        @if($canWrite)
                            <div class="card-footer d-flex justify-content-end py-6 px-9">
                                <button type="submit" class="btn btn-primary" form="floor_plan_form" id="submit-data">
                                    Save Changes
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="tab-pane fade" id="floor_plan_tables_tab" role="tabpanel">
                    <div class="card">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <div class="d-flex align-items-center position-relative my-1 me-3">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="floor-plan-tables-datatable-search" placeholder="Search..." autocomplete="off" />
                                </div>
                                <select id="floor-plan-tables-datatable-length" class="form-select w-auto">
                                    <option value="-1">All</option>
                                    <option value="5">5</option>
                                    <option value="10" selected>10</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="card-toolbar">
                                @if($canWrite)
                                    <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                                        <button type="button"
                                            class="btn btn-light-primary me-3"
                                            data-bs-toggle="modal"
                                            data-bs-target="#floor-plan-tables-modal"
                                            id="add-floor-plan-tables">
                                            <i class="ki-outline ki-plus fs-2"></i> Add
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body pt-9">
                            <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="floor-plan-tables-table">
                                <thead>
                                    <tr class="fw-semibold fs-6 text-gray-800">
                                        <th>Table No</th>
                                        <th>Seats</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody class="fw-semibold text-gray-600"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="floor-plan-tables-modal" class="modal fade" tabindex="-1" aria-labelledby="floor-plan-tables-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Floor Plan Tables</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="floor_plan_tables_form" method="post" action="#">
                        @csrf

                        <input type="hidden" id="floor_plan_table_id" name="floor_plan_table_id">

                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="table_number">
                                Table Number
                            </label>
                            <div class="col-lg-9">
                                <input type="number" class="form-control" id="table_number" name="table_number" min="1" step="1">
                            </div>
                        </div>
                        <div class="row mb-6">
                            <label class="col-lg-3 col-form-label required fw-semibold fs-6" for="seats">
                                Seats
                            </label>
                            <div class="col-lg-9">
                                <input type="number" class="form-control" id="seats" name="seats" min="1" step="1">
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="floor_plan_tables_form" class="btn btn-primary" id="submit-floor-plan-tables">Add</button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.log-notes-modal')
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>

    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush