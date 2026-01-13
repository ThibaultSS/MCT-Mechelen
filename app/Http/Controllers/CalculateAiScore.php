<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Aiornot;
use App\Models\Student;

class CalculateAiScore extends Controller
{
    public function calculateScore(Request $request)
    {
        $score = (int) $request->input('score');
        Aiornot::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $score,
        ]);
        Student::where('id', session('student_id'))->increment('total_score', $score);
        return view("game.hexa-guess");   

    } 
}
