@extends('layouts.app')

@section('content')
    <div class="row justify-content-center mt-10">
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

            <div class="col-4 col-xl-2 mb-7 d-flex justify-content-center">
                <a href="{{ $defaultLink }}"
                class="card app-card text-decoration-none h-100">

                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-5">

                        <!-- App Icon -->
                        <div class="app-icon-wrapper position-relative mb-5">

                            <div class="app-icon bg-white rounded-5 shadow-sm overflow-hidden">
                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ $app->app_name }}"
                                    class="w-100 h-100 object-fit-contain p-4"
                                />
                            </div>

                            <!-- Version Badge -->
                            <span class="badge badge-light-primary fw-bold px-3 py-2 app-version-badge">
                                v{{ $version }}
                            </span>

                        </div>

                        <!-- App Name -->
                        <h3 class="app-title fw-bold text-gray-900 mb-0">
                            {{ $app->app_name }}
                        </h3>

                    </div>
                </a>
            </div>

        @empty
            <div class="col-12">
                <div class="alert alert-primary mb-0">
                    No apps available for your account. 
                </div>
            </div>
        @endforelse
    </div>
@endsection
