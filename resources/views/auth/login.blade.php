@extends('layouts.auth')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/libs/toastify-js/src/toastify.css') }}">
@endpush

@section('content')
    <form id="login_form" method="POST" action="#" novalidate>
        @csrf
        <div class="row gy-3">
            <div class="col-xl-12">
                <label for="email" class="form-label text-default">Email</label>
                <input type="email" class="form-control" id="email" name="email" autocomplete="off">
            </div>
            <div class="col-xl-12 mb-2">
                <label for="password" class="form-label text-default d-block">Password</label>
                <div class="position-relative">
                    <input type="password" class="form-control" type="password" id="password" name="password" autocomplete="off">
                    <span class="password-addon text-muted">
                        <i class="ri-eye-off-line align-middle"></i>
                    </span>
                </div>
            </div>
        </div>
    </form>
    <div class="d-grid mt-3">
        <button type="submit" form="login_form" id="signin" class="btn btn-primary">Sign In</button>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/libs/toastify-js/src/toastify.js') }}"></script>
    <script type="module" src="{{ asset('assets/js/auth/login.js') }}"></script>
@endpush