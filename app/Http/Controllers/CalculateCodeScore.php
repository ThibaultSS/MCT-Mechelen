<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Codequest;

class CalculateCodeScore extends Controller
{
    public function calculateScore($score)
    {
        $total_score = (int)($score/10);
        Codequest::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $total_score,
        ]);
                
        Student::where('id', session('student_id'))->update([
            'total_score' => Student::find(session('student_id'))->total_score + $total_score,
        ]);
        return view("game.riddle");
        
    }
}
