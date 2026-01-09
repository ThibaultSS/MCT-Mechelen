<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Aiornot;

class CalculateAiScore extends Controller
{
    public function calculateScore($score)
    {
        Typeracer::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $score,
        ]);
        return view("welcome");   

    } 
}
