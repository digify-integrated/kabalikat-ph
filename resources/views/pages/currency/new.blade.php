@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Currency Details</h5>
        </div>
        <div class="card-body">
            <form id="currency_form" method="post" action="#" novalidate>
                @csrf
                <div class="row row-cols-1 row-cols-sm-3 rol-cols-md-3 row-cols-lg-3">
                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="currency_name">
                                Currency
                            </label>

                            <input type="text" class="form-control" id="currency_name" name="currency_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="symbol">
                                Symbol
                            </label>

                            <input type="text" class="form-control" id="symbol" name="symbol" maxlength="10" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="shorthand">
                                Shorthand
                            </label>

                            <input type="text" class="form-control" id="shorthand" name="shorthand" maxlength="50" autocomplete="off">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="currency_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush