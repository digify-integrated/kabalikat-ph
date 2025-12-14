@extends('layouts.error')

@section('content')
    <div class="page error-bg">
        <div class="error-page-background">
            <img src="{{ asset('assets/images/media/backgrounds/10.svg') }}" alt="background">
        </div>
        <div class="row align-items-center justify-content-center h-100 g-0">
            <div class="col-xl-7 col-lg-7 col-md-7 col-12">
                <div class="text-center px-2">
                    <div class="text-center mb-5">
                        <img src="{{ asset('assets/images/media/backgrounds/11.png') }}" alt="background" class="w-sm-auto w-100 h-100">
                    </div>
                    <span class="d-block fs-4 text-primary fw-semibold">Too Many Requests</span>
                    <p class="error-text mb-0">419</p>
                    <p class="fs-5 fw-normal mb-0">You are making requests too quickly. Please slow down and try again later.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    
@endpush