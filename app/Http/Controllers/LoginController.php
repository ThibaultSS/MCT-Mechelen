<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;

class LoginController extends Controller
{
    public function loginRun(Request $request)
    {
        $username = $request->input('username');
        $email = $request->input('email');

        $student = Student::firstOrCreate(
            ['email' => $email],
            ['username' => $username]
        );

        if ($student->wasRecentlyCreated === false) {
            $student->update(['username' => $username]);
        }

        session([
            'student_id' => $student->id,
            'student_name' => $username,
            'student_email' => $email
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => $student->wasRecentlyCreated 
                    ? 'New student created and logged in' 
                    : 'Login successful'
            ], $student->wasRecentlyCreated ? 201 : 200);
        }

        return redirect('/');
    }
}
