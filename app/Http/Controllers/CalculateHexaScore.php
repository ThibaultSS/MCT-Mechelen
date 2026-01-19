<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Heaguess;

class CalculateHexaScore extends Controller
{
    public function calculateScore(Request $request)
    {
        $score = (int) $request->input('score');
        $score = min($score, 10);
        $total_score = (int)($score*2);
        if(Heaguess::where('student_id', session('student_id'))->exists()){
            return view("game.code-quest"); 
        }
        else{
            Heaguess::create([
                'student_id' => session('student_id'),
                'time' => 0,
                'score' => $score,
                'total_score' => $total_score,
            ]);
            return view("game.code-quest");         
        }
        
    }
}
