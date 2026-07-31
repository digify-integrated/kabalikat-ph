@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="row mb-4">
        <div class="col">
            <input type="text" class="form-control form-control-solid" id="kitchen_ticket_search" placeholder="Search tickets..." />
        </div>
    </div>

    <div id="kitchen-board"></div>

    <div class="modal fade" id="kdsAlarmModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" style="z-index: 100000; display: none !important;">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content border border-danger border-3 shadow-2xl">
                
                <div class="bg-danger w-100 progress-bar-striped progress-bar-animated" style="height: 10px; background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent); background-size: 1rem 1rem;"></div>

                <div class="modal-body p-8 text-center bg-light">
                    <div class="mb-5 d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 90px; height: 90px; animation: pulse 1.5s infinite;">
                        <span class="fs-3tx">🚨</span>
                    </div>

                    <h2 class="text-danger fw-extrabold tracking-tight mb-2 fs-1 text-uppercase" id="kdsModalTitle">Ticket Overdue!</h2>
                    
                   <div class="p-4 rounded border border-gray-300 shadow-xs my-4">
                        <span class="d-block text-dark fw-bolder fs-2" id="kdsModalTicketNum">TICKET #-</span>
                        <span class="text-muted fw-bold fs-5 d-block mt-1" id="kdsModalTicketMeta">Table - • -</span>
                        <div class="badge bg-danger bg-opacity-10 text-danger fs-6 fw-bold px-3 py-2 mt-2 rounded-pill mb-3">
                            ⌛ Waiting Duration: <span id="kdsModalTicketTime">0</span> mins
                        </div>

                        <!-- NEW: DYNAMIC OVERDUE ITEMS LIST CONTAINER -->
                        <div class="text-start border-top pt-3 mt-1">
                            <span class="text-dark fw-extrabold fs-6 uppercase tracking-wider d-block mb-2 text-center text-uppercase">Items Remaining:</span>
                            <div id="kdsModalTicketItemsList" class="d-grid gap-2 overflow-auto mx-auto" style="max-height: 220px; max-width: 420px;">
                                <!-- Overdue menu rows inject here dynamically -->
                            </div>
                        </div>
                    </div>

                    <p class="text-muted px-2 mb-6 fs-6 lh-base fw-medium" id="kdsModalQueueNotice">The kitchen staff must acknowledge this warning immediately or choose to snooze the siren alert.</p>

                    <div class="d-flex gap-3">
                        <button id="btn-kds-snooze" class="btn btn-light-dark border border-gray-400 w-50 py-3.5 fs-6 fw-bold text-uppercase tracking-wide">
                            ⏳ Snooze (3m)
                        </button>
                        <button id="btn-kds-acknowledge" class="btn btn-danger w-50 py-3.5 fs-5 fw-extrabold text-uppercase tracking-wider shadow">
                            ✅ Acknowledge
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="kds-audio-lock" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black" style="z-index: 99999; --bs-bg-opacity: 0.96; backdrop-filter: blur(8px);">
        <div class="card border border-secondary border-opacity-25 shadow-2xl overflow-hidden" style="max-width: 460px;">
            <div class="bg-warning w-100" style="height: 4px;"></div>
            <div class="card-body p-5 text-center">
                <div class="mb-4 d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle position-relative" style="width: 80px; height: 80px;">
                    <div class="position-absolute w-100 h-100 bg-warning bg-opacity-5 rounded-circle animate-ping" style="animation: pulse 2s infinite;"></div>
                    <i class="ki-outline ki-notification-on fs-3tx text-warning position-relative z-index-1"></i>
                </div>
                <h1 class="text-white fw-extrabold tracking-tight mb-2 fs-2">Kitchen Audio Standby</h1>
                <p class="text-muted px-3 mb-5 fs-6 lh-base">Browser security rules require an initial tap to authorize live order chimes and instant ticket alerts.</p>
                <button id="btn-unlock-kds" class="btn btn-warning w-100 py-4 fs-5 fw-extrabold text-uppercase tracking-widest shadow-lg d-flex align-items-center justify-content-center gap-2" style="border-radius: 0.75rem;">
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
        window.kitchenAlertAudioUrl = "{{ asset('assets/audio/kitchen-alarm.mp3') }}";
    </script>
    
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush