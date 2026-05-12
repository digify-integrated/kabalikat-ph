@extends('layouts.app')

@section('content')
    <div class="row g-6 g-xl-9 my-2">
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

            <div class="col-sm-6 col-xl-4 mb-5">
                <a href="{{ $defaultLink }}"
                class="card app-card h-100 border-0 text-decoration-none">

                    <div class="card-header bg-transparent border-0 pt-6 px-6">
                        <div class="d-flex align-items-center justify-content-between w-100">

                            <div class="symbol symbol-60px rounded-4 bg-light-primary position-relative flex-shrink-0">

                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ $app->app_name }}"
                                    class="w-100 h-100 object-fit-contain p-3 rounded-4"
                                />

                                <span class="badge badge-light-primary fw-semibold px-2 py-1 fs-9 app-version-badge">
                                    v{{ $version }}
                                </span>
                            </div>

                        </div>
                    </div>

                    <div class="card-body px-6 pb-6 pt-4 d-flex flex-column">
                        <h3 class="fw-bold text-gray-900 mb-2 text-hover-primary transition">
                            {{ $app->app_name }}
                        </h3>

                        <p class="text-gray-600 fs-7 fw-medium mb-0 text-truncate">
                            {{ $app->app_description }}
                        </p>

                        <button type="button"
                            class="mt-5 d-inline-flex align-items-center gap-2 text-primary fw-semibold fs-7 btn btn-link p-0 text-decoration-none app-open-btn">

                            <span>Open App</span>

                            <i class="ki-duotone ki-arrow-right fs-5 transition">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </button>
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
