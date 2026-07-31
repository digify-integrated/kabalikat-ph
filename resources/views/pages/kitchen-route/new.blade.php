@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Kitchen Route Details</h5>
        </div>
        <div class="card-body">
            <form id="kitchen_route_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="fv-row mb-4">
                    <label class="fs-6 fw-semibold required form-label mt-3" for="kitchen_route_name">
                        Kitchen Route
                    </label>

                    <input type="text" class="form-control" id="kitchen_route_name" name="kitchen_route_name" maxlength="100" autocomplete="off">
                </div>
                
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="printer_ip">
                                Printer IP Address
                            </label>

                            <input type="text" class="form-control" id="printer_ip" name="printer_ip" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="printer_port">
                                Printer Port
                            </label>

                           <input type="number" class="form-control" id="printer_port" name="printer_port" min="0" step="1">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="kitchen_route_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush