@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Navigation Menu Details</h5>
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

                            <input type="text" class="form-control" id="navigation_menu_name" name="navigation_menu_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="app_id">
                                App
                            </label>

                            <select id="app_id" name="app_id" class="form-select" data-control="select2" data-allow-clear="false">
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

                            <select id="parent_id" name="parent_id" class="form-select" data-control="select2" data-allow-clear="false">
                                <option>--</option>
                            </select>
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mt-3" for="navigation_menu_icon">
                                Icon
                            </label>

                            <select id="navigation_menu_icon" name="navigation_menu_icon" class="form-select" data-control="select2" data-allow-clear="false">
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

                            <input type="number" class="form-control" id="order_sequence" name="order_sequence" min="0" max="1000">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold form-label mt-3" for="table_name">
                                Database Table
                            </label>

                            <select id="table_name" name="table_name" class="form-select" data-control="select2" data-allow-clear="false">
                                <option>--</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="navigation_menu_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush