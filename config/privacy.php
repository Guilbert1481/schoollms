<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Applicant PII Retention  (Roadmap D3 — RA 10173)
    |--------------------------------------------------------------------------
    |
    | How long a *never-enrolled* applicant's personal data is kept before the
    | `pii:purge-applicants` command erases it. Enrollment creates a Student row
    | (and drafts / health / academic / document files) up front, so a rejected
    | or abandoned applicant leaves a full PII footprint that must not linger
    | past its purpose. These are policy numbers — tune per your DPO without a
    | code change. All windows are measured from the applicant's LAST activity.
    |
    */

    // Applicants who submitted and got a terminal decision (rejected, exam
    // failed, cancelled): kept this many months for re-application / appeal.
    'applicant_retention_months' => (int) env('PII_APPLICANT_RETENTION_MONTHS', 12),

    // Never-submitted wizard drafts have no appeal value — purge them sooner.
    'abandoned_draft_days' => (int) env('PII_ABANDONED_DRAFT_DAYS', 90),

    // 'hard'  = delete the Student + child PII rows + files (+ applicant User).
    // 'scrub' = keep a skeleton Student row with PII columns nulled, still
    //           deleting child PII rows and files.
    'purge_depth' => env('PII_PURGE_DEPTH', 'hard'),

    // In hard mode, also delete the applicant's login (User) when it is a plain
    // student account with no other student and no parent-portal link.
    'delete_applicant_user' => filter_var(env('PII_DELETE_APPLICANT_USER', true), FILTER_VALIDATE_BOOL),

    // Enrollment statuses that PROTECT a student from any purge: reaching any of
    // these means they became a real (or billed, or once-enrolled) student, so
    // their record is kept regardless of age. Anything NOT in this list is a
    // "dead-end" application. Mirrors StudentEnrollment::STATUS_* — keep in sync.
    'protected_enrollment_statuses' => [
        'provisional',
        'sent_billing',
        'billed',
        'partially_paid',
        'enrolled',
        'provisionally_enrolled',
        'dropped',
        'completed',
    ],

    // Dead-end statuses that count as a *decided* application (→ the months
    // window). A student with only drafts / no enrollment uses the shorter
    // abandoned-draft window instead.
    'decided_dead_end_statuses' => [
        'submitted',
        'exam_passed',
        'exam_failed',
        'assessed',
        'rejected',
        'cancelled',
    ],

];
