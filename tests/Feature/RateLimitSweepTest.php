<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Roadmap H6 — rate limits beyond login. The chat limiter is exercised for
 * real (requests until 429); the remaining wirings are asserted on the route
 * table so a dropped middleware fails the build.
 */
class RateLimitSweepTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $sender;

    private User $peer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->sender = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
        $this->peer = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    }

    public function test_chat_messages_throttle_at_thirty_per_minute(): void
    {
        $this->actingAs($this->sender);

        $threadId = $this->postJson(route('communication.chat.direct', $this->peer))
            ->assertOk()->json('thread.id');

        for ($i = 1; $i <= 30; $i++) {
            $this->postJson(route('communication.chat.message.store', $threadId), ['message' => "m{$i}"])
                ->assertSuccessful();
        }

        $this->postJson(route('communication.chat.message.store', $threadId), ['message' => 'one too many'])
            ->assertStatus(429);
    }

    public function test_upload_and_public_apply_routes_carry_their_limiters(): void
    {
        $wirings = [
            'form.save' => 'throttle:uploads',
            'public.apply.store' => 'throttle:uploads',
            'public.apply.pathway.store' => 'throttle:uploads',
            'public.apply.qr.login' => 'throttle:public-apply',
            'public.apply.qr.register' => 'throttle:public-apply',
            'communication.chat.message.store' => 'throttle:chat',
        ];

        foreach ($wirings as $routeName => $middleware) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Route {$routeName} is missing.");
            $this->assertContains(
                $middleware,
                $route->gatherMiddleware(),
                "Route {$routeName} lost its {$middleware} limiter."
            );
        }
    }
}
