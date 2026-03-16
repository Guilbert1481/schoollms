<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    // In app/Models/Pricing.php
    protected $fillable = [
        'plan_name', 
        'student_price', 
        'teacher_price', 
        'staff_price', 
        'parent_price', 
        'alumni_price', 
        'setup_fee', 
        'addon_price'
    ];
}
