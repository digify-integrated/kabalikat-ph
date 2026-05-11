@extends('layouts.module')

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0">Product Details</h5>
        </div>
        <div class="card-body">
            <form id="product_form" method="post" action="#" novalidate>
                @csrf
                
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="product_name">
                                Product Name
                            </label>

                            <input type="text" class="form-control" id="product_name" name="product_name" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-5 rol-cols-md-5 row-cols-lg-5">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold form-label mt-3" for="sku">
                                SKU
                            </label>

                            <input type="text" class="form-control" id="sku" name="sku" maxlength="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold form-label mt-3" for="barcode">
                                Barcode
                            </label>

                            <input type="text" class="form-control" id="barcode" name="barcode" maxlength="100" autocomplete="off">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="product_type">
                                Product Type
                            </label>

                            <select id="product_type" name="product_type" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="Goods">Goods</option>
                                <option value="Service">Service</option>
                            </select>
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="product_status">
                                Product Status
                            </label>

                            <select id="product_status" name="product_status" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="tax_classification">
                                Tax Classification
                            </label>

                            <select id="tax_classification" name="tax_classification" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="Vatable">Vatable</option>
                                <option value="VAT Exempt">VAT Exempt</option>
                                <option value="Zero Rated">Zero Rated</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-5 rol-cols-md-5 row-cols-lg-5">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="base_price">
                                Base Price
                            </label>

                            <input type="number" class="form-control" id="base_price" name="base_price" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="cost_price">
                                Cost Price
                            </label>

                            <input type="number" class="form-control" id="cost_price" name="cost_price" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="base_unit_id">
                                Base Unit
                            </label>

                            <select id="base_unit_id" name="base_unit_id" class="form-select" data-control="select2" data-allow-clear="false">
                                <option>--</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="inventory_flow">
                                Inventory Flow
                            </label>

                            <select id="inventory_flow" name="inventory_flow" class="form-select" data-hide-search="true" data-control="select2" data-allow-clear="false">
                                <option value="FIFO">FIFO</option>
                                <option value="FEFO">FEFO</option>
                                <option value="LIFO">LIFO</option>
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold required form-label mt-3" for="reorder_level">
                                Reorder Level
                            </label>

                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="fv-row mb-4">
                            <label class="fs-6 fw-semibold form-label mt-3" for="product_description">
                                Description
                            </label>

                            <textarea class="form-control" id="product_description" name="product_description" maxlength="200" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-footer d-flex justify-content-end py-6 px-9">
            <button type="button" id="discard-create" class="btn btn-light btn-active-light-primary me-2">Discard</button>
            <button type="submit" form="product_form" class="btn btn-primary" id="submit-data">Save</button>
        </div>
    </div>
@endsection

@push('scripts')
    @if (!empty($jsFile))
        <script type="module" src="{{ asset('assets/js/pages/' . $jsFile . '.js') }}"></script>
    @endif
@endpush