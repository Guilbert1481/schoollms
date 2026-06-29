<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-school configuration for the Student Digital ID:
 *  - orientation     : portrait | landscape  (drives the Profile tab layout)
 *  - barcode_source  : lrn | student_number  (what the ID barcode encodes)
 *  - show_back       : whether the ID has a Back side
 *
 * The visual ID design itself is handled elsewhere (the ID builder); this only
 * holds the display options.
 */
class StudentIdSetting extends Model
{
    protected $fillable = [
        'school_id',
        'orientation',
        'barcode_source',
        'show_back',
    ];

    protected $casts = [
        'show_back' => 'boolean',
    ];

    public const ORIENTATIONS = ['portrait', 'landscape'];
    public const BARCODE_SOURCES = ['lrn', 'student_number'];

    /** Get (or create with defaults) the settings row for a school. */
    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId],
            ['orientation' => 'portrait', 'barcode_source' => 'lrn', 'show_back' => true]
        );
    }
}
