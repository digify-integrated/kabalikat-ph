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
                                    id="delete-app">
                                        Delete
                                    </a>
                                </div>
                            @endif

                        </div>
                    @endif

                </div>
            </div>
        </div>

        <div class="card-body pt-9">
            <div class="table-responsive">
                <table class="table align-middle cursor-pointer table-row-dashed fs-6 gy-5" id="app-table">
                    <thead>
                        <tr class="text-start text-gray-800 fw-bold fs-7 text-uppercase gs-0">
                            <th>
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" id="datatable-checkbox" type="checkbox">
                                </div>
                            </th>
                            <th>App</th>
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

