@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;
        $canAssign = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(5, Auth::id());
    @endphp

    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-10">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Navigation Menu Details</h3>
                    </div>
                    @if($canDelete)
                        <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="navigation-menu px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-navigation-menu">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <form id="navigation_menu_form" method="post" action="#" novalidate>
                        @csrf

                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="navigation_menu_name">
                                        Display Name
                                    </label>

                                    <input type="text" 
                                        class="form-control"
                                        id="navigation_menu_name"
                                        name="navigation_menu_name"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="app_id">
                                        App
                                    </label>

                                    <select
                                        id="app_id"
                                        name="app_id"
                                        class="form-select"
                                        data-control="select2"
                                        data-allow-clear="false"
                                        @disabled(!$canWrite)
                                    >
                                        <option>--</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="parent_id">
                                        Parent Menu
                                    </label>

                                    <select
                                        id="parent_id"
                                        name="parent_id"
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
                                    <label class="fs-6 fw-semibold form-label mt-3" for="navigation_menu_icon">
                                        Icon
                                    </label>

                                    <select
                                        id="navigation_menu_icon"
                                        name="navigation_menu_icon"
                                        class="form-select"
                                        data-control="select2"
                                        data-allow-clear="false"
                                        @disabled(!$canWrite)
                                    >
                                        <option value="">--</option>
                                        @include('partials.navigation-menu-item-options')
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="parent_id">
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

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="table_name">
                                        Database Table
                                    </label>

                                    <select
                                        id="table_name"
                                        name="table_name"
                                        class="form-select"
                                        data-control="select2"
                                        data-allow-clear="false"
                                        @disabled(!$canWrite)
                                    >
                                        <option>--</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if($canWrite)
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" form="navigation_menu_form" id="submit-data">
                            Save Changes
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-10">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Navigation Menu Route Details</h3>
                    </div>
                </div>
                <div class="card-body">
                    <form id="navigation_menu_route_form" method="post" action="#" novalidate>
                        @csrf
                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="index_view_file">
                                        Index View File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="index_view_file"
                                        name="index_view_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="index_js_file">
                                        Index JS File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="index_js_file"
                                        name="index_js_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="new_view_file">
                                        New View File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="new_view_file"
                                        name="new_view_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="new_js_file">
                                        New JS File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="new_js_file"
                                        name="new_js_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="details_view_file">
                                        Details View File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="details_view_file"
                                        name="details_view_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="details_js_file">
                                        Details JS File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="details_js_file"
                                        name="details_js_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="import_view_file">
                                        Import View File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="import_view_file"
                                        name="import_view_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="import_js_file">
                                        Import JS File
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="import_js_file"
                                        name="import_js_file"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if($canWrite)
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" form="navigation_menu_route_form" id="submit-route-data">
                            Save Changes
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1 me-3">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="navigation-menu-permission-datatable-search" placeholder="Search..." autocomplete="off" />
                        </div>
                        <select id="navigation-menu-permission-datatable-length" class="form-select w-auto">
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
                        <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">
                            @if(($canAssign ?? false) === true)
                                <button type="button"
                                        class="btn btn-light-primary me-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#role-permission-assignment-modal"
                                        id="assign-role-permission">
                                    <i class="ki-outline ki-plus fs-2"></i> Assign
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body pt-9">
                    <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="role-permission-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Role</th>
                                <th>Read Access</th>
                                <th>Create Access</th>
                                <th>Write Access</th>
                                <th>Delete Access</th>
                                <th>Import Access</th>
                                <th>Export Access</th>
                                <th>Log Notes Access</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="role-permission-assignment-modal" class="modal fade" tabindex="-1" aria-labelledby="role-permission-assignment-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Assign Role</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="role_permission_assignment_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <select multiple="multiple" size="20" id="role_id" name="role_id[]"></select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="role_permission_assignment_form" class="btn btn-primary" id="submit-assignment">Assign</button>
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