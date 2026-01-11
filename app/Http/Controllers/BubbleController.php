<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;

class BubbleController extends Controller
{
    public function showHighscore(){
        $students = Student::pluck('total_score')->toArray();
        return response()->json($students);
    }
    public function bubbleSorter(){
        // get students as associative arrays with name and total_score
        $students = Student::select('username', 'total_score')->get()->toArray();
        $countNumbers = count($students);
        $sorted = false;

        // bubble sort the array by total_score descending
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
