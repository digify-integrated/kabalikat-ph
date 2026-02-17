@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                @include('partials.datatable-search')
            </div>

            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-customer-table-toolbar="base">

                    @if(($deletePermission ?? 0) > 0 || ($exportPermission ?? 0) > 0)
                        <a href="#"
                        class="btn btn-light-primary btn-flex btn-center btn-active-light-primary show menu-dropdown action-dropdown me-3 d-none"
                        data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                            Actions
                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                        </a>

                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                            data-kt-menu="true">

                            @if(($exportPermission ?? 0) > 0)
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);"
                                    class="menu-link px-3"
                                    id="export-data"
                                    data-bs-toggle="modal"
                                    data-bs-target="#export-modal">
                                        Export
                                    </a>
                                </div>
                            @endif

                            @if(($deletePermission ?? 0) > 0)
                                <div class="menu-item px-3">
                                    <a href="javascript:void(0);"
                                    class="menu-link px-3"
                                    id="delete-delete-data">
                                        Delete
                                    </a>
                                </div>
                            @endif

                        </div>
                    @endif
                </div>
                <div>
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end"><i class="ki-outline ki-filter fs-2"></i> Filter</button>
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-5">
                            <div class="mb-5">
                                <label class="form-label fs-6 fw-semibold" for="filter_by_app">Filter By App:</label>
                                <select id="filter_by_app" name="filter_by_app" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false"></select>
                            </div>
                            <div class="mb-5">
                                <label class="form-label fs-6 fw-semibold" for="filter_by_parent_menu">Filter By  Parent Menu:</label>
                                <select id="filter_by_parent_menu" name="filter_by_parent_menu" multiple="multiple" class="form-select" data-control="select2" data-allow-clear="false"></select>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6" id="reset-filter" data-kt-menu-dismiss="true">Reset</button>
                                <button type="button" class="btn btn-primary fw-semibold px-6" id="apply-filter" data-kt-menu-dismiss="true">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body pt-9">
            <div class="table-responsive">
                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5" id="navigation-menu-table">
                    <thead>
                        <tr class="text-start text-gray-800 fw-bold fs-7 text-uppercase gs-0">
                            <th>
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" id="datatable-checkbox" type="checkbox">
                                </div>
                            </th>
                            <th>Navigation Menu</th>
                            <th>App</th>
                            <th>Parent Menu</th>
                            <th>Order Sequence</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-800"></tbody>
                </table>
            </div>
        </div>
    </div>

    @if(($exportPermission ?? 0) > 0)
        @include('partials.export-modal')
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>

    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush

