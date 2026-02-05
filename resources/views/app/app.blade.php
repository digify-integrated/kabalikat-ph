@extends('layouts.app')

@section('content')
    <div class="row g-6 g-xl-9">
        <div class="col-md-6 col-xl-3">
            <a href="/metronic8/demo34/?page=apps/projects/project" class="card border-hover-primary ">
                <div class="card-header border-0 pt-9">
                    <div class="card-title m-0">
                        <div class="symbol symbol-50px w-50px bg-light">
                            <img src="{{asset('assets/media/default/app-module-logo.png')}}" alt="image" class="p-3"/>
                        </div>
                    </div>

                    <div class="card-toolbar">
                        <span class="badge badge-sm badge-light-primary me-auto px-2 py-2">v. 1.0.0</span>
                    </div>
                </div>
                                                
                <div class="card-body p-9">
                    <div class="fs-3 fw-bold text-gray-900">
                        Point of Sale
                    </div>
                    <p class="text-gray-500 fw-semibold fs-8 mt-1">
                        CRM App application to HR efficiency
                    </p>
                </div>
            </a>
        </div>
    </div>
@endsection