<!doctype html>
<html lang="en">
    <head>
        <title> {{ $pageTitle }} </title>
        @include('partials.required-css')
    </head>
    <body id="kt_app_body" data-kt-app-header-fixed-mobile="true" data-kt-app-toolbar-enabled="true" class="app-default">
        @include('partials.theme-switcher')

        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
                @include('partials.module-header')

                <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">                                        
                    @include('partials.module-toolbar')
                    <div class="app-container  container-xxl">
                        <div class="app-main flex-column flex-row-fluid " id="kt_app_main">
                            <div class="d-flex flex-column flex-column-fluid">
                                <div id="kt_app_content" class="app-content flex-column-fluid">
                                    @yield('content')
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('partials.footer')
                </div>
            </div>
        </div>

        @include('partials.scroll')

        <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
        <script type="module" src="{{ asset('assets/js/navigation.js') }}"></script>
        @stack('scripts')
    </body>
</html>
