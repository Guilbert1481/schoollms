<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host-based multi-tenancy
    |--------------------------------------------------------------------------
    |
    | Each school is reached on its own host — a subdomain of our base domain
    | (e.g. "memory-ridge.philceb.ph") or the school's own custom domain
    | (e.g. "memoryridge.edu.ph"). The school is resolved from the request
    | Host header by App\Support\Tenancy\TenantResolver; nothing about the
    | school lives in the URL path. See ADR-0007 and SECURITY_PRINCIPLES.
    |
    */

    /*
    | The canonical base domain used to BUILD a school's subdomain host from its
    | slug (e.g. slug "memory-ridge" -> "memory-ridge.philceb.ph"). Used for
    | redirects when we must bounce a user back to their own school's host.
    | Local dev: "localhost" so "memory-ridge.localhost" works with no hosts file.
    */
    'primary_base_domain' => env('TENANCY_PRIMARY_BASE_DOMAIN', 'localhost'),

    /*
    | Base domains under which "<school-slug>.<base>" resolves to a school by
    | its slug WITHOUT needing an explicit school_domains row. We control these
    | domains, so their subdomains are always safe to serve/issue TLS for.
    | Comma-separated in the env.
    */
    'base_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TENANCY_BASE_DOMAINS', 'localhost'))
    ))),

    /*
    | Subdomain labels that are NEVER treated as a school slug — they belong to
    | the platform itself. A request to any of these resolves to no school
    | (the central/platform context: landing page, superadmin, registration).
    */
    'reserved_labels' => [
        'www', 'app', 'admin', 'portal', 'api', 'mail', 'static', 'assets', 'cdn',
    ],
];
