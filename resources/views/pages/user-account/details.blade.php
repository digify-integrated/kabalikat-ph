@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')    
    @php
        $canWrite  = ($writePermission ?? 0) > 0;
        $canDelete = ($deletePermission ?? 0) > 0;

        $activateUser = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(1, Auth::id());

        $deactivateUser = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(2, Auth::id());

        $canAssignUserAccount = app(\App\Http\Controllers\SystemActionController::class)
            ->userHasRoleAccessForAction(3, Auth::id());

        $user = DB::table('users')
            ->where('id', $detailsId)
            ->first();
    @endphp

    <div class="d-flex flex-column flex-lg-row">
        <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
            <div class="card card-flush">
                <div class="card-body text-center">
                    <div class="image-input image-input-outline" data-kt-image-input="true">
                        <div
                            class="image-input-wrapper w-125px h-125px"
                            id="profile_picture_image"
                            style="background-image: url('{{ asset('assets/media/default/default-avatar.jpg') }}')"
                        ></div>

                        @if($canWrite)
                            <label
                                class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                data-kt-image-input-action="change"
                                data-bs-toggle="tooltip"
                                aria-label="Change image"
                                data-bs-original-title="Change image"
                            >
                                <i class="ki-outline ki-pencil fs-7"></i>
                                <input type="file" id="profile_picture" name="profile_picture" accept=".png, .jpg, .jpeg">
                            </label>
                        @endif
                    </div>

                    <div class="form-text mt-5">
                        Set the user profile image. Only *.png, *.jpg and *.jpeg image files are accepted.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <div class="card card-flush">
                <div class="card-header border-0">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">User Details</h3>
                    </div>

                    @if($canDelete || ($activateUser ?? false) === true && $user->status === 'Inactive' || ($deactivateUser ?? false) === true && $user->status === 'Active')
                       <a href="#" class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown align-self-center" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true" style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-60px, 539px);" data-popper-placement="bottom-end">

                            @if(($activateUser ?? false) === true && $user->status === 'Inactive')
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="activate-user">
                                        Activate
                                    </a>
                                </div>                            
                            @endif

                            @if(($deactivateUser ?? false) === true && $user->status === 'Active')
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="deactivate-user">
                                        Deactivate
                                    </a>
                                </div>
                            @endif

                            @if($canDelete)
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);" class="menu-link px-3" id="delete-user">
                                        Delete
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <form id="user_form" method="post" action="#" novalidate>
                    @csrf
                    <div class="card-body border-top p-9">
                        <div class="row">
                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="user_name">
                                        User Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="user_name"
                                        name="user_name"
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
                                    <label class="fs-6 fw-semibold required form-label mt-3" for="email">
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        maxlength="100"
                                        autocomplete="off"
                                        @disabled(!$canWrite)
                                    >
                                </div>
                            </div>

                            <div class="col">
                                <div class="fv-row mb-7">
                                    <label class="fs-6 fw-semibold form-label mt-3" for="user_name">
                                        Password
                                    </label>

                                    <div class="input-group mb-5">
                                        <input
                                            type="password"
                                            id="password"
                                            name="password"
                                            class="form-control"
                                            @disabled(!$canWrite)
                                        >
                                        <span class="input-group-text password-addon">
                                            <i class="ki-outline ki-eye fs-3"></i>
                                        </span>
                                    </div>
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

            <div class="card mb-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1 me-3">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text" class="form-control w-250px ps-12" id="role-datatable-search" placeholder="Search..." autocomplete="off" />
                        </div>
                        <select id="role-datatable-length" class="form-select w-auto">
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
                                        data-bs-target="#role-assignment-modal"
                                        id="assign-role">
                                    <i class="ki-outline ki-plus fs-2"></i> Assign
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body pt-9">
                    <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5 gs-7" id="role-table">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th>Role</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="role-assignment-modal" class="modal fade" tabindex="-1" aria-labelledby="role-assignment-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Assign Role</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <form id="role_assignment_form" method="post" action="#">
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
                    <button type="submit" form="role_assignment_form" class="btn btn-primary" id="submit-assignment">Assign</button>
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