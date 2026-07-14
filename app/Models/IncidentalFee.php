<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A one-off / incidental charge (school-play ticket, fair t-shirt, field-trip
 * levy). Saving one fans out a one-time invoice to the enrolled students it
 * covers — see App\Services\Finance\IncidentalBillingService.
 */
class IncidentalFee extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'education_node_id',
        'program_id',
        'year_level',
        'academic_year_id',
        'amount',
        'due_date',
        'is_active',
        'charged_at',
        'created_by',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'is_active'  => 'boolean',
        'charged_at' => 'datetime',
        // due_date is intentionally left uncast so it serialises as a plain
        // "YYYY-MM-DD" string the edit modal's <input type="date"> can consume.
    ];

    public function educationNode()
    {
        return $this->belongsTo(EducationNode::class, 'education_node_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
