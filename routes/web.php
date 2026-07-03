
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SchoolRegistrationController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\StudentRegisterController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\UserThemeController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\AssignableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\GlobalSearchController;


/*
|-------------------------------------------------------------------------------------------
| Authentication & Public Routes (DO NOT TOUCH THIS UNLESS ABSOLUTELY NECESSARY!!)
|-------------------------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Main Login (for superadmins/platform)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');

// School-Specific Login (using {slug})
Route::get('{slug}/login', [LoginController::class, 'showLoginForm'])->name('school.login');
Route::post('{slug}/login', [LoginController::class, 'login'])->name('school.login.post')->middleware('throttle:login');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Password Reset (M1 — self-service forgot/reset password; built-in broker)
Route::get('/forgot-password',        [PasswordResetController::class, 'showLinkRequest'])->name('password.request');
Route::post('/forgot-password',       [PasswordResetController::class, 'sendLink'])->name('password.email')->middleware('throttle:6,1');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password',        [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:6,1');





/*
|--------------------------------------------------------------------------
| ROLE-BASED DASHBOARD (GLOBAL ENTRY)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');




/*
|--------------------------------------------------------------------------
| SETTINGS (Accessible to all authenticated users, but content varies by role)
|--------------------------------------------------------------------------
*/
    Route::middleware(['auth'])
    ->get('/settings', function () {
        return view('settings.index');
    })
    ->name('settings.index');

/*
|--------------------------------------------------------------------------
| School Registration
|--------------------------------------------------------------------------
*/

Route::get('/register-institution', [SchoolRegistrationController::class, 'showRegistrationForm'])
    ->name('register.school.public');

Route::post('/register-institution', [SchoolRegistrationController::class, 'register'])
    ->name('register.school.post');


/*
|--------------------------------------------------------------------------
| ONBOARDING WIZARD
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('onboarding')->group(function () {
    Route::get('/setup', [OnboardingController::class, 'showWizard'])->name('onboarding.wizard');
    Route::post('/setup', [OnboardingController::class, 'setupEntity'])->name('onboarding.setup');
    Route::get('/success', [OnboardingController::class, 'showSuccess'])->name('onboarding.success');
});


/*
|--------------------------------------------------------------------------
| Subscription & Billing Routes
|--------------------------------------------------------------------------
*/

Route::prefix('settings')
    ->middleware(['auth', 'role:admin,superadmin'])
    ->name('settings.')
    ->group(function () {

        Route::get('/subscription', function () {
            return view('settings.subscription');
        })->name('subscription.index');

        Route::get('/addons', function () {
            return view('settings.addons');
        })->name('addons.index');

    });



/*
|--------------------------------------------------------------------------
| Student Registration Routes
|--------------------------------------------------------------------------
*/

Route::get('/register/{term}', [StudentRegisterController::class, 'show'])
    ->name('student.register');

Route::post('/register/{term}', [StudentRegisterController::class, 'store'])
    ->name('student.register.store');



/*
|--------------------------------------------------------------------------
| School Branding & Settings
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,superadmin'])->group(function () {

    Route::get('/school/settings/branding', [SchoolController::class, 'showBranding'])
        ->name('school.branding.settings');

    Route::post('/school/settings/branding', [SchoolController::class, 'updateBranding'])
        ->name('school.update.branding');

});


Route::middleware(['auth'])->group(function () {
    Route::get('/settings/theme', [UserThemeController::class, 'edit'])->name('theme.edit');
    Route::post('/settings/theme', [UserThemeController::class, 'update'])->name('theme.update');
});


/*
|--------------------------------------------------------------------------
| Admin Quote Management
|--------------------------------------------------------------------------
*/


Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin,superadmin'])
    ->group(function () {

        Route::get('/quotes', [QuoteController::class, 'index'])
            ->name('quotes.index');

        Route::post('/quotes', [QuoteController::class, 'store'])
            ->name('quotes.store');

        Route::post('/quotes/update-display', [QuoteController::class, 'updateDisplay'])
            ->name('quotes.update-display');

        Route::get('/quotes/{quote}/edit', [QuoteController::class, 'edit'])
         ->name('quotes.edit');

        Route::put('/quotes/{quote}', [QuoteController::class, 'update'])
            ->name('quotes.update');

        Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])
            ->name('quotes.destroy');

    });

/*
|--------------------------------------------------------------------------
| Assignables
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/assignables', [AssignableController::class, 'index'])
        ->name('assignables.index');

    Route::get('/assignable-users', function (\Illuminate\Http\Request $request) {
        return \App\Models\User::when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            })
            ->limit(10)
            ->select('id','name')
            ->get();
    });

    Route::get('/assignable-groups', function () {
        return \App\Models\Group::select('id','name')->get();
    });

});





/*|--------------------------------------------------------------------------
| SETTINGS - USER MANAGEMENT (Admin Only)
|--------------------------------------------------------------------------*/

    Route::prefix('settings')->name('settings.')->middleware(['auth', 'role:admin,superadmin'])->group(function () {

    Route::get('/users', [UserManagementController::class, 'index'])
        ->name('users.index');

    Route::post('/users', [UserManagementController::class, 'store'])
        ->name('users.store');

    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])
        ->name('users.edit');

    Route::get('/users/{user}/show', [UserManagementController::class, 'show'])
        ->name('users.show');

    Route::delete('/users/{user}', [UserManagementController::class, 'delete'])
        ->name('users.delete');

    
    Route::put('/users/{user}', [UserManagementController::class, 'update'])
        ->name('users.update');

});

Route::prefix('settings')->middleware(['auth', 'role:admin,superadmin'])->group(function () {
    Route::post('/roles', [UserManagementController::class, 'storeRole'])
        ->name('roles.store');

    Route::put('/roles/{role}', [UserManagementController::class, 'updateRole'])
        ->name('roles.update');

    Route::delete('/roles/{role}', [UserManagementController::class, 'destroyRole'])
        ->name('roles.destroy');
});


/*|--------------------------------------------------------------------------
    | PDF file
    |--------------------------------------------------------------------------*/
    Route::get('/tests/{test}/pdf', [PDFController::class, 'downloadTestPdf'])
    ->name('tests.pdf');



    
    /*|--------------------------------------------------------------------------
    | GLOBAL SEARCH
    |--------------------------------------------------------------------------*/
        Route::get('/global-search', [GlobalSearchController::class, 'search'])
        ->name('global.search');
    



    Route::post('/notifications/{id}/read', function ($id) {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json(['success' => true]);
    })->name('notifications.read');

    Route::get('/notifications/feed', function () {
        $user = auth()->user();

        $dbNotifications = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('type', '!=', \App\Notifications\EnrollmentOpenNotification::class)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($n) {
                $data = json_decode($n->data);
                return [
                    'id'           => $n->id,
                    'title'        => $data->title ?? 'Notification',
                    'message'      => $data->message ?? '',
                    'type'             => $data->type ?? 'announcement',
                    'reference_id'     => $data->reference_id ?? 0,
                    'term_id'          => $data->term_id ?? null,
                    'academic_year_id' => $data->academic_year_id ?? null,
                    'read'             => !is_null($n->read_at),
                    'urgent'       => false,
                    'days_left'    => null,
                    'created_at'   => $n->created_at,
                ];
            })
            ->all();

        $virtual = \App\Support\EnrollmentNotifications::forUser($user);

        $notifications = array_merge($virtual, $dbNotifications);

        $count = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('type', '!=', \App\Notifications\EnrollmentOpenNotification::class)
            ->whereNull('read_at')
            ->count() + count($virtual);

        return response()->json([
            'count'         => $count,
            'notifications' => $notifications,
        ]);
    })->name('notifications.feed');
    
    
    
