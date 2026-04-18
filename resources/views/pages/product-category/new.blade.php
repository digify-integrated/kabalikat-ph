@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Product Category Details</h5>
        </div>
        <div class="card-body">
            <form id="product_category_form" method="post" action="#" novalidate>
                @csrf
                <div class="fv-row mb-4">
                    <label class="fs-6 fw-semibold required form-label mt-3" for="product_category_name">
                        Product Category
                    </label>

                    <input type="text" class="form-control" id="product_category_name" name="product_category_name" maxlength="100" autocomplete="off">
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="product_category_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush