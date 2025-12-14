@extends('layouts.auth')

@section('content')
   <form id="otp_form" method="POST" action="#">
        @csrf
        <div class="row gy-3">
            <div class="col-xl-12 mb-2">
                <div class="row">
                    <div class="col-2">
                        <input type="text" class="form-control form-control-lg text-center" id="one" maxlength="1">
                    </div>
                    <div class="col-2">
                        <input type="text" class="form-control form-control-lg text-center" id="two" maxlength="1">
                    </div>
                    <div class="col-2">
                        <input type="text" class="form-control form-control-lg text-center" id="three" maxlength="1">
                    </div>
                    <div class="col-2">
                        <input type="text" class="form-control form-control-lg text-center" id="four" maxlength="1">
                    </div>
                    <div class="col-2">
                        <input type="text" class="form-control form-control-lg text-center" id="five" maxlength="1">
                    </div>
                    <div class="col-2">
                        <input type="text" class="form-control form-control-lg text-center" id="six" maxlength="1">
                    </div>
                </div>
                <div class="mt-3">
                    Did not recieve a code ?<a href="mail.html" class="text-primary ms-2 d-inline-block fw-medium">Resend</a>
                </div>
            </div>
        </div>
    </form>
    <div class="d-grid mt-3">
        <button type="submit" form="otp_form" class="btn btn-primary">Verify</button>
    </div>
    <div class="text-center mt-3 fw-medium">
       <p class="text-danger mt-3 mb-0 fw-medium"><sup><i class="ri-asterisk"></i></sup> Keep the verification code private!</p>
    </div>
@endsection

@push('scripts')

@endpush