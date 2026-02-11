<!DOCTYPE html>
<html lang="en">
<head>
    <title> {{ $pageTitle ?? env('APP_NAME', 'Laravel') }} </title>
    @include('partials.required-css')
</head>
<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
    @include('partials.theme-switcher')

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
            body {
                background-image: url('./assets/media/auth/bg4.jpg');
            }

            [data-bs-theme="dark"] body {
                background-image: url('./assets/media/auth/bg4-dark.jpg');
            }
        </style>
        
        <div class="d-flex flex-center flex-column-fluid flex-lg-row">
            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
                <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-500px w-100 p-10">
                    <div class="d-flex flex-center flex-column flex-column-fluid px-0 pb-lg-10 pt-lg-10">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.error-modal')
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    @stack('scripts')
</body>
</html>