# Going live on philceb.ph (staging domains)

Operator runbook for serving Sophentis under the borrowed `philceb.ph` zone
while the real domain is pending. Complements [`deploy.sh`](../../deploy.sh)
(Contabo VPS) and [ADR-0007](../adr/0007-host-based-tenant-resolution.md)
(host-based tenancy).

**Host shape:**

| Host                          | Serves                                        |
| ----------------------------- | --------------------------------------------- |
| `sophentis.philceb.ph`        | Platform central: superadmin, landing, registration (`sophentis` is a reserved label in `config/tenancy.php`) |
| `memory-ridge.philceb.ph`     | Memory Ridge (school slug `memory-ridge`)     |
| `<slug>.philceb.ph`           | Any other school, by its `schools.slug`       |

The bare `philceb.ph` site stays on its shared host (162.240.166.175) —
untouched. School hosts come from the DB slug, so `memory-ridge` keeps its
hyphen; a no-hyphen alias like `memoryridge.philceb.ph` would need a
`school_domains` row + its own DNS record.

## 1. DNS (philceb.ph zone — managed at GoDaddy)

Target is the Contabo VPS `207.180.239.10` (same box as gideon.philceb.ph).
Name is entered WITHOUT ".philceb.ph" — GoDaddy appends it.

| Type | Name           | Value            | TTL   | Status |
| ---- | -------------- | ---------------- | ----- | ------ |
| A    | `sophentis`    | `207.180.239.10` | 600 s | ✅ live (2026-07-17) |
| A    | `memory-ridge` | `207.180.239.10` | 600 s | to add |
| A    | one per new school (its slug) | `207.180.239.10` | 600 s | as needed |

The earlier `*.sophentis` wildcard record is obsolete under this model
(schools are no longer under `sophentis.`) — harmless to keep, fine to delete.
A `*.philceb.ph` wildcard would remove per-school DNS work later, but it also
sweeps every unclaimed subdomain to the VPS — only do that deliberately.
Existing `api` / `app` / `superadmin` / `gideon` records belong to other
apps: leave them alone.

## 2. Server `.env` (on the VPS, not in git)

```dotenv
APP_NAME=Sophentis
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sophentis.philceb.ph

TENANCY_PRIMARY_BASE_DOMAIN=philceb.ph
TENANCY_BASE_DOMAINS=philceb.ph

# Keep cookies host-only — this is the primary tenant-isolation layer (ADR-0007 §5).
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
```

Requires the `reserved_labels` additions in `config/tenancy.php`
(`sophentis`, `superadmin`, `gideon`, `argo`, `email`) to be deployed —
without them a school slugged "sophentis" could shadow the platform host.

## 3. Web server (nginx) + TLS

**Verified 2026-07-17:** the VPS front proxy is **nginx 1.18 (Ubuntu)** — it
already terminates TLS for `gideon.philceb.ph` and answers port 80 for
`sophentis.philceb.ph` with 404 (no site configured). Sophentis therefore gets
an nginx server block alongside Gideon's; do NOT replace nginx with Caddy while
Gideon depends on it.

`server_name` lists hosts **explicitly** (no `*.philceb.ph` catch-all — that
would swallow `api`/`app`/`superadmin` traffic belonging to other apps).
Add each new school's host here and re-run certbot for it.

`/etc/nginx/sites-available/sophentis` (then symlink into `sites-enabled`,
`nginx -t`, `systemctl reload nginx`):

```nginx
server {
    listen 80;
    server_name sophentis.philceb.ph memory-ridge.philceb.ph;

    root /var/www/schoollms/public;
    index index.php;
    client_max_body_size 50m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

### Certificates (certbot)

nginx has no on-demand TLS, so certs are issued per hostname with certbot
(HTTP-01, auto-renews):

```bash
certbot --nginx -d sophentis.philceb.ph -d memory-ridge.philceb.ph
certbot --nginx -d <new-school-slug>.philceb.ph   # per new school later
```

New school checklist: GoDaddy A record (slug) → add slug host to
`server_name` → `certbot --nginx -d <slug>.philceb.ph` → reload nginx.

**Later options** (when per-school steps get old): a `*.philceb.ph` wildcard
cert via DNS-01 (GoDaddy API or manual TXT — manual means manual ~90-day
renewals), or moving TLS to Caddy with on-demand issuance gated by the app's
`/tenancy/tls-check` endpoint (the original ADR-0007 design — the platform
host must then be an explicitly named site, because
`TenantResolver::isIssuableHost()` refuses non-school hosts).

## 4. Deploy

On the VPS: `./deploy.sh --fresh` first time, `./deploy.sh` thereafter
(see the header of [`deploy.sh`](../../deploy.sh) for assumptions:
`/var/www/schoollms`, www-data, PHP ≥ 8.2, Composer, Node 18+, npm).
Note deploy.sh pulls **GitHub `main`** — local uncommitted work is not
included until pushed.

## 5. Smoke checks

- `https://sophentis.philceb.ph` → platform landing / superadmin login,
  valid certificate, **no** school branding.
- `https://memory-ridge.philceb.ph/login` → Memory Ridge-branded login.
- Log in on school A's host, then open school B's host: you must be bounced
  back to school A (host-isolation guard).
- On the VPS: `curl -H "Host: sophentis.philceb.ph" "http://127.0.0.1/tenancy/tls-check?domain=memory-ridge.philceb.ph"`
  → 200, and with `?domain=random-word.philceb.ph` → 404 — proves host
  resolution + the future on-demand-TLS guard behave.

## Moving to the real domain later

Only env + DNS + nginx change: point the new domain's records at the VPS,
swap `philceb.ph` for the new base in `.env` (`TENANCY_*`, and `APP_URL` to
the new platform host) and in the nginx `server_name` (+ new certbot runs),
then `php artisan config:cache`. School hosts follow automatically because
they are derived from `TENANCY_PRIMARY_BASE_DOMAIN` at request time. During a
transition list **both** bases in `TENANCY_BASE_DOMAINS` (comma-separated) so
old links keep resolving.
