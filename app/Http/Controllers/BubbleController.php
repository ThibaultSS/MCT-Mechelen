<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Codequest;
use App\Models\Riddle;
use App\Models\Aiornot;
use App\Models\Heaguess;
use App\Models\Typeracer;

class BubbleController extends Controller
{
    public function calculateAll(){
        $students = Student::all();

        foreach ($students as $student) {
            $id = $student->id;

            $sum = 0;
            $sum += Codequest::where('student_id', $id)->sum('total_score');
            $sum += Riddle::where('student_id', $id)->sum('total_score');
            $sum += Aiornot::where('student_id', $id)->sum('total_score');
            $sum += Heaguess::where('student_id', $id)->sum('total_score');
            $sum += Typeracer::where('student_id', $id)->sum('total_score');

            $student->total_score = $sum;
            $student->save();
        }
    }
    public function showHighscore(){
        $this->calculateAll();
        $students = Student::pluck('total_score')->toArray();
        return response()->json($students);
    }
    public function bubbleSorter(){
        $this->calculateAll();
        $students = Student::select('username', 'total_score')->get()->toArray();
        $countNumbers = count($students);
        $sorted = false;

        while($sorted == false){
            $sorted = true;
            for($i = 0; $i < $countNumbers-1; $i++){
                if($students[$i]['total_score'] < $students[$i + 1]['total_score']){
                    $temp = $students[$i];
                    $students[$i] = $students[$i + 1];
                    $students[$i + 1] = $temp;
                    $sorted = false;
                }
            }
        }

        return response()->json($students);
    }
}
