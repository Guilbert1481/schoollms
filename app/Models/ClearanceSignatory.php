<?php

namespace App\Models;

use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ClearanceSignatory extends Model
{
    use BelongsToSchool;

    public const TYPE_DEPARTMENT = 'department';

    public const TYPE_SUBJECT_TEACHERS = 'subject_teachers';

    public const APPLIES_BASIC = 'basic';

    public const APPLIES_HIGHER = 'higher';

    public const APPLIES_BOTH = 'both';

    protected $fillable = [
        'school_id',
        'name',
        'type',
        'applies_to',
        'sort_order',
    ];

    public function items()
    {
        return $this->hasMany(ClearanceItem::class);
    }
}
