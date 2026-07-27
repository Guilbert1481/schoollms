<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The floating <x-math-tool> (MathLive equation editor / calculator) must render
 * through the real HTTP + Blade stack on every teacher question-type builder
 * page, and on the student take screen with evaluation disabled (a calculator
 * that computes results has no place on a live test by default).
 *
 * Also guards the self-hosted vendor assets: the component is useless — and the
 * page silently broken — if public/vendor/mathlive or compute-engine disappear.
 */
class MathToolRenderTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\DataProvider('builderPages')]
    public function test_every_question_builder_page_renders_the_math_tool(string $route, string $sessionType): void
    {
        $user = User::factory()->create([
            'school_id' => School::factory()->create()->id,
            'role' => 'teacher',
        ]);

        $this->actingAs($user)
            ->withSession(['qb' => [
                'subject_id' => 1,
                'topic_id' => 1,
                'lesson_id' => 1,
                'competency_id' => 1,
                'academic_level_id' => 1,
                'question_type' => $sessionType,
            ]])
            ->get(route($route))
            ->assertOk()
            ->assertSee('data-mathtool', false)
            ->assertSee('data-evaluate="1"', false); // teachers get the full calculator
    }

    /** @return array<string, array{0:string,1:string}> */
    public static function builderPages(): array
    {
        return [
            'mcq' => ['mcq.builder', 'multiple_choice'],
            'true-false' => ['tf.builder', 'true_false'],
            'mtf' => ['mtf.builder', 'mtf'],
            'fib' => ['fib.builder', 'fib'],
            'identification' => ['identification.builder', 'identification'],
            'enumeration' => ['enumeration.builder', 'enumeration'],
            'essay' => ['essay.builder', 'essay'],
            'matching' => ['matching.builder', 'matching'],
        ];
    }

    public function test_student_take_screen_renders_the_math_tool_without_evaluation(): void
    {
        $this->actingAs(User::factory()->create([
            'school_id' => School::factory()->create()->id,
            'role' => 'student',
        ]));

        $html = view('student.assessments.take', [
            'test' => (object) ['title' => 'Quiz', 'subject' => null],
            'attempt' => 1,
            'sections' => [],
            'remainingSeconds' => null,
            'requireFullscreen' => false,
        ])->render();

        $this->assertStringContainsString('data-mathtool', $html);
        $this->assertStringContainsString('data-evaluate="0"', $html); // editor only — no calculator
    }

    public function test_self_hosted_vendor_assets_are_present(): void
    {
        $this->assertFileExists(public_path('vendor/mathlive/mathlive.min.mjs'));
        $this->assertFileExists(public_path('vendor/mathlive/fonts/KaTeX_Main-Regular.woff2'));
        $this->assertFileExists(public_path('vendor/compute-engine/compute-engine.js'));
        $this->assertNotEmpty(glob(public_path('vendor/compute-engine/chunks/*.js')));
    }
}
