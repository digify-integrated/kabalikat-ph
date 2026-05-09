@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Floor Plan Details</h5>
        </div>
        <div class="card-body">
            <form id="floor_plan_form" method="post" action="#" novalidate>
                @csrf
                <div class="row">
                    <div class="col">
                        <label class="fs-6 fw-semibold required form-label mt-3" for="floor_plan_name">
                            Display Name
                        </label>

                        <input type="text" class="form-control" id="floor_plan_name" name="floor_plan_name" maxlength="100" autocomplete="off">
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="floor_plan_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush