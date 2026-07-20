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

];
