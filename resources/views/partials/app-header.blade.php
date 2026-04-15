<div id="kt_header" style="" class="header align-items-stretch">
    <div class="header-brand">
        <a href="{{ route('apps.index') }}">
            <img alt="Logo" src="{{ asset('assets/media/logos/logo-light.svg') }}" class="h-30px d-sm-none" />
            <img alt="Logo" src="{{ asset('assets/media/logos/logo-light.svg') }}" class="h-30px d-none d-sm-block" />
        </a>
    </div>

    <div class="toolbar d-flex align-items-stretch">
        <div class="container-xxl py-6 py-lg-0 d-flex flex-column flex-lg-row align-items-lg-stretch justify-content-lg-between">
            <div class="page-title d-flex justify-content-center flex-column me-5">
                <h1 class="d-flex flex-column text-gray-900 fw-bold fs-3 mb-0">
                    {{ $pageTitle ?? '' }}
                </h1>
            </div>
            <div class="d-flex align-items-stretch overflow-auto pt-3 pt-lg-0">
                <div class="d-flex align-items-center">
                    <a href="#" class="btn btn-sm btn-icon btn-icon-muted btn-active-icon-primary" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-night-day theme-light-show fs-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span></i>    <i class="ki-duotone ki-moon theme-dark-show fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </a>

                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-night-day fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span></i>
                                </span>
                                <span class="menu-title">
                                    Light
                                </span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <span class="menu-title">
                                    Dark
                                </span>
                            </a>
                        </div>
                        
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-screen fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                                <span class="menu-title">
                                    System
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>