<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Finance settings restructure: the five former sub-pages live as tabs
 * inside Finance Preferences (old URLs redirect to the right tab), and the
 * Email page carries the combined Configuration tab with provider presets
 * and SMTP/IMAP connection tests.
 */
class FinanceSettingsPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::factory()->create();
        $this->finance = User::factory()->create([
            'school_id' => $school->id,
            'role'      => 'finance_manager',
        ]);
    }

    public function test_preferences_page_carries_the_moved_sections_as_tabs(): void
    {
        $this->actingAs($this->finance)
            ->get(route('finance.settings.preferences'))
            ->assertOk()
            ->assertSee('Payment Methods')
            ->assertSee('Penalty Rules')
            ->assertSee('OR &amp; Receipt Numbering', false)
            ->assertSee('Invoice Numbering')
            ->assertSee('SOA Template');
    }

    public function test_old_sub_page_urls_redirect_to_their_tab(): void
    {
        $this->actingAs($this->finance)
            ->get('/finance/settings/payment-methods')
            ->assertRedirect(route('finance.settings.preferences', ['tab' => 'payment-methods']));
    }

    public function test_email_page_has_provider_presets_and_test_buttons(): void
    {
        $this->actingAs($this->finance)
            ->get(route('finance.settings.email'))
            ->assertOk()
            ->assertSee('Email Provider')
            ->assertSee('Gmail / Google Workspace')
            ->assertSee('Send Test Email (SMTP)')
            ->assertSee('Test IMAP Connection');
    }

    public function test_smtp_test_requires_saved_settings(): void
    {
        $this->actingAs($this->finance)
            ->post(route('finance.settings.email.test-smtp'))
            ->assertSessionHas('error');
    }
}
