<!doctype html>
<html lang="en">
    <head>
        <title>Error</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.required-css')
        @stack('css')
    </head>
    <body id="kt_app_body"
      data-kt-app-header-fixed-mobile="true"
      data-kt-app-toolbar-enabled="true"
      data-kt-app-page-loading-enabled="true"
      data-kt-app-page-loading="on"
      class="app-default">
        @include('partials.theme-switcher')
        @include('partials.page-loader')
        <div class="d-flex flex-column flex-root">
            <div class="page d-flex flex-row flex-column-fluid">
                <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                    <div class="content d-flex flex-column flex-column-fluid " id="kt_content">
                        <div class="post d-flex flex-column-fluid" id="kt_post">
                            <div id="kt_content_container" class=" container-xxl ">
                                @yield('content')
                            </div>
                        </div>
                    </div>
                    @include('partials.footer')
                </div>
            </div>
        </div>

       
        
        @include('partials.error-modal')
        @include('partials.scroll')

        <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
        <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
        <script type="module" src="{{ asset('assets/js/navigation.js') }}"></script>
        @stack('scripts')
    </body>
</html>
