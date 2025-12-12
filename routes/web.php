<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login', [
        'pageTitle' => env('APP_NAME', 'Login'),
        'title' => 'Hi, Welcome back!',
        'description' => 'Please enter your credentials'
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
        'description' => 'Join us by creating a free account !'
    ]); 
})->name('register');

Route::get('/app', function () {
    return view('app.app', [
        'pageTitle' => 'App',
        'title' => 'Sign Up',
        'description' => 'Join us by creating a free account !'
    ]);
})->name('app');
