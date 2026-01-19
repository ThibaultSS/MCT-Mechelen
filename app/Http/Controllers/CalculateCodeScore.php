<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Codequest;

class CalculateCodeScore extends Controller
{
    public function calculateScore(Request $request)
    {
        $score = (int) $request->input('score');
        $score = min($score, 100);
        $total_score = (int)($score/5);
        if(Codequest::where('student_id', session('student_id'))->exists()){
            return view("game.riddle");
        }
        else{
            Codequest::create([
                'student_id' => session('student_id'),
                'time' => 0,
                'score' => $score,
                'total_score' => $total_score,
            ]);   
            return view("game.riddle");         
        }
        
    }
}
