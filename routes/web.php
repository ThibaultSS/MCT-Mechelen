<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

Route::get('/', function () {
    // If student is logged in, show games page, otherwise redirect to login
    if (session()->has('student_id')) {
        return view('welcome');
    }
    return redirect('/login');
});

Route::get('/login', function () {
    // If already logged in, redirect to home
    if (session()->has('student_id')) {
        return redirect('/');
    }
    return view('login');
});

Route::post('/loginRun', [LoginController::class, 'loginRun']);

Route::get('/game/comet-typing', function () {
    return view('game.comet-typing');
})->name('game.comet-typing');