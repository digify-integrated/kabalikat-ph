@extends('layouts.auth')

@section('content')
    <form class="form w-100" id="login_form" method="POST" action="#" novalidate>
        @csrf
        <div class="text-start mb-10">
            <h1 class="text-gray-900 fw-bolder mb-3">{{ $title }}</h1>
            <div class="text-gray-500 fw-semibold fs-7">
                {{ $description }}
            </div>
        </div>

        <div class="fv-row mb-8">
            <input type="email" placeholder="Email" id="email" name="email" autocomplete="off" class="form-control bg-transparent" />
        </div>
        
        <div class="fv-row mb-3">
            <div class="input-group mb-5">
                <input type="password" placeholder="Password" id="password" name="password" class="form-control bg-transparent"/>
                <span class="input-group-text password-addon">
                    <i class="ki-outline ki-eye fs-3"></i>
                </span>
            </div>
        </div>
        
        <div class="d-grid">
            <button type="submit" form="login_form" id="signin" class="btn btn-primary">Sign In</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/auth/login.js') }}"></script>
@endpush