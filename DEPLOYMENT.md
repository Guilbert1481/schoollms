# SchoolLMS — GitHub & Contabo VPS Workflow Guide

Step-by-step instructions for moving code **between your local machine, GitHub, and your Contabo VPS**.

- **GitHub repo:** `https://github.com/Guilbert1481/schoollms.git`
- **Default branch:** `main`
- **Local path (Windows):** `C:\laragon\www\schoollms`
- **VPS path (Linux):** `/var/www/schoollms`

---

## 1. One-time setup

### 1.1 Local machine (Windows + Laragon)

```powershell
# Confirm git identity (once)
git config --global user.name  "Guilbert1481"
git config --global user.email "guilbert1481@users.noreply.github.com"

# Confirm remote is correct
cd C:\laragon\www\schoollms
git remote -v
# Expected: origin  https://github.com/Guilbert1481/schoollms.git (fetch/push)
```

**GitHub authentication (Personal Access Token):**
1. GitHub → Settings → Developer settings → Personal access tokens → **Tokens (classic)**.
2. Generate token, scope = `repo`, copy it.
3. First `git push` will prompt for username + password — paste the token as the password.
4. Windows Credential Manager will remember it.

### 1.2 Contabo VPS (one-time bootstrap)

SSH into the VPS, then:

```bash
# Install prerequisites (Ubuntu/Debian example)
sudo apt update
sudo apt install -y git curl unzip php-cli php-mbstring php-xml php-bcmath \
    php-curl php-mysql php-zip php-gd php-intl composer nginx mariadb-server
# Node 18+:
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo bash -
sudo apt install -y nodejs

# Clone the repository
sudo mkdir -p /var/www && cd /var/www
sudo git clone https://github.com/Guilbert1481/schoollms.git
sudo chown -R $USER:$USER schoollms
cd schoollms

# Configure environment
cp .env.example .env
nano .env   # set APP_ENV=production, APP_DEBUG=false, APP_URL=https://yourdomain,
            # DB_*, MAIL_*, SESSION_DRIVER, CACHE_STORE, etc.

# Run first-time deploy
sudo ./deploy.sh --fresh
```

Then configure Nginx (or Apache) to point its document root to **`/var/www/schoollms/public`**.

---

## 2. Export local → GitHub (push your changes)

Run these from `C:\laragon\www\schoollms` in PowerShell.

```powershell
# 1. See what changed
git status

# 2. Stage everything (use specific paths if you want a partial commit)
git add -A

# 3. Commit with a clear message
git commit -m "feat(scope): short summary of what changed"

# 4. Push to GitHub
git push origin main
```

### Useful variations

| Goal | Command |
|---|---|
| Stage only one folder | `git add app/Http/Controllers/Academic` |
| Unstage a file | `git restore --staged path/to/file` |
| Discard local edits to a file | `git restore path/to/file` |
| Amend the last commit message | `git commit --amend -m "new message"` then `git push --force-with-lease` |
| See last 10 commits | `git log --oneline -10` |
| Show file diff vs. last commit | `git diff path/to/file` |

### Working with branches (recommended for larger features)

```powershell
git checkout -b feature/student-enrollment
# ...make changes...
git add -A
git commit -m "feat: student enrollment flow"
git push -u origin feature/student-enrollment
# Open a Pull Request on GitHub, then merge into main.
```

### Before pushing — sanity checklist

- [ ] `.env` is **not** staged (`git status` should not show it).
- [ ] No DB dumps (`*.sql`), logs, or `cookies.txt` / `test.txt` in the diff.
- [ ] `php artisan test` passes locally (optional but recommended).
- [ ] Migrations are additive (don't rewrite published migrations).

---

## 3. Import GitHub → local (pull latest)

If you work from another machine, or want teammates' updates:

```powershell
cd C:\laragon\www\schoollms
git fetch origin
git pull --ff-only origin main

# Re-install any new dependencies
composer install
npm install
npm run dev   # or `npm run build` for production assets

# Apply any new migrations
php artisan migrate
```

**If `git pull` fails because of local edits:**

```powershell
git stash push -m "wip"   # save your uncommitted work
git pull --ff-only origin main
git stash pop             # bring your work back
```

---

## 4. Export GitHub → Contabo VPS (deploy)

SSH into the VPS and run the included script:

```bash
cd /var/www/schoollms
sudo ./deploy.sh
```

What [deploy.sh](deploy.sh) does:

1. Enables Laravel maintenance mode.
2. `git fetch` + `git reset --hard origin/main` (forces server to match GitHub).
3. `composer install --no-dev --optimize-autoloader`.
4. `npm ci && npm run build` (skip with `--no-npm`).
5. `php artisan migrate --force`.
6. Rebuilds config/route/view caches.
7. Restores `www-data` ownership and permissions on `storage/` & `bootstrap/cache/`.
8. Restarts queue workers.
9. Brings the site back up.

### Flags

| Flag | Purpose |
|---|---|
| `--fresh` | First-time install (generates `APP_KEY`, runs `storage:link`). |
| `--no-npm` | Skip frontend build (use when assets are built elsewhere). |

### Manual deploy (if you don't want to use deploy.sh)

```bash
cd /var/www/schoollms
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
php artisan queue:restart
php artisan up
```

---

## 5. Import Contabo → local (rare: pulling DB or files back)

You normally pull **code** from GitHub, not the VPS. But for **database** or **uploaded files**, you go server → local directly.

### 5.1 Database dump from VPS → local

On the VPS:
```bash
mysqldump -u <db_user> -p <db_name> | gzip > /tmp/schoollms-$(date +%F).sql.gz
```

On Windows (PowerShell), download via SCP:
```powershell
scp user@your-vps-ip:/tmp/schoollms-2026-05-12.sql.gz C:\backups\
# Restore locally:
gzip -d C:\backups\schoollms-2026-05-12.sql.gz
mysql -u root schoollms < C:\backups\schoollms-2026-05-12.sql
```

### 5.2 User-uploaded files (storage/app/public) from VPS → local

```powershell
scp -r user@your-vps-ip:/var/www/schoollms/storage/app/public/* `
    C:\laragon\www\schoollms\storage\app\public\
```

> ⚠️ **Never `git push` data from the VPS.** Database dumps and uploads must travel via SCP/rsync, not Git.

---

## 6. Common scenarios

### 6.1 "I made changes directly on the VPS — how do I save them?"

**Don't.** The VPS is downstream. Pull the change back locally:

```powershell
# On Windows
scp user@your-vps-ip:/var/www/schoollms/path/to/changed/file C:\laragon\www\schoollms\path\to\changed\file
# Then commit + push normally
git add path/to/changed/file
git commit -m "fix: hotfix copied from VPS"
git push origin main
# Then on the VPS:
sudo ./deploy.sh
```

### 6.2 "Local and remote diverged" error on push

```powershell
git pull --rebase origin main
# Resolve any conflicts, then:
git push origin main
```

### 6.3 "I committed a secret/file by accident"

```powershell
# Remove from history (DANGEROUS — rewrites history)
git rm --cached path/to/secret
echo "path/to/secret" >> .gitignore
git commit -m "chore: untrack secret"
git push origin main
```

If the secret is sensitive (API key, password): **rotate it immediately** in the provider, because it's already in GitHub history.

### 6.4 Roll back the VPS to a previous commit

```bash
cd /var/www/schoollms
git log --oneline -10           # find the commit hash
git reset --hard <commit-hash>
./deploy.sh --no-npm            # skip pull-reset re-clobber? no — deploy.sh resets to origin/main again
```

To roll back **and** keep the VPS pinned, revert on GitHub instead:

```powershell
# Locally
git revert <bad-commit-hash>
git push origin main
# Then on VPS:
sudo ./deploy.sh
```

---

## 7. Day-to-day cheat sheet

| Task | Where | Command |
|---|---|---|
| Push code | Windows | `git add -A; git commit -m "..."; git push origin main` |
| Pull latest code | Windows | `git pull --ff-only origin main` |
| Deploy to VPS | VPS (SSH) | `cd /var/www/schoollms && sudo ./deploy.sh` |
| Fresh install on VPS | VPS (SSH) | `sudo ./deploy.sh --fresh` |
| View VPS logs | VPS (SSH) | `tail -f storage/logs/laravel.log` |
| Restart queue worker | VPS (SSH) | `php artisan queue:restart` |
| Backup VPS DB | VPS (SSH) | `mysqldump -u USER -p DB > backup.sql` |
| Backup VPS uploads | Windows | `scp -r user@vps:/var/www/schoollms/storage/app/public C:\backups\` |

---

## 8. Files that should **never** be committed

These are already in [.gitignore](.gitignore):

- `.env`, `.env.production`, `.env.backup`
- `vendor/`, `node_modules/`
- `public/build/`, `public/hot/`, `public/storage`
- `storage/logs/*.log`, `storage/framework/cache/`, `storage/framework/sessions/`, `storage/framework/views/`
- `*.sql`, `*.sqlite`, database dumps
- `cookies.txt`, `test.txt`, `routes.txt`, `diag_*.php` (local debug artifacts)

If you ever see them in `git status`, add them to `.gitignore` and run:

```powershell
git rm --cached path/to/file
git commit -m "chore: untrack local artifact"
```
