# AI Coding Guidelines for SchoolLMS

## Project Overview
SchoolLMS is a Laravel 12-based Learning Management System focused on teacher-driven content creation. Core entities: Users (admin/teacher roles), Subjects (assigned to teachers), Topics (nested under subjects), and Tests (with configurable settings).

## Architecture
- **Backend**: Laravel MVC with Eloquent models (`User`, `Subject`, `Topic`). Controllers in `app/Http/Controllers/`, routes in `routes/web.php`.
- **Frontend**: Blade templates in `resources/views/` for auth/pages, React components in `resources/js/components/` for interactive dashboards (e.g., `TeacherDashboard`).
- **Assets**: Vite builds CSS/JS from `resources/` to `public/`. Tailwind CSS for styling.
- **Data Flow**: Teachers create subjects → add topics → configure tests. No student-facing features yet.

## Key Workflows
- **Setup**: Run `composer run-script setup` (installs deps, generates key, migrates DB, builds assets).
- **Development**: Use `composer run-script dev` for concurrent Laravel serve, queue listener, log tailing, and Vite dev server.
- **Testing**: Execute `composer run-script test` (clears config cache, runs PHPUnit).
- **Migrations**: Use `php artisan migrate` for DB schema changes. Models use `$fillable` arrays for mass assignment.
- **Asset Building**: `npm run dev` for development, `npm run build` for production.

## Conventions
- **Models**: Define relationships explicitly (e.g., `Topic::belongsTo(Subject::class)`). Use HasFactory trait.
- **Routes**: Group under middleware (e.g., `auth`). Prefix teacher routes with `/teacher/`.
- **Views**: Blade templates in `resources/views/teacher/` for teacher pages. Pass data via compact() or view()->share().
- **JS/React**: Mount React roots in Blade views using `document.getElementById()`. Pass initial data via `window.__GLOBAL_VAR__`.
- **Migrations**: Incremental schema changes; avoid altering core tables directly. Use foreign keys with `constrained()->cascadeOnDelete()`.
- **Styling**: Tailwind classes in Blade/React. Custom CSS in `resources/css/dashboard.css`.

## Examples
- **Creating a Model Relationship**: In `Topic.php`, add `public function subject() { return $this->belongsTo(Subject::class); }`.
- **Adding a Route**: `Route::get('/teacher/subjects', [SubjectController::class, 'index'])->middleware('auth')->name('teacher.subjects');`
- **React Component**: Import in `app.jsx`, render in Blade via `<div id="component-root"></div>`.
- **Migration**: Add column with `Schema::table('subjects', fn(Blueprint $t) => $t->string('code')->unique());`

## Integration Points
- **Queue Jobs**: Use for background tasks (e.g., test processing). Listener runs in dev script.
- **Mail**: Configured in `config/mail.php`; use for notifications (not implemented yet).
- **Sessions/Cache**: Standard Laravel; avoid custom drivers unless necessary.

Focus on teacher workflows; extend models/controllers for new features like student enrollment.