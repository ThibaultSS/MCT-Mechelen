<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
public function loginRun(Request $request)
{
    $request->validate([
        'email' => ['required', 'email'],
        'username' => [
            'required',
            Rule::unique('students', 'username')
                ->ignore($request->email, 'email'),
        ],
    ]);

    $student = Student::updateOrCreate(
        ['email' => $request->email],
        ['username' => $request->username]
    );

    session(['student_id' => $student->id]);

    return view('welcome');
}
}
