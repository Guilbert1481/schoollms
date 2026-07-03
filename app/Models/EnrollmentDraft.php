<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrollmentDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'term_id',
        'data',
    ];

    /**
     * `data` is a JSON object holding the in-progress wizard steps.
     *
     * It is stored single-encoded. On read we decode up to twice so legacy rows
     * that were accidentally double-encoded (saved via json_encode() into the
     * array-cast column) still load as an array — and they self-heal to a clean
     * single-encoded value the next time the draft is saved.
     */
    protected function data(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                for ($i = 0; $i < 2 && is_string($value); $i++) {
                    $value = json_decode($value, true);
                }

                return is_array($value) ? $value : [];
            },
            set: fn ($value) => (is_array($value) || is_object($value)) ? json_encode($value) : $value,
        );
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
