<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
    @include('partials.stylesheet')
</head>

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat"
    data-kt-app-page-loading-enabled="true" data-kt-app-page-loading="off">

    {{-- Theme scripts partial --}}
    @include('partials.theme-script')

    <div class="d-flex flex-column flex-lg-row flex-column-fluid">
        <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
            <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                <div class="w-lg-600px p-10">
                    <form class="form w-100" id="login_form" method="post" action="#">
                        <img src="{{ asset('assets/images/logos/logo-dark.svg') }}" class="mb-5 system-logo" alt="Logo-Dark" />
                        <h2 class="mb-2 mt-4 fs-1 fw-bolder">Login to your account</h2>
                        <p class="mb-10 fs-5">Enter your email below to login to your account</p>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="position-relative mb-3">
                                <input class="form-control" type="password" id="password" name="password" autocomplete="off" />
                                <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2 password-addon">
                                    <i class="ki-outline ki-eye-slash fs-2 p-0"></i>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                            <a href="" class="link-primary">Forgot Password?</a>
                        </div>
                        <div class="d-grid">
                            <button id="signin" type="submit" class="btn btn-primary">Sign In</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2"
            style="background-image: url({{ asset('assets/images/background/login-bg.jpg') }});">
        </div>
    </div>

    {{-- Error modal and required JS --}}
    @include('partials.error-modal')
    @include('partials.required-js')

    {{-- Page-specific JS --}}
    <script type="module" src="{{ asset('assets/js/auth/login.js') }}?v={{ rand() }}"></script>
</body>

</html>
