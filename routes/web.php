<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login', [
        'pageTitle' => env('APP_NAME', 'Login'),
        'title' => 'Hi, Welcome back!',
        'description' => 'Please enter your credentials.'
    ]);
})->name('login');

Route::get('/forgot', function () {
    return view('auth.forgot', [
        'pageTitle' => 'Forgot Password',
        'title' => 'Forgot Password?',
        'description' => 'Enter your email to reset your password.'
    ]);
})->name('forgot');

Route::get('/register', function () {
    return view('auth.register', [
        'pageTitle' => 'Register',
        'title' => 'Sign Up',
        'description' => 'Join us by creating a free account!'
    ]); 
})->name('register');

Route::get('/otp', action: function () {
    return view('auth.otp-verification', [
        'pageTitle' => 'Two-Factor',
        'title' => 'Verify Your Account',
        'description' => 'Enter the 6 digit code sent to the registered email.'
    ]);
})->name('otp');

Route::get('/reset-password', action: function () {
    return view('auth.reset-password', [
        'pageTitle' => 'Reset Password',
        'title' => 'Reset Password',
        'description' => 'Set your new password here.'
    ]);
})->name('reset.password');

Route::get('/app', function () {
    return view('app.app', [
        'pageTitle' => 'App',
        'title' => 'Sign Up',
        'description' => 'Join us by creating a free account !'
    ]);
})->name('app');
