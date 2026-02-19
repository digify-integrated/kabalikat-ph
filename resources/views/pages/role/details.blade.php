@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;

        $canAssignUserAccount = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(3, Auth::id());

        $canAssignNavigationMenu = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(4, Auth::id());

        $canAssignSystemAction = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(5, Auth::id());
    @endphp

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-10">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Role Details</h3>
                    </div>
                    @if($canDelete)
                        <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">
                            <div class="role px-3">
                                <a href="javascript:void(0);" class="menu-link px-3" id="delete-role">
                                    Delete
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <form id="role_form" method="post" action="#" novalidate>
                        @csrf

                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="role_name">
                                Display Name
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="role_name"
                                name="role_name"
                                maxlength="100"
                                autocomplete="off"
                                @disabled(!$canWrite)
                            >
                        </div>
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="role_description">
                                Description
                            </label>

                            <textarea
                                class="form-control"
                                id="role_description"
                                name="role_description"
                                maxlength="200"
                                rows="3"
                                @disabled(!$canWrite)
                            ></textarea>
                        </div>
                    </form>
                </div>

                @if($canWrite)
                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="submit" class="btn btn-primary" form="role_form" id="submit-data">
                            Save Changes
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1 me-3">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="role-user-account-datatable-search" placeholder="Search..." autocomplete="off" />
                        </div>
                        <select id="role-user-account-datatable-length" class="form-select w-auto">
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
                            @if(($canAssignUserAccount ?? false) === true)
                                <button type="button"
                                        class="btn btn-light-primary me-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#user-account-assignment-modal"
                                        id="assign-user-account">
                                    <i class="ki-outline ki-plus fs-2"></i> Assign
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body pt-9">
                    <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="role-user-account-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>User Account</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-10">
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
                            @if(($canAssignNavigationMenu ?? false) === true)
                                <button type="button"
                                        class="btn btn-light-primary me-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#navigation-menu-permission-assignment-modal"
                                        id="assign-navigation-menu-permission">
                                    <i class="ki-outline ki-plus fs-2"></i> Assign
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body pt-9">
                    <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="navigation-menu-permission-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Navigation Menu</th>
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

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1 me-3">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="system-action-permission-datatable-search" placeholder="Search..." autocomplete="off" />
                        </div>
                        <select id="system-action-permission-datatable-length" class="form-select w-auto">
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
                            @if(($canAssignSystemAction ?? false) === true)
                                <button type="button"
                                        class="btn btn-light-primary me-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#system-action-permission-assignment-modal"
                                        id="assign-system-action-permission">
                                    <i class="ki-outline ki-plus fs-2"></i> Assign
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body pt-9">
                    <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="system-action-permission-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>System Action</th>
                                <th>Access</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="navigation-menu-permission-assignment-modal" class="modal fade" tabindex="-1" aria-labelledby="navigation-menu-permission-assignment-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Assign Navigation Menu</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="navigation_menu_permission_assignment_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <select multiple="multiple" size="20" id="navigation_menu_id" name="navigation_menu_id[]"></select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="navigation_menu_permission_assignment_form" class="btn btn-primary" id="submit-navigation-menu-assignment">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <div id="system-action-permission-assignment-modal" class="modal fade" tabindex="-1" aria-labelledby="system-action-permission-assignment-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Assign System Action</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="system_action_permission_assignment_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <select multiple="multiple" size="20" id="system_action_id" name="system_action_id[]"></select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="system_action_permission_assignment_form" class="btn btn-primary" id="submit-system-action-assignment">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <div id="user-account-assignment-modal" class="modal fade" tabindex="-1" aria-labelledby="user-account-assignment-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Assign User Account</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="user_account_assignment_form" method="post" action="#">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <select multiple="multiple" size="20" id="user_account_id" name="user_account_id[]"></select>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="user_account_assignment_form" class="btn btn-primary" id="submit-user-account-assignment">Assign</button>
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