<?php

namespace Tests\Feature;

use App\Models\StudentEnrollment;
use App\Services\Finance\PaymentScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the "most-specific node wins" tuition rule: a broad default plus a
 * narrower strand/grade override must resolve to a single, correct tuition
 * charge (no double-charging). Money path — see MODERNIZATION governance docs.
 */
class TuitionMostSpecificTest extends TestCase
{
    use RefreshDatabase;

    private function seedFees(): void
    {
        // Minimal tree: Basic Education > Senior High School > {Grade 11 STEM, Grade 11 ABM}
        DB::table('education_nodes')->insert([
            ['id' => 1,  'name' => 'Basic Education',    'parent_id' => null, 'is_offered' => 1, 'is_active' => 1, 'order_index' => 1],
            ['id' => 15, 'name' => 'Senior High School', 'parent_id' => 1,    'is_offered' => 1, 'is_active' => 1, 'order_index' => 1],
            ['id' => 43, 'name' => 'Grade 11',           'parent_id' => 15,   'is_offered' => 1, 'is_active' => 1, 'order_index' => 1],
            ['id' => 45, 'name' => 'Grade 11',           'parent_id' => 15,   'is_offered' => 1, 'is_active' => 1, 'order_index' => 2],
        ]);

        $base = [
            'school_id' => 1, 'academic_year_id' => null, 'term_id' => null, 'program_id' => null,
            'payment_plan_id' => null, 'billing_basis' => 'fixed', 'is_active' => 1, 'notes' => null,
        ];
        DB::table('finance_fee_setups')->insert([
            $base + ['education_node_id' => 15, 'year_level' => 11, 'fee_type' => 'tuition', 'code' => 'DEF11',  'name' => 'Grade 11 Tuition',      'amount' => 80000],
            $base + ['education_node_id' => 43, 'year_level' => 11, 'fee_type' => 'tuition', 'code' => 'STEM11', 'name' => 'Grade 11 STEM Tuition', 'amount' => 85000],
            $base + ['education_node_id' => 15, 'year_level' => 11, 'fee_type' => 'miscellaneous', 'code' => 'MISC', 'name' => 'Misc Fee', 'amount' => 1000],
        ]);
    }

    private function tuitionFor(int $node): array
    {
        $e = new StudentEnrollment();
        $e->school_id = 1; $e->education_node_id = $node; $e->year_level = 11; $e->total_units = 0;

        return collect(app(PaymentScheduleService::class)->feePreview($e)['items'])
            ->filter(fn ($i) => stripos($i->fee_type, 'tuition') !== false)
            ->map(fn ($i) => (float) $i->amount)->values()->all();
    }

    public function test_strand_override_wins_over_default(): void
    {
        $this->seedFees();
        $this->assertSame([85000.0], $this->tuitionFor(43), 'STEM (override) should win');
    }

    public function test_default_applies_when_no_override(): void
    {
        $this->seedFees();
        $this->assertSame([80000.0], $this->tuitionFor(45), 'ABM (no override) should get the default');
    }

    public function test_never_double_charges_tuition(): void
    {
        $this->seedFees();
        $this->assertCount(1, $this->tuitionFor(43), 'exactly one tuition line even with default + override');
    }

    public function test_non_tuition_fees_still_stack(): void
    {
        $this->seedFees();
        $e = new StudentEnrollment();
        $e->school_id = 1; $e->education_node_id = 43; $e->year_level = 11; $e->total_units = 0;
        $misc = collect(app(PaymentScheduleService::class)->feePreview($e)['items'])
            ->filter(fn ($i) => stripos($i->fee_type, 'tuition') === false);
        $this->assertGreaterThanOrEqual(1, $misc->count());
    }
}
