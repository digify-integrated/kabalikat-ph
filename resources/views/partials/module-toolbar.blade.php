<div id="kt_app_toolbar" class="app-toolbar py-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex align-items-start">
        <div class="d-flex flex-column flex-row-fluid">
            @include('partials.breadcrumbs')

            <div class="d-flex flex-stack flex-wrap flex-lg-nowrap gap-4 gap-lg-10 pt-13 pb-6 mb-lg-0 mb-8">
                <div class="page-title me-5">
                    <h1 class="page-heading d-flex text-white fw-bold fs-2 flex-column justify-content-center my-0">
                        {{ $pageTitle ?? '' }}
                    </h1>
                </div>

                <div class="d-flex align-self-center flex-center flex-shrink-1">
                    @if(($createPermission ?? 0) > 0 && !request()->routeIs('apps.new'))
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
</div>
