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

Route::get('/game/ai-or-not', function () {
    return view('game.ai-or-not');
})->name('game.ai-or-not');

Route::get('/game/hexa-guess', function () {
    return view('game.hexa-guess');
})->name('game.hexa-guess');

Route::get('/game/code-quest', function () {
    return view('game.code-quest');
})->name('game.code-quest');

Route::get('/game/riddle', function () {
    return view('game.riddle');
})->name('game.riddle');