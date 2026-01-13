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
        $total_score = (int)($score/10);
        Codequest::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $total_score,
        ]);
                
        Student::where('id', session('student_id'))->increment('total_score', $total_score);
        
        return view("game.riddle");
        
    }
}
