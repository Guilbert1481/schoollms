<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Http\Request;

class ExpiredSessionRedirect
{
    /**
     * Resolve a school slug for the current request and redirect the
     * visitor to that school's public website. Falls back to /login
     * when no slug can be determined.
     */
    public static function handle(Request $request)
    {
        $slug = self::resolveSlug($request);

        if ($slug) {
            return redirect()
                ->route('website.home', ['schoolSlug' => $slug])
                ->with('warning', 'Your session has expired. Please sign in again.');
        }

        return redirect('/login')
            ->with('warning', 'Your session has expired. Please sign in again.');
    }

    protected static function resolveSlug(Request $request): ?string
    {
        // 1) Authenticated user (rare on 419, but possible).
        if ($user = $request->user()) {
            if ($user->school && $user->school->slug) {
                return $user->school->slug;
            }
        }

        // 2) Long-lived cookie set at login.
        if ($cookie = $request->cookie('last_school_slug')) {
            if (self::slugExists($cookie)) {
                return $cookie;
            }
        }

        // 3) First URL segment, if it matches a known school slug
        //    (covers public website routes like /{schoolSlug}/...).
        $segment = $request->segment(1);
        if ($segment && self::slugExists($segment)) {
            return $segment;
        }

        // 4) Referrer URL first segment, as a last resort.
        $referer = $request->headers->get('referer');
        if ($referer) {
            $path = trim(parse_url($referer, PHP_URL_PATH) ?? '', '/');
            $first = $path === '' ? null : explode('/', $path)[0];
            if ($first && self::slugExists($first)) {
                return $first;
            }
        }

        return null;
    }

    protected static function slugExists(string $slug): bool
    {
        return School::where('slug', $slug)->exists();
    }
}
