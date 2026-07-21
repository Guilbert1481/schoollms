# Incident Response Plan — Sophentis (Roadmap P1)

**Status:** draft, 2026-07-21. Owner: operator (Jabhy). Pairs with the
[Disaster Recovery Runbook](../deploy/disaster-recovery-runbook.md) (P5) and the
D1 backup scripts in [`scripts/backup/`](../../scripts/backup/).

This plan says **who acts, in what order, with which exact commands** when
Sophentis is attacked, breached, or leaking data. Sophentis holds minors' PII,
government IDs, grades, and money — so a security incident is also a **data-privacy
incident** under RA 10173 (PH Data Privacy Act), with a hard **72-hour** breach
notification clock (§7).

Production host: `root@207.180.239.10` · site `https://memory-ridge.philceb.ph`
· app dir `/var/www/schoollms` · DB `lms_db` (MySQL, `127.0.0.1:3306`).

---

## 1. Roles & contacts

| Role | Who | Responsibility |
|---|---|---|
| **Incident Lead** | operator (Jabhy) | Runs this plan; decides containment vs. uptime; single source of truth during the incident. |
| **Data Protection Officer (DPO)** | ⚠️ **to be designated** (RA 10173 §P3) | Owns the NPC + data-subject notification (§7). Until named, the operator holds this hat. |
| **Hosting / infra** | Contabo VPS (`207.180.239.10`) | Host-level isolation, snapshots, network. |
| **Provider support** | mail / SMS / AI vendors | Key revocation when a provider credential leaks (§6). |

> Fill the real names + 24/7 phone/email into the operator's password manager,
> **not** into this repo.

## 2. Detection — where an incident first shows up

| Source | Where | Watch for |
|---|---|---|
| **Off-DB audit trail** (S4) | `storage/logs/audit/audit-*.log` | Unexpected `pii_purge`, finance `audit.data`, or role changes; gaps in the sequence. |
| **Login log** (Phase 4) | superadmin → **Logins** page; `login_logs` table | Failed-login spikes, logins from new geographies, success right after many failures. |
| **Threshold alert** (Phase 4) | app log, `auth.failed.threshold` | ≥10 failures for one IP/email in 15 min — credential-stuffing signal. |
| **Error monitor** (S3) | Sentry/Flare *(pending)* | Probing turns into exceptions first. |
| **Uptime monitor** (S5) | external check on `/up` *(pending)* | Outage / defacement noticed by us, not a school. |
| **Human report** | operator / school staff | "I got logged out", "a grade changed", ransom email. |

## 3. Severity

| Level | Definition | Examples |
|---|---|---|
| **SEV-1** | Confirmed PII/credential exposure, money tampering, or full outage. | DB dump exfiltrated; admin account takeover; ledger altered; site defaced. |
| **SEV-2** | Active attack, no confirmed data loss yet. | Credential-stuffing past the throttle; a single account compromised; SSRF/injection attempt in logs. |
| **SEV-3** | Suspicious but unconfirmed. | Odd login-log pattern; a dependency CVE (Dependabot); a probing scan. |

SEV-1/2 → start §4 immediately. SEV-3 → investigate, log, watch.

## 4. First 60 minutes (triage)

1. **Declare & timestamp.** One person = Incident Lead. Open a running notes doc
   (outside the server). Record everything with times.
2. **Preserve evidence FIRST — before any cleanup.** The DB-resident logs can be
   wiped by an attacker; the off-DB trail is your ground truth:
   ```bash
   # Copy the tamper-evident audit trail OFF the box immediately.
   rsync -a /var/www/schoollms/storage/logs/audit/ ~/ir-evidence-$(date +%F-%H%M)/
   mysqldump --single-transaction lms_db login_logs audit_logs \
     > ~/ir-evidence-$(date +%F-%H%M)/db-logs.sql
   ```
3. **Scope it.** Which accounts/schools/data? Use the Logins page + `audit_logs`
   to build a timeline of what the actor touched.
4. **Decide containment** (§5): isolate without destroying evidence. Prefer
   force-logout + credential rotation over wiping.
5. **Escalate** per severity (§1). SEV-1 → notify the DPO now; the 72-hour clock
   (§7) starts at *awareness*, not at resolution.

## 5. Containment

### 5a. Take the app offline (hard stop)
```bash
cd /var/www/schoollms && php artisan down --secret="<random>"   # you can still reach it with the secret
# ...resolve...
php artisan up
```

### 5b. Mass force-logout (evict every session)
Sessions use the **file** driver (`config/session.php` → `storage/framework/sessions`).
Deleting them logs everyone out on their next request:
```bash
rm -f /var/www/schoollms/storage/framework/sessions/*
```
This is the fastest kill-switch for "an attacker holds a valid session." Note:
the `AuthenticateSession` middleware (M6) already evicts *other* sessions on a
password change, and a password reset invalidates that account's sessions — but
the file-wipe is the only **all-users-at-once** option today.

### 5c. Lock one compromised account
There is **no user-deactivation flag yet** (Roadmap P2 gap). Interim procedure:
1. Superadmin/admin **resets that user's password** (User Management → edit).
2. Wipe sessions (§5b) so their current session dies immediately.
3. If the account is staff with mandatory 2FA, the reset + M2 challenge blocks
   re-entry until they re-enrol.
> **Follow-up:** build P2 (an `is_active`/`suspended` flag that blocks login and
> kills sessions on the spot) so this isn't a manual password reset.

## 6. Credential & key rotation

Rotate anything that may have leaked. After any rotation: `php artisan config:clear`,
restart PHP-FPM + `php artisan queue:restart`.

| Secret | Where | Steps |
|---|---|---|
| **DB password** | `.env` `DB_PASSWORD` + MySQL user | Change in MySQL, update `.env`, clear config, restart. |
| **Mail / SMS / AI provider keys** | provider dashboard + `.env` / `ai_providers`, `*_settings` (encrypted columns) | **Revoke at the provider first**, then set the new value in-app. |
| **Session/cookie secret** | `APP_KEY` | ⚠️ **See below — never rotate blindly.** |

### ⚠️ APP_KEY rotation — the dangerous one (couples to D2)
`APP_KEY` encrypts every `encrypted` column and file: **`students.government_id_number`**
(D2), **uploaded ID files** (D2b), `ai_providers.api_key`, and finance
`smtp_password`/`imap_password` — plus it signs session cookies. Losing or
changing it makes all of that unreadable, so **do not rotate it just to log
users out** — use the session-file wipe (§5b) for that. Rotate `APP_KEY` only
when the key itself may have leaked (e.g. `.env` exposure).

Procedure (keeps the app readable throughout):

1. `php artisan down` + fresh backup (DB **and** `storage/app` files).
2. Set the **new** `APP_KEY` and move the **old** key into `APP_PREVIOUS_KEYS`
   (`config/app.php` → `previous_keys`). Laravel then **encrypts with the new
   key but still decrypts with the old** — existing data and cookies keep
   working, new writes use the new key. `php artisan config:clear` + restart.
3. `php artisan up`. The leaked key is now retired for all *new* encryption and
   all sessions are invalidated (cookies were signed with the old key).

> **Known gap — data at rest is NOT yet re-keyed.** The D2/D2b backfill commands
> only convert *plaintext* to ciphertext; they **skip** anything that already
> decrypts (which includes data encrypted under the old key, still valid via
> `APP_PREVIOUS_KEYS`), so they do **not** re-encrypt it under the new key. Until
> a purpose-built `key:rotate` pass exists (decrypt-with-old → encrypt-with-new
> for every encrypted column + file), **keep the old key in `APP_PREVIOUS_KEYS`**
> — removing it makes the still-old-key-encrypted PII permanently unreadable.
> Building that command is the real fix; track it on the roadmap.

## 7. Data-breach notification (RA 10173 / NPC)

If personal data was (or was likely) accessed by an unauthorized party:

- **Clock:** notify the **National Privacy Commission and affected data subjects
  within 72 hours** of becoming aware. The DPO (§1) leads; the operator supports.
- **Scope the breach** using the preserved logs (§4.2): the `audit_logs` /
  off-DB trail show *what records* were touched; `login_logs` show *whose access*
  was used. For minors, notify the parent/guardian on record.
- **Report contents:** nature of the breach, data involved, likely harm,
  measures taken, and contact point (DPO).
- Keep the incident notes + evidence bundle as the compliance record.

## 8. Recovery

- Restore from backup per the [DR Runbook](../deploy/disaster-recovery-runbook.md)
  (§4 DB rollback / §5 full rebuild). Confirm the restore on a scratch DB before
  overwriting live.
- Bring the app up (§5a), verify logins + a finance read + an ID-doc serve.
- Force one more logout (§5b) so no pre-incident session survives the restore.

## 9. Post-incident

1. **Preserve** the evidence bundle (§4.2) off-site; do not delete audit logs.
2. **Write-up:** timeline, root cause, data touched, actions, gaps found.
3. **Fix the class, not the instance** — feed findings back into the
   [Modernization Roadmap](../../MODERNIZATION_ROADMAP.md) (e.g. this incident is
   why P2 account-deactivation and the `key:rotate` command matter).
4. If a dependency was the vector, confirm Dependabot/`composer audit` (S1/S2)
   would now catch it.

---

### Quick reference (SEV-1 muscle memory)
```bash
# 1. Preserve evidence
rsync -a /var/www/schoollms/storage/logs/audit/ ~/ir-evidence-$(date +%F-%H%M)/
# 2. Contain
cd /var/www/schoollms && php artisan down --secret="<random>"
rm -f storage/framework/sessions/*        # evict all sessions
# 3. Rotate leaked creds (§6) → config:clear → restart php-fpm + queue:restart
# 4. DPO starts the 72h NPC/data-subject clock (§7)
# 5. Restore if needed (DR runbook) → verify → php artisan up
```
