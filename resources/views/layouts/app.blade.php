
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent" data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat" data-toggled="close"  data-vertical-style="doublemenu" data-toggled="double-menu-open">
<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title> {{ $pageTitle }} </title>
    @include('partials.required-css')
    @stack('css')
</head>

<body>
    @include('partials.progress-bar')
    @include('partials.theme-switcher')
    @include('partials.loader')

    <div class="page">
        @include('partials.header')
        @include('partials.sidebar')

       <div class="main-content app-content">
            <div class="container-fluid page-container main-body-container">
                @yield('content')
            </div>
        </div>      
       
        @include('partials.footer')
    </div>
    @include('partials.scroll')
    @include('partials.required-js')
    @stack('scripts')
    <script src="{{ asset('assets/js/custom.js') }}"></script>
</body>

</html>