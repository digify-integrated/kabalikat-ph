<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title> {{ $pageTitle ?? 'Error' }} </title>
    <link rel="icon" href="{{ asset('assets/images/brand-logos/favicon.svg') }}" type="image/x-icon">
    <script src="{{ asset('assets/js/authentication-main.js') }}"></script>
    <link id="style" href="{{ asset('assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" >
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" >
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet" >
    @stack('css')
</head>

<body class="authentication-background">
    <div class="authentication-basic-background">
        <img src="{{ asset('assets/images/media/backgrounds/9.png') }}" alt="background">
    </div>

    @include('partials.auth-switcher')

    <div class="container">
        <div class="row justify-content-center align-items-center authentication authentication-basic h-100">
            <div class="col-lg-6 col-md-6 col-sm-8 col-12">
                <div class="card custom-card border-0 my-4">
                    <div class="card-body p-5">
                        <div class="mb-4"> 
                            <a href="{{ route(name: 'login') }}" class="auth-logo">
                                <img src="{{ asset('assets/images/brand-logos/logo-light.svg') }}" alt="logo" class="logo-light"> 
                                <img src="{{ asset('assets/images/brand-logos/logo-dark.svg') }}" alt="logo" class="logo-dark"> 
                            </a> 
                        </div>
                        <div>
                            <h4 class="mb-1 fw-semibold">{{ $title }}</h4>
                            <p class="mb-4 text-muted fw-normal">{{ $description }}</p>
                        </div>
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>

</html>