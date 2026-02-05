<header id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate="{default: false, lg: true}" data-kt-sticky-name="app-header-sticky" data-kt-sticky-offset="{default: false, lg: '300px'}">
    <div class="app-container container-xxl d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
        <div class="d-flex align-items-center d-lg-none ms-n2 me-2" title="Show sidebar menu">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_header_menu_toggle">
                <i class="ki-outline ki-abstract-14 fs-2"></i>
            </div>
        </div>
        
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-18">
            <a href="/metronic8/demo34/?page=index">
                <img alt="Logo" src="{{ asset('assets/media/logos/logo-light.svg') }}" class="h-30px d-sm-none" />
                <img alt="Logo" src="{{ asset('assets/media/logos/logo-light.svg') }}" class="h-30px d-none d-sm-block" />
            </a>
        </div>
        
        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper" >
            <div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                @include('partials.navbar')
            </div>
       
            <div class="app-navbar flex-shrink-0">
                <div class="app-navbar-item ms-5" id="kt_header_user_menu_toggle">
                    <div class="cursor-pointer symbol symbol-35px symbol-md-40px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        <img class="symbol symbol-circle symbol-35px symbol-md-40px" src="{{ asset('assets/media/default/default-avatar.jpg') }}" alt="user"/>
                    </div>

                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-5">
                                    <img alt="Logo" src="{{ asset('assets/media/default/default-avatar.jpg') }}"/>
                                </div>
                            
                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">
                                        Max Smith
                                        <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">Pro</span>
                                    </div>

                                    <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
                                        max@kt.com
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="separator my-2"></div>

                        <div class="menu-item px-5" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="left-start" data-kt-menu-offset="-15px, 0">
                            <a href="#" class="menu-link px-5">
                                <span class="menu-title position-relative">
                                    Mode

                                    <span class="ms-5 position-absolute translate-middle-y top-50 end-0">
                                        <i class="ki-outline ki-night-day theme-light-show fs-2"></i>
                                        <i class="ki-outline ki-moon theme-dark-show fs-2"></i>
                                    </span>
                                </span>
                            </a>

                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                                <div class="menu-item px-3 my-0">
                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                        <span class="menu-icon" data-kt-element="icon">
                                            <i class="ki-outline ki-night-day fs-2"></i>
                                        </span>
                                         <span class="menu-title"> Light </span>
                                    </a>
                                </div>
                                
                                <div class="menu-item px-3 my-0">
                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                        <span class="menu-icon" data-kt-element="icon">
                                            <i class="ki-outline ki-moon fs-2"></i>
                                        </span>
                                        <span class="menu-title"> Dark </span>
                                    </a>
                                </div>
                                                
                                <div class="menu-item px-3 my-0">
                                    <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                        <span class="menu-icon" data-kt-element="icon">
                                            <i class="ki-outline ki-screen fs-2"></i>
                                        </span>
                                        <span class="menu-title"> System </span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="menu-item px-5 my-1">
                            <a href="/metronic8/demo34/?page=account/settings" class="menu-link px-5">
                                Account Settings
                            </a>
                        </div>
                                        
                        <div class="menu-item px-5">
                            <a href="/logout" class="menu-link px-5">
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>