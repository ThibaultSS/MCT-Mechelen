<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Heaguess;

class CalculateHexaScore extends Controller
{
    public function calculateScore($score)
    {
        Heaguess::create([
            'student_id' => session('student_id'),
            'time' => 0,
            'score' => $score,
            'total_score' => $score,
        ]);
                
        Student::where('id', session('student_id'))->update([
            'total_score' => Student::find(session('student_id'))->total_score + $score,
        ]);
        return view("game.comet");
        
    }
}
