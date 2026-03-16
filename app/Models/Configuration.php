<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuration extends Model
{
    protected $fillable = [
        'school_id',
        'category',
        'label',
        'value',
        'is_active',
        'order_index',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    // Relationship: Configuration belongs to a School
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Scope: Get active configurations only
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: Get by category
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Helper: Get configurations by category with caching
    public static function getByCategory($category, $schoolId = null)
    {
        $schoolId = $schoolId ?? auth()->user()->school_id ?? 1;
        
        $cacheKey = "config_{$category}_school_{$schoolId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($category, $schoolId) {
            return self::where('school_id', $schoolId)
                ->where('category', $category)
                ->active()
                ->orderBy('order_index')
                ->get();
        });
    }

    // Helper: Clear cache when updating
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($config) {
            Cache::forget("config_{$config->category}_school_{$config->school_id}");
        });

        static::deleted(function ($config) {
            Cache::forget("config_{$config->category}_school_{$config->school_id}");
        });
    }
}