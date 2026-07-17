    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Teacher\Question\McqController;
    use App\Http\Controllers\Teacher\Question\AiQuestionController;
    

    /*
    |--------------------------------------------------------------------------
    | MCQ Builder Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:teacher,course_architect,trainor'])->prefix('teacher/tests')->group(function () {
        
        // URL: /teacher/tests/mcq-builder
        Route::get('/mcq', [McqController::class, 'index'])
            ->name('mcq.builder');

        // SAVE route (Must match what is in mcq.js)
        Route::post('/mcq/save', [McqController::class, 'saveMcq'])
            ->name('mcq.save');

        // AI-assisted generation — fills the builder for the teacher to review.
        Route::post('/mcq/ai-generate', [AiQuestionController::class, 'generateMcq'])
            ->name('mcq.ai-generate');

        Route::match(['get'], '/mcq/save', function () {
            return response('Method Not Allowed', 405);
        });

            
        // Session clear route
        Route::post('/session/clear', [McqController::class, 'clearQuestionSession']);
    });

