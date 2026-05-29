@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="row g-5 g-xl-8 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card card-flush h-xl-100 border-0 shadow-sm bg-body">
                <div class="card-body d-flex flex-column justify-content-between p-6">
                    <div class="d-flex flex-stack mb-4">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-bold fs-6 text-uppercase tracking-wider">Gross Sales</span>
                            <span class="text-gray-400 fw-semibold fs-7 mt-0.5">All active tickets</span>
                        </div>
                        <div class="d-flex flex-center rounded-2 h-50px w-50px bg-light-danger text-danger"> 
                            <i class="ki-duotone ki-chart-line fs-2x"><span class="path1"></span><span class="path2"></span></i>             
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <span class="fs-2 text-gray-600 fw-semibold me-1">₱</span>
                        <span class="fs-2hx text-gray-900 fw-bold tracking-tight" id="gross-sales-count">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-flush h-xl-100 border-0 shadow-sm bg-body">
                <div class="card-body d-flex flex-column justify-content-between p-6">
                    <div class="d-flex flex-stack mb-4">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-bold fs-6 text-uppercase tracking-wider">Net Sales</span>
                            <span class="text-gray-400 fw-semibold fs-7 mt-0.5">Excluding voids/cancels</span>
                        </div>
                        <div class="d-flex flex-center rounded-2 h-50px w-50px bg-light-success text-success"> 
                            <i class="ki-duotone ki-wallet fs-2x"><span class="path1"></span><span class="path2"></span></i>             
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <span class="fs-2 text-gray-600 fw-semibold me-1">₱</span>
                        <span class="fs-2hx text-gray-900 fw-bold tracking-tight" id="net-sales-count">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-flush h-xl-100 border-0 shadow-sm bg-body">
                <div class="card-body d-flex flex-column justify-content-between p-6">
                    <div class="d-flex flex-stack mb-4">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-bold fs-6 text-uppercase tracking-wider">Total Orders</span>
                            <span class="text-gray-400 fw-semibold fs-7 mt-0.5">Processed ticket volume</span>
                        </div>
                        <div class="d-flex flex-center rounded-2 h-50px w-50px bg-light-info text-info"> 
                            <i class="ki-duotone ki-shop fs-2x"><span class="path1"></span><span class="path2"></span></i>             
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <span class="fs-2hx text-gray-900 fw-bold tracking-tight" id="total-orders-count">0</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-flush h-xl-100 border-0 shadow-sm bg-body">
                <div class="card-body d-flex flex-column justify-content-between p-6">
                    <div class="d-flex flex-stack mb-4">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-bold fs-6 text-uppercase tracking-wider">Avg Order Value</span>
                            <span class="text-gray-400 fw-semibold fs-7 mt-0.5">Average ticket size</span>
                        </div>
                        <div class="d-flex flex-center rounded-2 h-50px w-50px bg-light-primary text-primary"> 
                            <i class="ki-duotone ki-calculator fs-2x"><span class="path1"></span><span class="path2"></span></i>             
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <span class="fs-2 text-gray-600 fw-semibold me-1">₱</span>
                        <span class="fs-2hx text-gray-900 fw-bold tracking-tight" id="avg-order-value">0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-5">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Recent Shop Orders</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Live transaction log streams from terminal registers</span>
                    </h3>
                </div>
                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3" id="recent-orders-table">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Order Info</th>
                                    <th>Register/Area</th>
                                    <th>Status</th>
                                    <th class="text-end">Net Total</th>
                                </tr>
                            </thead>
                            <tbody class="fw-bold text-gray-600"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-5">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Recent Payments Ledger</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">Detailed payment trace logs grouped by collection method</span>
                    </h3>
                </div>
                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3" id="payments-ledger-table">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Order Ref</th>
                                    <th>Method</th>
                                    <th>Trace Ref</th>
                                    <th>Status</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="fw-bold text-gray-600"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/plugins/datatables/datatables.bundle.js') }}"></script>
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush