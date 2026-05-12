<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'name',
        'certificate_type',
        'category',
        'training_type_id',
        'layout_json',
        'orientation',
        'paper_size',
        'background_image',
        'logo',
        'elements',
        'is_default'
    ];

    protected $casts = [
        'layout_json' => 'array',
        'elements' => 'array',
    ];

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function events()
    {
        return $this->hasMany(CertificateEvent::class, 'template_id');
    }

    
}
