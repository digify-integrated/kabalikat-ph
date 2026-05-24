@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="row g-5 g-xl-9" id="shop_register_container"></div>

   <div id="register-modal" class="modal fade" tabindex="-1" aria-labelledby="register-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl modal-fullscreen-lg-down">
        <div class="modal-content border-0">

            <div class="modal-body p-5">
                <form id="register_form" method="post" action="#">
                    @csrf

                    <input type="hidden" id="shop_register_id" name="shop_register_id">
                    <input type="hidden" id="session" name="session">

                    <!-- HEADER -->
                    <div class="d-flex align-items-center mb-7">

                        <div>
                            <h2 class="fw-bold mb-1">
                                Cash Count
                            </h2>

                            <div class="text-muted fs-7">
                                Enter denomination quantities to compute opening cash
                            </div>
                        </div>

                        <div class="ms-auto">
                            <span class="badge badge-light-success px-4 py-3 fs-7 fw-bold register-badge">
                                Register Opening
                            </span>
                        </div>

                    </div>

                    <div class="row g-6">

                        <!-- LEFT -->
                        <div class="col-xl-8">

                            <!-- BILLS -->
                            <div class="card border-0 shadow-sm rounded-4 mb-6 overflow-hidden">

                                <div class="card-header border-0 bg-light-primary min-h-60px">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">
                                            Bills
                                        </h3>
                                    </div>
                                </div>

                                <div class="card-body p-5">

                                    <div class="row g-4">

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

                                            <div class="col-md-6">

                                                <div class="border border-gray-200 rounded-4 p-4 bg-light h-100 denomination-card">

                                                    <div class="d-flex align-items-center mb-4">

                                                        <div class="fw-bolder text-primary fs-1">
                                                            ₱ {{ explode('.', $label)[0] }}
                                                        </div>

                                                        <div class="ms-auto">
                                                            <span class="badge badge-light-primary fs-8">
                                                                Bill
                                                            </span>
                                                        </div>

                                                    </div>

                                                    <div class="row g-3">

                                                        <!-- QTY -->
                                                        <div class="col-6">

                                                            <label class="fs-8 fw-semibold text-muted mb-2">
                                                                Quantity
                                                            </label>

                                                            <input type="number"
                                                                id="open_{{ $id }}"
                                                                name="open_{{ $id }}"
                                                                class="form-control form-control-solid text-center fw-bold qty-input"
                                                                data-denomination="{{ str_replace(',', '', $label) }}"
                                                                min="0"
                                                                step="1"
                                                                inputmode="numeric"
                                                                placeholder="0" />

                                                        </div>

                                                        <!-- SUBTOTAL -->
                                                        <div class="col-6">

                                                            <label class="fs-8 fw-semibold text-muted mb-2">
                                                                Subtotal
                                                            </label>

                                                            <div class="form-control form-control-solid d-flex align-items-center justify-content-end fw-bolder text-primary">

                                                                ₱&nbsp;

                                                                <span class="subtotal"
                                                                    id="subtotal_{{ $id }}">
                                                                    0.00
                                                                </span>

                                                            </div>

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                        @endforeach

                                    </div>

                                </div>

                            </div>

                            <!-- COINS -->
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                                <div class="card-header border-0 bg-light-warning min-h-60px">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">
                                            Coins
                                        </h3>
                                    </div>
                                </div>

                                <div class="card-body p-5">

                                    <div class="row g-4">

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

                                            <div class="col-md-4 col-6">

                                                <div class="border border-gray-200 rounded-4 p-4 bg-light h-100 denomination-card">

                                                    <div class="d-flex align-items-center mb-3">

                                                        <div class="fw-bolder fs-2 text-dark">
                                                            ₱ {{ $label }}
                                                        </div>

                                                        <div class="ms-auto">
                                                            <span class="badge badge-light-warning fs-8">
                                                                Coin
                                                            </span>
                                                        </div>

                                                    </div>

                                                    <label class="fs-8 fw-semibold text-muted mb-2">
                                                        Quantity
                                                    </label>

                                                    <input type="number"
                                                        id="open_{{ $id }}"
                                                        name="open_{{ $id }}"
                                                        class="form-control form-control-solid text-center fw-bold qty-input mb-3"
                                                        data-denomination="{{ str_replace(',', '', $label) }}"
                                                        min="0"
                                                        step="1"
                                                        inputmode="numeric"
                                                        placeholder="0" />

                                                    <div class="text-end">

                                                        <div class="fs-8 text-muted">
                                                            Subtotal
                                                        </div>

                                                        <div class="fw-bolder text-primary fs-5">
                                                            ₱ <span class="subtotal"
                                                                id="subtotal_{{ $id }}">
                                                                0.00
                                                            </span>
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                        @endforeach

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="col-xl-4">

                            <div class="position-sticky" style="top: 20px;">

                                <!-- TOTAL -->
                                <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">

                                    <div class="bg-success px-6 py-6">

                                        <div class="text-white opacity-75 fs-7 mb-2 total-label">
                                            Opening Cash Total
                                        </div>

                                        <div class="fw-bolder text-white lh-1"
                                            style="font-size: 44px;">

                                            ₱ <span id="open_total">0.00</span>

                                        </div>

                                    </div>

                                    <div class="card-body py-4">

                                        <div class="d-flex align-items-center text-muted fs-7">

                                            <i class="ki-outline ki-information-5 me-2"></i>

                                            Auto-calculated in real time

                                        </div>

                                    </div>

                                </div>

                                <!-- REMARKS -->
                                <div class="card border-0 shadow-sm rounded-4 mb-5">

                                    <div class="card-body">

                                        <label class="fw-semibold fs-6 mb-3" for="remarks">
                                            Remarks
                                        </label>

                                        <textarea class="form-control form-control-solid"
                                            id="remarks"
                                            name="remarks"
                                            rows="6"
                                            maxlength="1000"
                                            placeholder="Optional remarks..."></textarea>

                                    </div>

                                </div>

                                <!-- ACTION -->
                                <button type="submit"
                                    class="btn btn-success w-100 py-4 fw-bold rounded-4 fs-5"
                                    id="submit-data">

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

