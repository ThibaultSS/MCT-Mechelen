<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/loginRun', [LoginController::class, 'loginRun']);
Route::get('/login', function () {
    return view('login');
});

Route::get('/game/comet-typing', function () {
    return view('game.comet-typing');
})->name('game.comet-typing');