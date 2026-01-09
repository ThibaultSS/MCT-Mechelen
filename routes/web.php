<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;

Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {

    return view('login');
});

Route::post('/loginRun', [LoginController::class, 'loginRun']);

Route::get('/game/comet-typing', function () {
    return view('game.comet-typing');
})->name('game.comet-typing');

Route::get('/game/ai-or-not', function () {
    return view('game.ai-or-not');
})->name('game.ai-or-not');