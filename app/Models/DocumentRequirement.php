<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * Registrar-configured document requirement: which documents students of a
 * given type (and optional level/program/year scope) must upload with their
 * enrollment application. The actual uploads live in enrollment_documents.
 */
class DocumentRequirement extends Model
{
    use BelongsToSchool;

    /** Mirrors the enrollment wizard's student_type vocabulary. */
    public const STUDENT_TYPES = [
        'new'        => 'New Student',
        'transferee' => 'Transferee',
        'shifter'    => 'Shifter',
        'returnee'   => 'Returnee',
        'regular'    => 'Regular',
        'irregular'  => 'Irregular',
    ];

    /** Student types whose enrollment form shows the upload block. */
    public const UPLOAD_TYPES = ['new', 'transferee', 'shifter', 'returnee'];

    protected $fillable = [
        'school_id',
        'student_type',
        'education_node_id',
        'program_id',
        'year_level',
        'documents',
        'is_active',
        'for_foreigner',
        'nationality',
    ];

    protected $casts = [
        'documents'     => 'array',
        'is_active'     => 'boolean',
        'year_level'    => 'integer',
        'for_foreigner' => 'boolean',
    ];

    public function educationNode()
    {
        return $this->belongsTo(EducationNode::class, 'education_node_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
