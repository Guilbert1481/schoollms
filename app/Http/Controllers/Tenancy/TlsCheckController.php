<?php

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * On-demand TLS "ask" endpoint for the reverse proxy (Caddy).
 *
 * Caddy calls this before issuing a certificate for a hostname it has not seen.
 * We answer 200 only for hosts we actually serve (a base-domain subdomain of a
 * real school, or a VERIFIED custom domain) so that pointing an arbitrary
 * domain at our IP cannot mint certificates. See packaging/server (Phase 2).
 */
class TlsCheckController extends Controller
{
    public function check(Request $request, TenantResolver $resolver): Response
    {
        $host = (string) $request->query('domain', '');

        return $resolver->isIssuableHost($host)
            ? response('OK', 200)
            : response('unknown host', 404);
    }
}
