<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationNode extends Model
{
    use HasFactory;

    public const TYPE_LEVEL        = 'level';
    public const TYPE_STAGE        = 'stage';
    public const TYPE_TRACK        = 'track';
    public const TYPE_STRAND       = 'strand';
    public const TYPE_PROGRAM_TYPE = 'program_type';

    public const TYPES = [
        self::TYPE_LEVEL        => 'Level',
        self::TYPE_STAGE        => 'Stage',
        self::TYPE_TRACK        => 'Track',
        self::TYPE_STRAND       => 'Strand',
        self::TYPE_PROGRAM_TYPE => 'Program Type',
    ];

    protected $fillable = [
        'name',
        'parent_id',
        'node_type',
        'order_index',
        'is_offered',
        'is_active',
    ];

    protected $casts = [
        'is_offered'  => 'boolean',
        'is_active'   => 'boolean',
        'order_index' => 'integer',
        'parent_id'   => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_index')->orderBy('id');
    }

    /**
     * Eager-load all nested descendants.
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Returns "Parent > Child > Node" by walking up the tree.
     */
    public function getFullPathAttribute(): string
    {
        $segments = [$this->name];
        $node     = $this->parent;
        while ($node) {
            array_unshift($segments, $node->name);
            $node = $node->parent;
        }

        return implode(' > ', $segments);
    }

    /**
     * Roots ordered for tree rendering.
     */
    public static function tree()
    {
        return self::with('childrenRecursive')
            ->whereNull('parent_id')
            ->orderBy('order_index')
            ->orderBy('id')
            ->get();
    }
}
