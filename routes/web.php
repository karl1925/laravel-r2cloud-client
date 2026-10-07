<?php

use R2Cloud\Http\Controllers\Auth\R2AccountsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


Route::middleware('web')->group(
    function () {
    Route::get('/', function () {
        return Auth::check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    });

    Route::get('/auth/accounts/redirect', [R2AccountsController::class, 'redirect'])
        ->name('accounts.login');

    Route::get('/auth/callback', [R2AccountsController::class, 'callback'])
        ->name('accounts.callback');

    Route::post('/logout', [R2AccountsController::class, 'logout'])
        ->name('logout');

    Route::get('/login', function () {
        return redirect()->route('accounts.login');
    })->name('login');
});
