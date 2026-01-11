<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CalculateCometScore;
use App\Http\Controllers\CalculateAiScore;
use App\Http\Controllers\CalculateHexaScore;

Route::get('/', function () {
    return view('login');
});

Route::get('/score/comet-typing/{score}', [CalculateCometScore::class, 'calculateScore']);
Route::get('/score/ai-or-not/{score}', [CalculateAiScore::class, 'calculateScore']);
Route::get('/score/hexa-guess/{score}', [CalculateHexaScore::class, 'calculateScore']);
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