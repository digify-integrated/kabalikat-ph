<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" data-theme-mode="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Error</title>
    <link rel="icon" href="{{ asset('assets/images/brand-logos/favicon.svg') }}" type="image/x-icon">
    <script src="{{ asset('assets/js/authentication-main.js') }}"></script>
    <link id="style" href="{{ asset('assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" >
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" >
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet" >
</head>

<body>
    @include('partials.auth-switcher')

    @yield('content')

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>

</html>