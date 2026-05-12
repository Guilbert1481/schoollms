<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    protected $fillable = [
        'school_id',
        'code',
        'name',
        'office_type_id',
        'office_head_id'
    ];

    public function type()
{
    return $this->belongsTo(OfficeType::class, 'office_type_id');
}

public function head()
{
    return $this->belongsTo(Staff::class, 'office_head_id');
}
}