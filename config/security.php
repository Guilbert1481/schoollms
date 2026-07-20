<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Two-factor enforcement (Roadmap M2)
    |--------------------------------------------------------------------------
    | When enabled, users in `two_factor_mandatory_roles` cannot use the
    | system until they enrol an authenticator app; users of ANY role who
    | have enrolled are challenged once per session regardless of this flag.
    | The test suite disables enforcement globally (phpunit.xml) and the
    | dedicated 2FA tests re-enable it explicitly.
    */

    'enforce_2fa' => env('TWO_FACTOR_ENFORCE', true),

    'two_factor_mandatory_roles' => [
        'superadmin',
        'admin',
        'finance_manager',
        'registrar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff session hardening (Roadmap D4)
    |--------------------------------------------------------------------------
    | Privileged accounts sit in front of money, grades and minors' PII, so
    | they get a SHORTER idle timeout than the global SESSION_LIFETIME (480m):
    | after `staff_idle_timeout` minutes of no requests the session is
    | invalidated server-side and the user must re-authenticate. This is the
    | real protection for shared school office computers — it does not depend
    | on the browser honouring cookie expiry. Set to 0 to disable.
    */

    'staff_idle_timeout' => (int) env('STAFF_IDLE_TIMEOUT', 30),

    'staff_session_roles' => [
        'superadmin',
        'admin',
        'finance_manager',
        'registrar',
    ],

];
