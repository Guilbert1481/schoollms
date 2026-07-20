<?php

/*
|--------------------------------------------------------------------------
| CORS (Roadmap M3)
|--------------------------------------------------------------------------
| Sophentis is a same-origin Blade application — no third-party frontend
| consumes it, so nothing is allowed cross-origin. If a public API ever
| ships, open ONLY its paths here with an explicit origin allow-list,
| never '*' (SECURITY_PRINCIPLES §16).
*/

return [

    'paths' => [],

    'allowed_methods' => [],

    'allowed_origins' => [],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
