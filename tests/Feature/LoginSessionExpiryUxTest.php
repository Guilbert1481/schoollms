<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The mobile "session expired" loop: a cached login form resubmits a dead
 * CSRF token, the 419 bounced the user to the website home, the cached form
 * came back, and the retry loop ran them into the login rate limiter.
 * Guards the two halves of the fix: auth forms are never cached, and a 419
 * raised on the login form redirects to a FRESH login form instead.
 */
class LoginSessionExpiryUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_password_forms_are_served_no_store(): void
    {
        foreach (['/login', '/forgot-password'] as $uri) {
            $cacheControl = $this->get($uri)->assertOk()->headers->get('Cache-Control');
            $this->assertStringContainsString('no-store', $cacheControl, "$uri must be no-store");
        }
    }

    public function test_school_login_form_is_served_no_store(): void
    {
        $school = School::factory()->create();

        $cacheControl = $this->get('/'.$school->slug.'/login')
            ->assertOk()->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_expired_login_post_redirects_to_a_fresh_global_login_form(): void
    {
        // CSRF is skipped under tests, so raise the 419 the middleware would.
        Route::post('/login', fn () => throw new TokenMismatchException('CSRF token mismatch.'))
            ->middleware('web');

        $this->post('/login')
            ->assertRedirect('/login')
            ->assertSessionHas('warning');
    }

    public function test_expired_school_login_post_redirects_to_that_schools_login_form(): void
    {
        $school = School::factory()->create();
        Route::post('{slug}/login', fn () => throw new TokenMismatchException('CSRF token mismatch.'))
            ->middleware('web');

        $this->post('/'.$school->slug.'/login')
            ->assertRedirect(route('school.login', ['slug' => $school->slug]))
            ->assertSessionHas('warning');
    }

    public function test_expired_non_login_request_still_bounces_to_the_school_website(): void
    {
        $school = School::factory()->create();
        Route::post('{slug}/somewhere', fn () => throw new TokenMismatchException('CSRF token mismatch.'))
            ->middleware('web');

        $this->post('/'.$school->slug.'/somewhere')
            ->assertRedirect(route('website.home', ['schoolSlug' => $school->slug]))
            ->assertSessionHas('warning');
    }

    public function test_login_page_renders_the_expiry_warning(): void
    {
        $this->withSession(['_flash' => ['old' => [], 'new' => []]])
            ->get('/login')
            ->assertOk();

        $response = $this->session(['warning' => 'That sign-in page had expired — please try again.'])
            ->get('/login');

        $response->assertSee('That sign-in page had expired', false);
    }
}
