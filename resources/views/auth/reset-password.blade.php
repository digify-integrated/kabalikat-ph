@extends('layouts.auth')

@section('content')
   <form id="reset_password_form" method="POST" action="#">
        @csrf
        <div class="row gy-3">
            <div class="col-xl-12">
                <div class="row">
                    <div class="col-xl-12 mb-2">
                        <label for="new_password" class="form-label text-default d-block">Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="off">
                            <span class="show-password-button text-muted">
                                <i class="ri-eye-off-line align-middle"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xl-12">
                    <label for="confirm_password" class="form-label text-default d-block">Confirm Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="off">
                        <span class="show-password-button text-muted">
                            <i class="ri-eye-off-line align-middle"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="d-grid mt-3">
        <button type="submit" form="reset_password_form" class="btn btn-primary">Reset Password</button>
    </div>
@endsection

@push('scripts')
    
@endpush