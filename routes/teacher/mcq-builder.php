    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Teacher\Question\McqController;
    

    /*
    |--------------------------------------------------------------------------
    | MCQ Builder Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth'])->prefix('teacher/tests')->group(function () {
        
        // URL: /teacher/tests/mcq-builder
        Route::get('/mcq', [McqController::class, 'index'])
            ->name('mcq.builder');

        // SAVE route (Must match what is in mcq.js)
        Route::post('/mcq/save', [McqController::class, 'saveMcq'])
            ->name('mcq.save');

        Route::match(['get'], '/mcq/save', function () {
            return response('Method Not Allowed', 405);
        });

            
        // Session clear route
        Route::post('/session/clear', [McqController::class, 'clearQuestionSession']);
    });

