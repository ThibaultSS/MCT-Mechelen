<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CalculateCometScore;

Route::get('/', function () {
    return view('login');
});

Route::get('/score/comet-typing', [CalculateCometScore::class, 'calculateScore']);
Route::post('/loginRun', [LoginController::class, 'loginRun']);

Route::get('/login', function () {

    return view('login');
});

Route::get('/game/comet-typing', function () {
    return view('game.comet-typing');
})->name('game.comet-typing');