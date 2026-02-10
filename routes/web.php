<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppRenderController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Support\Facades\Route;

// Routes that should NOT be accessible when logged in
Route::middleware('guest')->group(function () {
    Route::view('/', 'auth.login', [
        'pageTitle' => env('APP_NAME', 'Laravel'), // set below if you want
        'title' => 'Hi, Welcome back!',
        'description' => 'Please enter your credentials'
    ])->name('login');

    Route::view('/forgot', 'auth.forgot', [
        'pageTitle' => 'Forgot Password',
        'title' => 'Forgot Password?',
        'description' => 'Enter your email to reset your password.'
    ])->name('forgot');

    Route::view('/register', 'auth.register', [
        'pageTitle' => 'Register',
        'title' => 'Sign Up',
        'description' => 'Join us by creating a free account!'
    ])->name('register');

    Route::view('/otp', 'auth.otp-verification', [
        'pageTitle' => 'Two-Factor',
        'title' => 'Verify Your Account',
        'description' => 'Enter the 6 digit code sent to the registered email.'
    ])->name('otp');

    Route::view('/reset-password', 'auth.reset-password', [
        'pageTitle' => 'Reset Password',
        'title' => 'Reset Password',
        'description' => 'Set your new password here.'
    ])->name('reset.password');

    Route::post('/authenticate', [AuthenticationController::class, 'authenticate'])
        ->name('authenticate');
});

// Routes that require login
Route::middleware('auth')->group(function () {
    Route::get('/app', [AppRenderController::class, 'index'])->name('apps.index');

    Route::middleware(['menu.read', 'breadcrumbs', 'nav.menu'])->group(function () {
        Route::get('/app/{appId}/module/{navigationMenuId}', [AppRenderController::class, 'base'])
            ->name('apps.base');

        Route::get('/app/{appId}/module/{navigationMenuId}/new', [AppRenderController::class, 'new'])
            ->name('apps.new');

        Route::get('/app/{appId}/module/{navigationMenuId}/details/{details_id}', [AppRenderController::class, 'details'])
            ->name('apps.details');

        Route::get('/app/{appId}/module/{navigationMenuId}/import', [AppRenderController::class, 'import'])
            ->name('apps.import');
    });

    Route::prefix('datatable')
        ->as('datatable.')
        ->group(function () {

        Route::get('/apps/{appId}/module/{navigationMenuId}', [AppController::class, 'generateAppTable'])
            ->name('app');
    });

    Route::get('/logout', [AuthenticationController::class, 'logout'])->name('logout');
});
