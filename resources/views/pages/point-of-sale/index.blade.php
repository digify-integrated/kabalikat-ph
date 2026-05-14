@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
   <div class="row g-5 g-xl-9">

    <!-- OPEN REGISTER -->
    <div class="col-md-6 col-xl-4">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">

            <!-- Accent -->
            <div class="h-5px bg-success"></div>

            <!-- Header -->
            <div class="card-header border-0 pt-6 pb-4">

                <div class="d-flex align-items-center justify-content-between w-100">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="ki-outline ki-shop fs-2x text-success"></i>
                            </div>
                        </div>

                        <div>
                            <h3 class="fw-bold mb-1">Main Cashier</h3>

                            <div class="d-flex align-items-center fs-7 text-muted">
                                <span class="bullet bullet-dot bg-success me-2"></span>
                                Active session
                            </div>
                        </div>

                    </div>

                    <span class="badge badge-light-success fw-bold px-4 py-2">
                        OPEN
                    </span>

                </div>

            </div>

            <!-- Stats -->
            <div class="px-6 mb-5">

                <div class="row g-3">

                    <div class="col-4">
                        <div class="bg-light-success rounded-4 px-3 py-3 text-center">
                            <div class="text-muted fs-8 mb-1">Cash</div>
                            <div class="fw-bold fs-4">₱8.4K</div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="bg-light-primary rounded-4 px-3 py-3 text-center">
                            <div class="text-muted fs-8 mb-1">Sales</div>
                            <div class="fw-bold fs-4">42</div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="bg-light-info rounded-4 px-3 py-3 text-center">
                            <div class="text-muted fs-8 mb-1">Duration</div>
                            <div class="fw-bold fs-4">5h</div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Timeline -->
            <div class="px-6 mb-5">

                <div class="bg-light rounded-4 p-5">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Session Timeline</h5>
                        <span class="badge badge-light-success">LIVE</span>
                    </div>

                    <div class="timeline">

                        <div class="timeline-item align-items-center mb-7">

                            <div class="timeline-line mt-1 mb-n6"></div>

                            <div class="timeline-icon">
                                <i class="ki-duotone ki-check-circle fs-2 text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>

                            <div class="timeline-content m-0">

                                <span class="fs-7 text-gray-500 d-block">
                                    Register Opened
                                </span>

                                <span class="fs-6 fw-bold text-gray-800">
                                    04 Mar 2026 · 02:58 PM
                                </span>

                            </div>

                        </div>

                        <div class="timeline-item align-items-center">

                            <div class="timeline-line"></div>

                            <div class="timeline-icon">
                                <i class="ki-duotone ki-time fs-2 text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>

                            <div class="timeline-content m-0">

                                <span class="fs-7 text-gray-500 d-block">
                                    Currently Active
                                </span>

                                <span class="fs-6 fw-bold text-gray-800">
                                    Awaiting closing
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Action -->
            <div class="card-body pt-0">

                <a href="#"
                   class="btn btn-primary fw-bold w-100 py-3 rounded-3">
                    View Register
                </a>

            </div>

        </div>

    </div>

    <!-- CLOSED REGISTER -->
    <div class="col-md-6 col-xl-4">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">

            <!-- Accent -->
            <div class="h-5px bg-danger"></div>

            <!-- Header -->
            <div class="card-header border-0 pt-6 pb-4">

                <div class="d-flex align-items-center justify-content-between w-100">

                    <div class="d-flex align-items-center">

                        <div class="symbol symbol-45px me-4">
                            <div class="symbol-label bg-light-danger">
                                <i class="ki-outline ki-lock-2 fs-2x text-danger"></i>
                            </div>
                        </div>

                        <div>
                            <h3 class="fw-bold mb-1">Main Cashier</h3>

                            <div class="d-flex align-items-center fs-7 text-muted">
                                <span class="bullet bullet-dot bg-danger me-2"></span>
                                Closed session
                            </div>
                        </div>

                    </div>

                    <span class="badge badge-light-danger fw-bold px-4 py-2">
                        CLOSED
                    </span>

                </div>

            </div>

            <!-- Stats -->
            <div class="px-6 mb-5">

                <div class="row g-3">

                    <div class="col-4">
                        <div class="bg-light-danger rounded-4 px-3 py-3 text-center">
                            <div class="text-muted fs-8 mb-1">Closing</div>
                            <div class="fw-bold fs-4">₱5.4K</div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="bg-light-warning rounded-4 px-3 py-3 text-center">
                            <div class="text-muted fs-8 mb-1">Sales</div>
                            <div class="fw-bold fs-4">31</div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="bg-light-info rounded-4 px-3 py-3 text-center">
                            <div class="text-muted fs-8 mb-1">Shift</div>
                            <div class="fw-bold fs-4">12h</div>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Timeline -->
            <div class="px-6 mb-5">

                <div class="bg-light rounded-4 p-5">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Last Session</h5>
                        <span class="badge badge-light-danger">CLOSED</span>
                    </div>

                    <div class="timeline">

                        <div class="timeline-item align-items-center mb-7">

                            <div class="timeline-line mt-1 mb-n6"></div>

                            <div class="timeline-icon">
                                <i class="ki-duotone ki-check-circle fs-2 text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>

                            <div class="timeline-content m-0">

                                <span class="fs-7 text-gray-500 d-block">
                                    Register Opened
                                </span>

                                <span class="fs-6 fw-bold text-gray-800">
                                    03 Mar 2026 · 08:00 AM
                                </span>

                            </div>

                        </div>

                        <div class="timeline-item align-items-center">

                            <div class="timeline-line"></div>

                            <div class="timeline-icon">
                                <i class="ki-duotone ki-cross-circle fs-2 text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>

                            <div class="timeline-content m-0">

                                <span class="fs-7 text-gray-500 d-block">
                                    Register Closed
                                </span>

                                <span class="fs-6 fw-bold text-gray-800">
                                    03 Mar 2026 · 08:15 PM
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Action -->
            <div class="card-body pt-0">

                <a href="#"
                   class="btn btn-success fw-bold w-100 py-3 rounded-3">
                    Open Register
                </a>

            </div>

        </div>

    </div>

    <div class="col-md-6 col-xl-4">

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">

        <!-- Accent -->
        <div class="h-5px bg-secondary"></div>

        <!-- Header -->
        <div class="card-header border-0 pt-6 pb-3">

            <div class="d-flex align-items-center justify-content-between w-100">

                <div class="d-flex align-items-center">

                    <div class="symbol symbol-45px me-4">
                        <div class="symbol-label bg-light-secondary">
                            <i class="ki-outline ki-setting-2 fs-2x text-gray-700"></i>
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold mb-0">
                            Main Cashier
                        </h3>

                        <div class="text-muted fs-7 mt-1">
                            Not initialized
                        </div>
                    </div>

                </div>

                <span class="badge badge-light-secondary fw-semibold px-3 py-2">
                    READY
                </span>

            </div>

        </div>

        <!-- Core Message -->
        <div class="card-body pt-4 pb-3">

            <div class="text-center py-6">

                <i class="ki-outline ki-information-5 fs-3x text-gray-400 mb-4"></i>

                <h4 class="fw-bold text-gray-800 mb-2">
                    No register session yet
                </h4>

                <div class="text-muted fs-7 px-4">
                    This cashier register is ready. Start the first session to begin tracking sales and cash flow.
                </div>

            </div>

        </div>

        <!-- Minimal Status -->
        <div class="px-7 pb-5">

            <div class="d-flex justify-content-between py-2 border-bottom">
                <span class="text-muted fs-7">State</span>
                <span class="fw-semibold text-dark">Not started</span>
            </div>

            <div class="d-flex justify-content-between py-2">
                <span class="text-muted fs-7">History</span>
                <span class="fw-semibold text-muted">Empty</span>
            </div>

        </div>

        <!-- CTA -->
        <div class="card-body pt-0">

            <a href="#"
               class="btn btn-primary fw-bold w-100 py-3 rounded-3">

                Open First Register

            </a>

        </div>

    </div>

</div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-duallistbox/dist/jquery.bootstrap-duallistbox.min.js') }}"></script>

    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush

