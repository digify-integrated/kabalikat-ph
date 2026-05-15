@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="row g-5 g-xl-9" id="shop_register_container"></div>

    <div id="register-modal" class="modal fade" tabindex="-1" aria-labelledby="register-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">

                <div class="modal-body">
                    <form id="register_form" method="post" action="#">
                        @csrf

                        <input type="hidden" id="shop_register_id" name="shop_register_id">
                        <input type="hidden" id="session" name="session">

                        <!-- HEADER -->
                        <div class="d-flex align-items-center mb-6">
                            <div>
                                <h2 class="fw-bold mb-1">Cash Count</h2>
                                <div class="text-muted fs-7">
                                    Enter denominations to compute opening cash
                                </div>
                            </div>

                            <div class="ms-auto">
                                <span class="badge badge-light-success px-4 py-3 fs-7 fw-bold register-badge">
                                    Register Opening
                                </span>
                            </div>
                        </div>

                        <div class="row g-6">

                            <!-- LEFT: DENOMINATIONS -->
                            <div class="col-lg-7">

                                <!-- BILLS -->
                                <div class="card border-0 shadow-sm rounded-4 mb-6">
                                    <div class="card-body">

                                        <h5 class="text-uppercase text-muted fs-7 fw-bold mb-4">
                                            Bills
                                        </h5>

                                        <div class="row g-3">

                                            @php
                                                $bills = [
                                                    '1000' => '1,000.00',
                                                    '500'  => '500.00',
                                                    '200'  => '200.00',
                                                    '100'  => '100.00',
                                                    '50'   => '50.00',
                                                    '20'   => '20.00',
                                                ];
                                            @endphp

                                            @foreach ($bills as $id => $label)
                                                <div class="col-12">

                                                    <div class="d-flex align-items-center justify-content-between">

                                                        <label class="fw-semibold fs-6 text-gray-700 w-100px">
                                                            ₱ {{ $label }}
                                                        </label>

                                                        <div class="text-end">

                                                            <input type="number"
                                                                id="open_{{ $id }}"
                                                                name="open_{{ $id }}"
                                                                class="form-control form-control-solid w-150px text-end qty-input"
                                                                data-denomination="{{ str_replace(',', '', $label) }}"
                                                                min="0"
                                                                step="1"
                                                                placeholder="0" />

                                                            <!-- SUBTOTAL -->
                                                            <div class="fs-8 text-muted mt-1 subtotal"
                                                                id="subtotal_{{ $id }}">
                                                                ₱ 0.00
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>
                                            @endforeach

                                        </div>

                                    </div>
                                </div>

                                <!-- COINS -->
                                <div class="card border-0 shadow-sm rounded-4">

                                    <div class="card-body">

                                        <h5 class="text-uppercase text-muted fs-7 fw-bold mb-4">
                                            Coins
                                        </h5>

                                        <div class="row g-3">

                                            @php
                                                $coins = [
                                                    '10'   => '10.00',
                                                    '5'    => '5.00',
                                                    '1'    => '1.00',
                                                    '0_50' => '0.50',
                                                    '0_25' => '0.25',
                                                    '0_10' => '0.10',
                                                    '0_05' => '0.05',
                                                    '0_01' => '0.01',
                                                ];
                                            @endphp

                                            @foreach ($coins as $id => $label)
                                                <div class="col-12">

                                                    <div class="d-flex align-items-center justify-content-between">

                                                        <label class="fw-semibold fs-6 text-gray-700 w-100px">
                                                            ₱ {{ $label }}
                                                        </label>

                                                        <div class="text-end">

                                                            <input type="number"
                                                                id="open_{{ $id }}"
                                                                name="open_{{ $id }}"
                                                                class="form-control form-control-solid w-150px text-end qty-input"
                                                                data-denomination="{{ str_replace(',', '', $label) }}"
                                                                min="0"
                                                                step="1"
                                                                placeholder="0" />

                                                            <!-- SUBTOTAL -->
                                                            <div class="fs-8 text-muted mt-1 subtotal"
                                                                id="subtotal_{{ $id }}">
                                                                ₱ 0.00
                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>
                                            @endforeach

                                        </div>

                                    </div>
                                </div>

                            </div>

                            <!-- RIGHT: SUMMARY PANEL -->
                            <div class="col-lg-5">

                                <div class="position-sticky" style="top: 20px;">

                                    <!-- TOTAL CARD -->
                                    <div class="card border-0 shadow-sm rounded-4 mb-5">
                                        <div class="card-body text-center py-10">

                                            <div class="text-muted fs-7 mb-2 total-label">
                                                Opening Cash Total
                                            </div>

                                            <div class="fs-2hx fw-bold text-primary">
                                                ₱ <span id="open_total">0.00</span>
                                            </div>

                                            <div class="text-muted fs-7 mt-3">
                                                Auto-calculated in real time
                                            </div>

                                        </div>
                                    </div>

                                    <!-- REMARKS -->
                                    <div class="card border-0 shadow-sm rounded-4 mb-5">
                                        <div class="card-body">

                                            <label class="fw-semibold fs-6 mb-2" for="remarks">
                                                Remarks
                                            </label>

                                            <textarea class="form-control form-control-solid"
                                                    id="remarks"
                                                    name="remarks"
                                                    rows="6"
                                                    maxlength="1000"></textarea>

                                        </div>
                                    </div>

                                    <!-- ACTION -->
                                    <button type="submit" class="btn btn-success w-100 py-4 fw-bold rounded-3" id="submit-data">
                                        Open Register
                                    </button>

                                    <div class="text-muted fs-8 text-center mt-3">
                                        Ensure all cash counts are correct before submission
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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

