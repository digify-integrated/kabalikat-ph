<div id="kt_app_toolbar" class="app-toolbar py-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex align-items-start">
        <div class="d-flex flex-column flex-row-fluid">
            @include('partials.breadcrumbs')
                                
            <div class="d-flex flex-stack flex-wrap flex-lg-nowrap gap-4 gap-lg-10 pt-13 pb-6">
                <div class="page-title me-5">
                    <h1 class="page-heading d-flex text-white fw-bold fs-2 flex-column justify-content-center my-0">
                        <span class="page-desc text-gray-600 fw-semibold fs-6 pt-3">
                            {{ $pageTitle }}
                        </span>
                    </h1>
                </div>
                                    
                <div class="d-flex align-self-center flex-center flex-shrink-0">
                    <a href="#" class="btn btn-flex btn-sm btn-outline btn-active-color-primary btn-custom px-4" data-bs-toggle="modal" data-bs-target="#kt_modal_invite_friends">
                        <i class="ki-outline ki-plus-square fs-4 me-2"></i> Invite
                    </a>

                    <a href="#" class="btn btn-sm btn-active-color-primary btn-outline btn-custom ms-3 px-4" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">
                        Set Your Target
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>