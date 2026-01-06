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

        if ($student) {
            Student::where('email', $email)->update(['username' => $username]);
            return view("welcome");
        } else {
            Student::create([
                'username' => $username,
                'email' => $email,
            ]);
            return view("welcome");
        }
    }
}
