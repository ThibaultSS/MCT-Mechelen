<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Riddle;
use App\Models\Student;

class CalculateRiddleScore extends Controller
{
    public function calculateScore(Request $request)
    {
        $score = (int) $request->input('score');
        $score = min($score, 5);
        $total_score = (int)($score * 4);
        if(Riddle::where('student_id', session('student_id'))->exists()){
            return view("game.highscore"); 
        }
        else{
            Riddle::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $total_score,
        ]);
            return view("game.highscore"); 
        }  

    }
}
