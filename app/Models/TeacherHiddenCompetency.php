<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A teacher's private "hide" of a Course-Architect competency (see the
 * teacher_hidden_competencies migration). Scoped by user_id, never global.
 */
class TeacherHiddenCompetency extends Model
{
    protected $fillable = [
        'user_id',
        'competency_id',
    ];
}
