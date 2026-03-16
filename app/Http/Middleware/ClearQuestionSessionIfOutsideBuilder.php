<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearQuestionSessionIfOutsideBuilder
{
    public function handle(Request $request, Closure $next)
    {
        // If there is NO question-building session, do nothing
        if (!session()->has('qb.subject_id')) {
            return $next($request);
        }

        // Current route name
        $routeName = optional($request->route())->getName();

        // Route names
        $metadataRoute = 'question.metadata';

        // Routes that KEEP session (MCQ flow)
        $keepSessionRoutes = [
            'mcq.builder',
            'mcq.save',
            'question.session.clear',
        ];

        /*
         |---------------------------------------------------------
         | RULE 1: Visiting or refreshing METADATA
         | → CLEAR session (start planning again)
         |---------------------------------------------------------
         */
        if ($routeName === $metadataRoute) {
            $this->clearSession();
            return $next($request);
        }

        /*
         |---------------------------------------------------------
         | RULE 2: Staying in MCQ flow (refresh included)
         | → KEEP session
         |---------------------------------------------------------
         */
        if (in_array($routeName, $keepSessionRoutes)) {
            return $next($request);
        }

        /*
         |---------------------------------------------------------
         | RULE 3: ANY other route
         | → CLEAR session (abandoned)
         |---------------------------------------------------------
         */
        $this->clearSession();

        return $next($request);
    }

    private function clearSession(): void
    {
        session()->forget([
            'qb.subject_id',
            'qb.topic_id',
            'qb.lesson_id',
            'qb.competency_id',
            'qb.academic_level_id',
            'qb.question_type',
        ]);
    }
}
