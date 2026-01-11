<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Typeracer;
use App\Models\Student;

class CalculateCometScore extends Controller
{
    public function calculateScore($score)
    {
        $total_score = (int)($score / 5);
        Typeracer::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $total_score,
        ]);
                
        Student::where('id', session('student_id'))->update([
            'total_score' => Student::find(session('student_id'))->total_score + $total_score,
        ]);
        return view("game.ai-or-not");   

    }
}
