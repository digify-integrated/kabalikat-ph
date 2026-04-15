<div id="kt_header" style="" class="header align-items-stretch">
    <div class="header-brand">
        <a href="{{ route('apps.index') }}">
            <img alt="Logo" src="{{ asset('assets/media/logos/logo-light.svg') }}" class="h-30px d-sm-none" />
            <img alt="Logo" src="{{ asset('assets/media/logos/logo-light.svg') }}" class="h-30px d-none d-sm-block" />
        </a>
        <div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-minimize" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
            <i class="ki-duotone ki-entrance-right fs-1 me-n1 minimize-default">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <i class="ki-duotone ki-entrance-left fs-1 minimize-active">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
        </div>
        <div class="d-flex align-items-center d-lg-none me-n2" title="Show aside menu">
            <div class="btn btn-icon btn-active-color-primary w-30px h-30px" id="kt_aside_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>
        </div>
    </div>
    
    <div class="toolbar d-flex align-items-stretch">
        <div class="container-xxl d-flex flex-stack flex-wrap">
            <div class="page-title d-flex flex-column me-3 mt-2">
                <h1 class="d-flex flex-column text-gray-900 fw-bold fs-3 mb-0">
                    {{ $pageTitle ?? '' }}
                </h1>

                @include('partials.breadcrumbs')
            </div>
            <div class="d-flex align-items-center py-3 py-md-1">
                @if(($createPermission ?? 0) > 0 && (request()->routeIs('apps.base') || request()->routeIs('apps.details')))
                        <a href="{{ route('apps.new', ['appId' => $appId, 'navigationMenuId' => $navigationMenuId]) }}"
                           class="btn btn-flex btn-sm btn-outline btn-active-color-primary btn-custom px-4">
                            <i class="ki-outline ki-plus fs-4 me-2"></i> New
                        </a>
                    @endif

                    @if(($importPermission ?? 0) > 0 && request()->routeIs('apps.base') )
                        <a href="{{ route('apps.import', ['appId' => $appId, 'navigationMenuId' => $navigationMenuId]) }}"
                           class="btn btn-flex btn-sm btn-outline btn-active-color-primary btn-custom ms-3 px-4">
                            <i class="ki-outline ki-exit-down fs-4 me-2"></i> Import
                        </a>
                    @endif

                    @if(($logsPermission ?? 0) > 0 && request()->routeIs('apps.details'))
                        <button id="log-notes-main"
                                class="btn btn-flex btn-sm btn-outline btn-active-color-primary btn-custom ms-3 px-4"
                                data-bs-toggle="modal"
                                data-bs-target="#log-notes-modal">
                            <i class="ki-outline ki-shield-search fs-4 me-2"></i> Log Notes
                        </button>
                 @endif
            </div>
        </div>
    </div>
</div>