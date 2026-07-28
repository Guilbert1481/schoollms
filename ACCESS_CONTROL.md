# Access Control Playbook (READ BEFORE ADDING ROUTES OR TABLES)

The #1 security rule in this project:

> **Hiding a menu item in `config/sidebar.php` is NOT security.**
> Any logged-in user can type a URL. Every non-public route MUST be gated by role
> at the route level, and the generic table CRUD only touches *registered* tables.

If you skip this, a student (or any role) can open pages and edit data that isn't
theirs. That already happened once (a student reached the admin User Management
page). Follow the recipes below so it never happens again.

---

## Canonical role names

Use these exact snake_case names in `role:` (the middleware lowercases and turns
spaces/hyphens into `_`, but always type them like this):

```
superadmin   admin        academics            admission_manager
registrar    finance_manager                   dean
principal    program_head course_architect     subject_coordinator
teacher      guidance_counselor                student
trainee      trainor      training_program_head
```

Rule of thumb: **always include `admin,superadmin`** on staff/management routes
(admins oversee everything). Personal dashboards (student/trainee/trainor) get
only their own role.

---

## Recipe A — Gate a normal route group (most common)

When you create a routes file (e.g. `routes/<area>/<thing>.php`), gate the group:

```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Whatever\ThingController;

Route::middleware(['web', 'auth', 'role:dean,admin,superadmin'])  // <-- the gate
    ->prefix('dean')
    ->name('dean.')
    ->group(function () {
        Route::get('/things', [ThingController::class, 'index'])->name('things.index');
        // ...
    });
```

- Pick roles that match what the sidebar shows for that area (see `config/sidebar.php`).
- `'web'` is only needed if the group is loaded outside the default web stack;
  copy whatever the neighbouring route files in that folder use.
- **Register the new file** in `bootstrap/app.php` (the `web: [ ... ]` array) —
  route files are NOT auto-discovered.

### Student-facing routes
```php
Route::middleware(['web', 'auth', 'role:student'])
    ->prefix('student')->name('student.')->group(function () { /* ... */ });
```

### Don't gate these (legitimately shared by everyone logged in)
`tools/*`, `video-conference/*`, `communication`, profile/theme, onboarding,
address/cascading-dropdown helpers. These stay `['auth']` (or `['web','auth']`)
with **no** `role:` — they're for all authenticated users on purpose.

---

## Recipe B — Add a table to the generic CRUD (dynamic tables)

The generic endpoints (`school.system.dynamic.store/update/destroy/get`, used by
`<x-table>` and `modal.js`) can ONLY touch tables listed in the registry
`config/tables/tables.php`. Anything else returns **404 on purpose**.

So if you build a new screen that uses the generic CRUD and you get a 404, it
means the table isn't registered yet. To register it:

1. Create `config/tables/<your_table>.php` (copy `config/tables/departments.php`):
   ```php
   <?php
   return [
       'roles'   => ['admin', 'superadmin'],   // REQUIRED — who may touch it here
       'labels'  => ['name' => 'Name'],
       'columns' => [
           ['key' => 'name', 'label' => 'Name'],
       ],
       'form'    => ['name'],            // editable fields
       'hidden'  => ['id', 'school_id', 'created_at', 'updated_at'],
       'auto'    => ['school_id'],       // server-set; add 'user_id' if needed
       'relations' => [],
   ];
   ```
2. Register it in `config/tables/tables.php`:
   ```php
   'your_table' => require __DIR__ . '/your_table.php',
   ```
3. Make sure the DB table has a `school_id` column (it gets auto-scoped per school).
4. Add any new role to the route's `role:` list in `routes/school/system/dynamic.php`
   — that list is the **union** of every table's `roles`.

`BaseCrudController` then automatically:
- allows that table (and rejects every unregistered one),
- **refuses any role not in that table's `roles`** — a missing `roles` key means
  nobody, so the table stays inert until you declare its owner,
- scopes every read/update/delete to the user's `school_id`,
- writes ONLY real, non-protected columns (it strips `id`, `school_id`,
  `user_id`, timestamps, and any field that isn't a real column).

**`roles` is per table, not per route.** The route gate cannot express "a dean may
edit `curriculums` but not `offices`" — one flat list applies to everything behind
it. That is exactly how `subjects`, `topics` and `lessons` ended up writable by 15
roles, letting a teacher or course_architect restructure a curriculum without ever
visiting the screen that owns it. Set `roles` to the audience of the screens that
actually drive the table, and no wider.

**Registering a table is a grant of write access.** If nothing drives the table
through these endpoints, do not register it at all — `topics` and `lessons` have
config files but are deliberately left out of the registry for this reason.

You do NOT need to touch `BaseCrudController`. If you find yourself wanting to,
stop and ask — that's the security boundary.

> Prefer a **dedicated controller + explicit routes** (Recipe A) for anything
> sensitive. The generic CRUD is only for simple master-data tables.

---

## The 30-second pre-commit checklist

Before you push a new route or screen, confirm:

- [ ] Every new route group has a `role:` gate (Recipe A) — unless it's an
      intentionally shared utility.
- [ ] The roles match the sidebar audience for that area; `admin,superadmin`
      included for management pages.
- [ ] New route file is added to `bootstrap/app.php`.
- [ ] If it uses generic CRUD: the table is registered in
      `config/tables/tables.php` and has `school_id`.
- [ ] You verified it (commands below).

---

## Verify it (run these)

Show the middleware on your new routes — you should see your `role:` in the list:

```bash
php artisan route:clear
php artisan route:list --path=YOUR-PREFIX -v
```

Find route groups that are still `auth`-only (possible gaps to review):

```bash
# Git Bash
grep -rn "middleware(\['auth'\])" routes/
grep -rn "middleware(\['web', 'auth'\])" routes/
```

```powershell
# PowerShell
Select-String -Path routes\* -Recurse -Pattern "middleware\(\['auth'\]\)"
```

Manual smoke test: log in as a **student**, paste the admin/staff URL in the
address bar — you must get **403 Forbidden**, not the page.

---

## How the gate behaves
`role:` → `App\Http\Middleware\CheckRole` (alias registered in `bootstrap/app.php`).
It returns **403** if the user's role isn't in the list, and also 403 if a
non-superadmin has no `school_id`. Role names are normalized, so
`Course Architect`, `course-architect`, and `course_architect` all match.
