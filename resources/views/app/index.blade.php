@extends('layouts.app')

@section('content')
    <div class="row g-6 g-xl-9">
        @forelse ($apps as $app)
            @php
                $defaultLink = route('apps.base', [
                    'appId' => $app->app_id,
                    'navigationMenuId'  => $app->navigation_menu_id,
                ]);

                $defaultLogo = asset('assets/media/default/app-logo.png');

                $path = trim((string) ($app->app_logo ?? ''));

                $logoUrl = $path !== '' && Storage::disk('public')->exists($path)
                    ? Storage::url($path)
                    : $defaultLogo;
                    
                $version = $app->app_version ?? '1.0.0';
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
                                v. {{ $version }}
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-9">
                        <div class="fs-1 fw-bold text-gray-900">
                            {{ $app->app_name }}
                        </div>
                        <p class="text-gray-500 fw-semibold fs-7 mt-1">
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
