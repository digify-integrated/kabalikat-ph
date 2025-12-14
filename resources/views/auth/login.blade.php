@extends('layouts.auth')

@section('content')
    <form id="login_form" method="POST" action="#">
        @csrf
        <div class="row gy-3">
            <div class="col-xl-12">
                <label for="email" class="form-label text-default">Email</label>
                <input type="text" class="form-control" id="email" name="email" autocomplete="off">
            </div>
            <div class="col-xl-12 mb-2">
                <label for="password" class="form-label text-default d-block">Password</label>
                <div class="position-relative">
                    <input type="password" class="form-control" type="password" id="password" name="password" autocomplete="off">
                    <span class="show-password-button text-muted">
                        <i class="ri-eye-off-line align-middle"></i>
                    </span>
                </div>
                <div class="mt-2">
                    <a href="{{ route('forgot') }}" class="float-end link-danger fw-medium fs-12">Forgot password?</a>
                </div>
            </div>
        </div>
    </form>
    <div class="d-grid mt-3">
        <button type="submit" form="login_form" class="btn btn-primary">Sign In</button>
    </div>
    <div class="text-center mt-3 fw-medium">
        Dont have an account? <a href="{{ route(name: 'register') }}" class="text-primary">Register Here</a>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/show-password.js') }}"></script>
@endpush