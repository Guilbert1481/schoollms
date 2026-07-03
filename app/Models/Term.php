<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\EducationLevels;

class Term extends Model
{
    use HasFactory;

    protected $table = 'terms';

    protected $fillable = [
        'school_id',
        'education_level',
        'education_node_id',
        'academic_year',
        'enrollment_type',
        'academic_year_id',
        'term',
        'title',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'is_active',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    protected $appends = ['can_activate', 'computed_status'];

    /**
     * Keep the legacy `education_level` string ('basic_ed' | 'higher_ed') in sync
     * with the chosen Education Structure Tree root, so downstream consumers that
     * still read the string keep working while the tree node is the source of truth.
     */
    protected static function booted(): void
    {
        static::saving(function (self $term) {
            if ($term->education_node_id) {
                $node = EducationNode::find($term->education_node_id);
                if ($node) {
                    $term->education_level = EducationLevels::isBasic($node->name) ? 'basic_ed' : 'higher_ed';
                }
            }
        });
    }

    /** The education level (Education Structure Tree root) this term belongs to. */
    public function educationLevelNode()
    {
        return $this->belongsTo(EducationNode::class, 'education_node_id');
    }

    public function getComputedStatusAttribute()
{
    $today = now()->startOfDay();
    $start = \Carbon\Carbon::parse($this->start_date)->startOfDay();
    $end   = \Carbon\Carbon::parse($this->end_date)->endOfDay();

    if ($today->lt($start)) {
        return 'upcoming';
    }

    if ($today->gte($start) && $today->lte($end)) {
        return 'active';
    }

    return 'closed';
}

    public function getCanActivateAttribute(): bool
    {
        if ($this->end_date < now()) {
            return false;
        }

        $otherActiveAcademic = self::query()
            ->where('academic_year_id', $this->academic_year_id)
            ->where('school_id', $this->school_id)
            ->where('id', '!=', $this->id)
            ->whereIn('term', ['first','second','third','fourth','summer'])
            ->where('status', 'active')
            ->exists();

        return ! $otherActiveAcademic;
    }

    public function isAcademicTerm(): bool
    {
        return in_array(strtolower($this->term), [
            'first', 'second', 'third', 'fourth', 'summer',
        ]);
    }

    public function isSpecialTerm(): bool
    {
        return ! $this->isAcademicTerm();
    }

    public function subjectOfferings()
    {
        return $this->hasMany(SubjectOffering::class);
    }
}