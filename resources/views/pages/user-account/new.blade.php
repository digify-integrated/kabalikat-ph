@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">User Details</h5>
        </div>
        <div class="card-body">
            <form id="users_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="user_name">
                                User Name
                            </label>

                            <input type="text" class="form-control" id="user_name" name="user_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 rol-cols-md-1 row-cols-lg-2">
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="email">
                                Email
                            </label>

                            <input type="email" class="form-control" id="email" name="email" maxlength="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="user_name">
                                Password
                            </label>

                             <div class="input-group mb-5">
                                <input type="password" id="password" name="password" class="form-control bg-transparent"/>
                                <span class="input-group-text password-addon">
                                    <i class="ki-outline ki-eye fs-3"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="users_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush