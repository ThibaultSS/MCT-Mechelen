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
        Heaguess::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $score,
        ]);
                
        Student::where('id', session('student_id'))->increment('total_score', $score);
        return view("game.code-quest");
        
    }
}
