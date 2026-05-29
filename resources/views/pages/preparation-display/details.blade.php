@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="row mb-4">
        <div class="col">
            <input type="text" class="form-control form-control-solid" id="kitchen_ticket_search"placeholder="Search tickets..." />
        </div>
    </div>

    <div id="kitchen-board"></div>

    <div id="kds-audio-lock" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black" style="z-index: 99999; --bs-bg-opacity: 0.96; backdrop-filter: blur(8px);">
        <div class="card border border-secondary border-opacity-25 shadow-2xl overflow-hidden" style="max-width: 460px; border-radius: 1.25rem;">
            
            <div class="bg-warning w-100" style="height: 4px;"></div>

            <div class="card-body p-5 text-center">
                
                <div class="mb-4 d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle position-relative" style="width: 80px; height: 80px;">
                    <div class="position-absolute w-100 h-100 bg-warning bg-opacity-5 rounded-circle animate-ping" style="animation: pulse 2s infinite;"></div>
                    <i class="ki-outline ki-notification-on fs-3tx text-warning position-relative z-index-1"></i>
                </div>

                <h1 class="text-white fw-extrabold tracking-tight mb-2 fs-2">Kitchen Audio Standby</h1>
                
                <p class="text-muted px-3 mb-5 fs-6 lh-base">Browser security rules require an initial tap to authorize live order chimes and instant ticket alerts.</p>

                <button id="btn-unlock-kds" class="btn btn-warning w-100 py-4 fs-5 fw-extrabold text-uppercase tracking-widest shadow-lg d-flex align-items-center justify-content-center gap-2" style="border-radius: 0.75rem; transition: transform 0.1s ease-in-out;">
                    <span>🔊 Launch Live KDS Board</span>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>
    <script>
        window.kitchenAlertAudioUrl = "{{ asset('assets/audio/kitchen-ticket.mp3') }}";
    </script>
    
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush