<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectOffering extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'term_id',
        'program_id',
        'year_level',
        'offering_code',
        'delivery_mode',
        'teacher_id',
        'max_slots',
        'enrolled_count',
        'status',
        'is_open',
        'is_for_irregular',
    ];

    protected $casts = [
        'is_open'          => 'boolean',
        'is_for_irregular' => 'boolean',
        'year_level'       => 'integer',
        'max_slots'        => 'integer',
        'enrolled_count'   => 'integer',
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /* -----------------------------------------------------------------
     |  Display accessors (used by config-driven shareable table)
     | ----------------------------------------------------------------- */

    public function getSubjectLabelAttribute(): string
    {
        $subject = $this->relationLoaded('subject') ? $this->subject : $this->subject()->first();
        if (! $subject) return '—';
        return trim(($subject->code ?? '') . ' — ' . ($subject->name ?? ''), ' —');
    }

    public function getProgramLabelAttribute(): string
    {
        $program = $this->relationLoaded('program') ? $this->program : null;
        return $program?->name ?? 'All Programs';
    }

    public function getYearLevelLabelAttribute(): string
    {
        return $this->year_level ? (string) $this->year_level : '—';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_open
            ? '<span class="text-emerald-700 font-bold">Open</span>'
            : '<span class="text-slate-500">Closed</span>';
    }

    public function getIrregularLabelAttribute(): string
    {
        return $this->is_for_irregular ? 'Yes' : 'No';
    }
}
