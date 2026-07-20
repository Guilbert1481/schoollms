# Disaster Recovery Runbook — Sophentis (Roadmap P5)

**Status:** draft, 2026-07-20. Owner: operator (Jabhy). Pairs with the D1 backup
scripts in [`scripts/backup/`](../../scripts/backup/). This runbook is only real
once a restore has actually been performed (see §5) — until then, treat backups
as unverified.

Production host: `root@207.180.239.10` · site `https://memory-ridge.philceb.ph`
· app dir `/var/www/schoollms` · DB `lms_db` (MySQL, `127.0.0.1:3306`).

---

## 1. What is backed up, and where

| Asset | Backed up by | Location | Notes |
|---|---|---|---|
| **Database** (`lms_db`) | `scripts/backup/db-backup.sh` (cron) | local `/var/backups/sophentis` + off-site rclone remote | AES-256 encrypted, gzipped, 14-day local retention |
| **Uploaded files** (private disk: government IDs, enrolment docs; public disk: photos, proofs) | ⚠️ **not yet automated** — see §7 | `storage/app` | Add to the off-site sync; these are NOT in the DB dump |
| **`.env`** (APP_KEY, DB + mail creds) | ⚠️ **manual** | password manager | Losing `APP_KEY` makes every `encrypted` column (AiProvider key, finance SMTP/IMAP passwords) permanently unreadable |
| **Backup passphrase** | operator | password manager, off-server | Without it, no backup can be decrypted |

## 2. Recovery objectives (target)

- **RPO (max data loss):** 24h with daily backups — tighten to 1h with hourly
  cron if the finance load justifies it.
- **RTO (max downtime):** a few hours to rebuild on a fresh VPS; minutes if only
  the DB must be rolled back on the existing host.

## 3. Prerequisites to recover (keep these somewhere OTHER than the server)

1. The backup **encryption passphrase** (`BACKUP_PASSPHRASE`).
2. Access to the **off-site backup remote** (rclone config / bucket creds).
3. The production **`.env`** (or at least `APP_KEY` and DB creds).
4. SSH access / ability to provision a new VPS.

## 4. Scenario A — DB corruption or bad data, host is fine

The common case (bad migration, mass mis-edit, accidental delete):

```bash
cd /var/www/schoollms
php artisan down                       # maintenance mode
# newest local backup, or pull one from the off-site remote first:
ls -t /var/backups/sophentis/lms_db-*.sql.gz.enc | head

# Restore into a SCRATCH db first and eyeball it (default target is safe):
BACKUP_ENV=/etc/sophentis-backup.env \
  bash scripts/backup/db-restore.sh /var/backups/sophentis/lms_db-YYYYMMDD-HHMMSS.sql.gz.enc

# Verify row counts look right, THEN restore over live (explicit + confirmed):
BACKUP_ENV=/etc/sophentis-backup.env \
  bash scripts/backup/db-restore.sh <file> lms_db     # prompts "OVERWRITE LIVE"

php artisan up
```

## 5. Scenario B — host lost (disk failure, ransomware, provider gone)

1. Provision a fresh VPS (Ubuntu, PHP 8.2, Composer, Node, MySQL, nginx) — see
   [`sophentis-philceb-ph.md`](sophentis-philceb-ph.md) for the full build.
2. Clone the repo, restore `.env` from your password manager, `composer install
   --no-dev`, `npm ci && npm run build`.
3. Install rclone, restore its config, pull the newest backup from the off-site
   remote.
4. Create an empty `lms_db`, then:
   `bash scripts/backup/db-restore.sh <file> lms_db`.
5. Restore uploaded files (§7) into `storage/app`; `php artisan storage:link`.
6. `php artisan migrate` (no-op if the dump is current), `optimize`, point DNS /
   nginx + certbot, smoke-test `/up` and `/login`.

## 6. Restore drill (do this on a schedule — quarterly minimum)

A backup you have never restored is a guess. Each drill:

```bash
BACKUP_ENV=/etc/sophentis-backup.env \
  bash scripts/backup/db-restore.sh <newest-file>      # → lms_db_restore_test
MYSQL_PWD=… mysql -uschoollms lms_db_restore_test -e \
  "SELECT (SELECT COUNT(*) FROM users) users, (SELECT COUNT(*) FROM payments) payments, (SELECT MAX(created_at) FROM audit_logs) last_audit;"
```

Confirm counts track production and the newest rows are present, then drop the
scratch DB. Record the drill date below.

## 7. Known gaps (close these next)

- **Uploaded files are not yet in an automated off-site backup** — only the DB
  is. Add `storage/app` to the rclone sync (a second cron line, or extend
  `db-backup.sh`). Government-ID and enrolment documents live here.
- **`.env` backup is manual.** Consider an encrypted copy in the off-site remote.
- **D2 (encryption at rest for ID numbers)** is still open — plan `APP_KEY`
  rotation (P1) alongside it, since rotation re-encrypts those columns and would
  invalidate older backups' ciphertext unless the old key is retained.

## 8. Drill log

| Date | Backup file tested | Result | By |
|---|---|---|---|
| _(none yet)_ | | | |
