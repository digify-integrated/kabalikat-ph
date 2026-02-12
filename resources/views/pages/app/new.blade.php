@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">App Details</h5>
        </div>
        <div class="card-body">
            <form id="app_form" method="post" action="#">
                @csrf
                <div class="row row-cols-1 row-cols-sm-4 rol-cols-md-3 row-cols-lg-4">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="app_name">
                                Display Name
                            </label>

                            <input type="text" class="form-control" id="app_name" name="app_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="navigation_menu_id">
                                Default Page
                            </label>

                            <select id="navigation_menu_id" name="navigation_menu_id" class="form-select" data-control="select2" data-allow-clear="false">
                                <option>--</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="app_version">
                                App Version
                            </label>

                            <input type="text" class="form-control" id="app_version" name="app_version" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="order_sequence">
                                Order Sequence
                            </label>

                            <input type="number" class="form-control" id="order_sequence" name="order_sequence" min="0">
                        </div>
                    </div>
                </div>

                <div class="fv-row mb-4">
                    <label class="fs-6 fw-semibold required form-label mt-3" for="app_description">
                        Description
                    </label>

                    <textarea class="form-control" id="app_description" name="app_description" maxlength="500" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="app_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush