@extends('layouts.auth')

@section('content')
   <form id="reset_form" method="POST" action="#">
        @csrf
        <div class="row gy-3">
            <div class="col-xl-12">
                <label for="email" class="form-label text-default">Email</label>
                <input type="text" class="form-control" id="email" name="email" autocomplete="off">
            </div>
        </div>
    </form>
    <div class="d-grid mt-3">
        <button type="submit" form="reset_form" class="btn btn-primary">Submit</button>
    </div>
    <div class="text-center mt-3 fw-medium">
        Already have an account? <a href="{{ route(name: 'login') }}" class="text-primary">Sign In</a>
    </div>
@endsection

@push('scripts')

@endpush