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
        $score = min($score, 10);
        $total_score = (int)($score*2);
        if(Aiornot::where('student_id', session('student_id'))->exists()){
            return view("game.hexa-guess"); 
        }
        else{
            Aiornot::create([
                'student_id' => session('student_id'),
                'time' => 0,
                'score' => $score,
                'total_score' => $total_score,
            ]);        
            return view("game.hexa-guess");     
        }

    } 
}
