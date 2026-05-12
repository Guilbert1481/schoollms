<?php

namespace App\Modules\AcadEnrolment\Services\Contracts;

/**
 * Standardised return value for any validator.
 *
 *   $r = ValidationResult::pass();
 *   $r = ValidationResult::fail('Subject X has unmet prerequisite Y.', ['subject_id' => 42]);
 *   $r = ValidationResult::warn('Student is overloaded by 3 units.');
 */
class ValidationResult
{
    public const SEVERITY_PASS = 'pass';
    public const SEVERITY_WARN = 'warn';
    public const SEVERITY_FAIL = 'fail';

    /** @param array<int, array{severity:string,message:string,context:array}> $issues */
    public function __construct(public array $issues = []) {}

    public static function pass(): self
    {
        return new self();
    }

    public static function fail(string $message, array $context = []): self
    {
        return (new self())->add(self::SEVERITY_FAIL, $message, $context);
    }

    public static function warn(string $message, array $context = []): self
    {
        return (new self())->add(self::SEVERITY_WARN, $message, $context);
    }

    public function add(string $severity, string $message, array $context = []): self
    {
        $this->issues[] = compact('severity', 'message', 'context');
        return $this;
    }

    public function merge(self $other): self
    {
        $this->issues = array_merge($this->issues, $other->issues);
        return $this;
    }

    public function passed(): bool
    {
        foreach ($this->issues as $i) {
            if ($i['severity'] === self::SEVERITY_FAIL) {
                return false;
            }
        }
        return true;
    }

    public function hasWarnings(): bool
    {
        foreach ($this->issues as $i) {
            if ($i['severity'] === self::SEVERITY_WARN) {
                return true;
            }
        }
        return false;
    }

    public function failures(): array
    {
        return array_values(array_filter(
            $this->issues,
            fn ($i) => $i['severity'] === self::SEVERITY_FAIL
        ));
    }

    public function toArray(): array
    {
        return [
            'passed'      => $this->passed(),
            'has_warnings'=> $this->hasWarnings(),
            'issues'      => $this->issues,
        ];
    }
}
