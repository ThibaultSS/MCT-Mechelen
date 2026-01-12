<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Riddle extends Model
{
    protected $fillable = [
        'student_id',
        'score',
        'time',
        'total_score',
    ];
}
