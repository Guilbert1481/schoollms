<?php

namespace Tests\Feature;

use App\Models\ChatThread;
use App\Models\School;
use App\Models\User;
use App\Notifications\FlaggedChatNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tenant isolation for flagged-chat alerts: when a message trips the
 * config/chat.php word lists, the FlaggedChatNotification must reach only
 * admins / guidance counselors of the SENDER'S school — never another
 * tenant's staff. Regression for the unscoped recipient query fixed
 * 2026-07-21 in ChatController::storeMessage.
 */
class FlaggedChatSchoolScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $sender;

    private User $counselorA;

    private User $counselorB;

    private User $adminB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = School::factory()->create();
        $this->schoolB = School::factory()->create();

        $this->sender = $this->makeUser('student', $this->schoolA);
        $this->counselorA = $this->makeUser('guidance_counselor', $this->schoolA);
        $this->counselorB = $this->makeUser('guidance_counselor', $this->schoolB);
        $this->adminB = $this->makeUser('admin', $this->schoolB);
    }

    private function makeUser(string $role, School $school): User
    {
        return User::factory()->create([
            'school_id' => $school->id,
            'role' => $role,
        ]);
    }

    private function makeThreadFor(User $sender): ChatThread
    {
        $peer = $this->makeUser('student', School::query()->findOrFail($sender->school_id));

        $thread = ChatThread::create([
            'type' => 'private',
            'school_id' => $sender->school_id,
            'created_by' => $sender->id,
            'title' => 'Test thread',
        ]);
        $thread->participants()->attach([$sender->id, $peer->id]);

        return $thread;
    }

    public function test_flagged_message_notifies_only_the_senders_school(): void
    {
        Notification::fake();

        $thread = $this->makeThreadFor($this->sender);

        $this->actingAs($this->sender)
            ->postJson(route('communication.chat.message.store', $thread), [
                'message' => 'I hate this so much',
            ])
            ->assertOk();

        Notification::assertSentTo($this->counselorA, FlaggedChatNotification::class);
        Notification::assertNotSentTo($this->counselorB, FlaggedChatNotification::class);
        Notification::assertNotSentTo($this->adminB, FlaggedChatNotification::class);
    }

    public function test_clean_message_notifies_no_moderation_staff(): void
    {
        Notification::fake();

        $thread = $this->makeThreadFor($this->sender);

        $this->actingAs($this->sender)
            ->postJson(route('communication.chat.message.store', $thread), [
                'message' => 'See you at the library later',
            ])
            ->assertOk();

        Notification::assertNotSentTo($this->counselorA, FlaggedChatNotification::class);
        Notification::assertNotSentTo($this->counselorB, FlaggedChatNotification::class);
    }
}
