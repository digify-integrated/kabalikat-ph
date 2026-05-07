@extends('layouts.module')

@push('css')
    <link href="{{ asset('assets/plugins/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/plugins/bootstrap-duallistbox/dist/bootstrap-duallistbox.min.css') }}" rel="stylesheet" type="text/css"/>
@endpush

@section('content')
    <div class="row g-5 g-xl-8 mb-5">
        <div class="col-xl-3">
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100 bg-danger">
                <div class="card-header pt-5 mb-0">
                    <div class="d-flex flex-center rounded-circle h-80px w-80px"> 
                        <i class="ki-duotone ki-tag-cross text-white fs-3x lh-0"></i>             
                    </div>    
                </div>

                <div class="card-body d-flex align-items-end mb-3">
                    <div class="d-flex align-items-center">
                        <span class="fs-4hx text-white fw-bold me-6" id="out-of-stock-count">0</span>

                        <div class="fw-bold fs-6 text-white">
                            <span class="d-block fs-2">Out of Stock</span>
                            <span class="">Requires restock</span>
                        </div>            
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100 bg-warning">
                <div class="card-header pt-5 mb-0">
                    <div class="d-flex flex-center rounded-circle h-80px w-80px"> 
                        <i class="ki-duotone ki-calendar-remove text-white fs-3x lh-0"></i>             
                    </div>    
                </div>

                <div class="card-body d-flex align-items-end mb-3">
                    <div class="d-flex align-items-center">
                        <span class="fs-4hx text-white fw-bold me-6" id="expired-items-count">0</span>

                        <div class="fw-bold fs-6 text-white">
                            <span class="d-block fs-2">Expired Items</span>
                            <span class="">Remove from shelf</span>
                        </div>            
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100 bg-info">
                <div class="card-header pt-5 mb-0">
                    <div class="d-flex flex-center rounded-circle h-80px w-80px"> 
                        <i class="ki-duotone ki-information text-white fs-3x lh-0"></i>             
                    </div>    
                </div>

                <div class="card-body d-flex align-items-end mb-3">
                    <div class="d-flex align-items-center">
                        <span class="fs-4hx text-white fw-bold me-6" id="low-stock-count">0</span>

                        <div class="fw-bold fs-6 text-white">
                            <span class="d-block fs-2">Low Stock</span>
                            <span class="">Below threshold</span>
                        </div>            
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100 bg-primary">
                <div class="card-header pt-5 mb-0">
                    <div class="d-flex flex-center rounded-circle h-80px w-80px"> 
                        <i class="ki-duotone ki-calendar text-white fs-3x lh-0"></i>             
                    </div>    
                </div>

                <div class="card-body d-flex align-items-end mb-3">
                    <div class="d-flex align-items-center">
                        <span class="fs-4hx text-white fw-bold me-6" id="expiring-soon-count">0</span>

                        <div class="fw-bold fs-6 text-white">
                            <span class="d-block fs-2">Expiring Soon</span>
                            <span class="">Next 30 days</span>
                        </div>            
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
                        <span class="card-label fw-bold fs-3 mb-1">
                            Out of stock products
                        </span>
                        <span class="text-muted mt-1 fw-semibold fs-7">
                            These products are at zero and cannot be sold.
                        </span>
                    </h3>
                </div>

                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Product</th>
                                    <th class="min-w-100px">Warehouse</th>
                                </tr>
                            </thead>
                            
                            <tbody class="fw-bold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="/metronic8/demo8/?page=apps/ecommerce/catalog/edit-product" class="text-gray-800 text-hover-primary">
                                            #XGY-346
                                        </a>
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-5">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">
                            Expired Stock
                        </span>
                        <span class="text-muted mt-1 fw-semibold fs-7">
                            Items that are no longer safe or legal to sell
                        </span>
                    </h3>
                </div>

                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Product</th>
                                    <th class="min-w-100px">Warehouse</th>
                                    <th class="min-w-100px">Batch Number</th>
                                    <th class="min-w-100px">Qty</th>
                                    <th class="min-w-100px">Expiration Date</th>
                                </tr>
                            </thead>
                                
                            <tbody class="fw-bold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="/metronic8/demo8/?page=apps/ecommerce/catalog/edit-product" class="text-gray-800 text-hover-primary">
                                            #XGY-346
                                        </a>
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                        <span class="card-label fw-bold fs-3 mb-1">
                            Low Inventory
                        </span>
                        <span class="text-muted mt-1 fw-semibold fs-7">
                            Items that will run out if not replenished soon
                        </span>
                    </h3>
                </div>

                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Product</th>
                                    <th class="min-w-100px">Warehouse</th>
                                    <th class="min-w-100px">Current Qty</th>
                                    <th class="min-w-100px">Reorder At</th>
                                </tr>
                            </thead>
                                
                            <tbody class="fw-bold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="/metronic8/demo8/?page=apps/ecommerce/catalog/edit-product" class="text-gray-800 text-hover-primary">
                                            #XGY-346
                                        </a>
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card mb-5">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">
                            Near Expiry
                        </span>
                        <span class="text-muted mt-1 fw-semibold fs-7">
                            Items approaching their expiration date
                        </span>
                    </h3>
                </div>

                <div class="card-body py-3">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Product</th>
                                    <th class="min-w-100px">Warehouse</th>
                                    <th class="min-w-100px">Batch Number</th>
                                    <th class="min-w-100px">Qty</th>
                                    <th class="min-w-100px">Expiration Date</th>
                                </tr>
                            </thead>
                                
                            <tbody class="fw-bold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="/metronic8/demo8/?page=apps/ecommerce/catalog/edit-product" class="text-gray-800 text-hover-primary">
                                            #XGY-346
                                        </a>
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                    <td>
                                        7 min ago
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
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

