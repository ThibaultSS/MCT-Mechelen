<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Typeracer;
use App\Models\Student;

class CalculateCometScore extends Controller
{
    public function calculateScore(Request $request)
    {
        $score = (int) $request->input('score');
        $score = min($score, 50);
        $total_score = (int)($score / 2.5);
        if(Typeracer::where('student_id', session('student_id'))->exists()){
            return view("game.ai-or-not"); 
        }
        else{
            Typeracer::create([
                'student_id' => session('student_id'),
                'time' => 0,
                'score' => $score,
                'total_score' => $total_score,
            ]);
            return view("game.ai-or-not");          
        }

    }
}
