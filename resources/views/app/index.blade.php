@extends('layouts.app')

@section('content')
    <div class="row g-6 g-xl-9">
        @forelse ($apps as $app)
            @php
                // Link (adjust params if your route expects different names)
                $defaultLink = route('apps.index', [
                    'appModuleId' => $app->app_id,
                    'menuItemId'  => $app->navigation_menu_id,
                ]);

                // Logo URL:
                // If app_logo is stored like "app/1/settings.png" on the public disk
                $logoUrl = !empty($app->app_logo)
                        ? asset('storage/' . ltrim($app->app_logo, '/'))
                        : asset('assets/media/default/app-module-logo.png');

                // Optional version (if you don't have a version column yet)
                $version = $app->version ?? 'v. 1.0.0';
            @endphp

            <div class="col-md-6 col-xl-3">
                <a href="{{ $defaultLink }}" class="card border-hover-primary">
                    <div class="card-header border-0 pt-9">
                        <div class="card-title m-0">
                            <div class="symbol symbol-50px w-50px bg-light">
                                <img src="{{ $logoUrl }}" alt="image" class="p-3" />
                            </div>
                        </div>

                        <div class="card-toolbar">
                            <span class="badge badge-sm badge-light-primary me-auto px-2 py-2">
                                {{ $version }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-9">
                        <div class="fs-3 fw-bold text-gray-900">
                            {{ $app->app_name }}
                        </div>
                        <p class="text-gray-500 fw-semibold fs-8 mt-1">
                            {{ $app->app_description }}
                        </p>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    No apps available for your account. 
                </div>
            </div>
        @endforelse
    </div>
@endsection
