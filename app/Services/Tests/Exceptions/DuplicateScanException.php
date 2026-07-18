<?php

namespace App\Services\Tests\Exceptions;

use RuntimeException;

/**
 * Thrown when a sheet that already has a graded result is scanned again without
 * explicit re-scan authorization — so an accidental double scan can never
 * silently overwrite a recorded score.
 */
class DuplicateScanException extends RuntimeException
{
    public function __construct(public readonly int $sheetId)
    {
        parent::__construct('This sheet already has a recorded result; re-scan must be explicitly authorized.');
    }
}
