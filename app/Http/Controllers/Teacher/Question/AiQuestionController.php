<?php

namespace App\Http\Controllers\Teacher\Question;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\Competency;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\Ai\AiClient;
use Illuminate\Http\Request;

/**
 * AI-assisted question generation for the builders. Uses the question metadata
 * already captured in the session (subject/topic/lesson/competency/level) to
 * ground the prompt, calls the configured default AI provider, and returns
 * normalized questions the front-end drops into the builder for review. The
 * teacher still edits and saves — nothing is persisted here.
 */
class AiQuestionController extends Controller
{
    public function generateMcq(Request $request)
    {
        $qb = session('qb');
        if (! is_array($qb) || empty($qb['subject_id'])) {
            return response()->json([
                'success' => false,
                'error' => 'Question metadata is missing — restart from the metadata step.',
            ], 422);
        }

        $data = $request->validate([
            'num_questions' => ['required', 'integer', 'min:1', 'max:20'],
            'num_choices' => ['required', 'integer', 'min:2', 'max:6'],
        ]);

        $client = AiClient::default();
        if (! $client) {
            return response()->json([
                'success' => false,
                'error' => 'No AI provider is configured. Ask your superadmin to set one in Settings → AI.',
            ], 422);
        }

        $context = [
            'level' => optional(AcademicLevel::find($qb['academic_level_id'] ?? null))->name,
            'subject' => optional(Subject::find($qb['subject_id']))->name,
            'topic' => optional(Topic::find($qb['topic_id'] ?? null))->name,
            'lesson' => optional(Lesson::find($qb['lesson_id'] ?? null))->name,
            'competency' => optional(Competency::find($qb['competency_id'] ?? null))->name,
        ];

        $system = 'You are an expert teacher writing exam questions. Output ONLY valid JSON — no markdown, no prose.';
        $user = $this->buildPrompt($context, (int) $data['num_questions'], (int) $data['num_choices']);

        try {
            $text = $client->completeJson($system, $user);
            $questions = $this->parseQuestions($text);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 502);
        }

        if (empty($questions)) {
            return response()->json([
                'success' => false,
                'error' => 'The AI did not return usable questions. Please try again.',
            ], 502);
        }

        return response()->json(['success' => true, 'questions' => $questions]);
    }

    /** @param array<string, ?string> $context */
    private function buildPrompt(array $context, int $numQuestions, int $numChoices): string
    {
        $scope = collect([
            $context['level'] ? "Grade/level: {$context['level']}" : null,
            $context['subject'] ? "Subject: {$context['subject']}" : null,
            $context['topic'] ? "Topic: {$context['topic']}" : null,
            $context['lesson'] ? "Lesson: {$context['lesson']}" : null,
            $context['competency'] ? "Competency: {$context['competency']}" : null,
        ])->filter()->implode("\n");

        return <<<PROMPT
        Create {$numQuestions} multiple-choice questions for:
        {$scope}

        Requirements:
        - Each question has exactly {$numChoices} answer choices, with exactly one correct.
        - Keep them level-appropriate, clear, and unambiguous.
        - Mark each question's difficulty as either "average" or "advanced".

        Return a JSON object exactly like:
        {"questions":[{"question":"...","choices":["a","b"],"correct_index":0,"difficulty":"average","explanation":"short rationale"}]}
        - "choices" must have exactly {$numChoices} items.
        - "correct_index" is 0-based into "choices".
        - "explanation" is a short rationale (may be an empty string).
        PROMPT;
    }

    /**
     * Parse and normalize the model's JSON into the builder's shape.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseQuestions(string $text): array
    {
        $text = trim($text);
        // Strip ```json fences the model may wrap around the payload.
        $text = trim(preg_replace('/^```(?:json)?|```$/mi', '', $text));

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            return [];
        }

        $list = $decoded['questions'] ?? (array_is_list($decoded) ? $decoded : []);

        $out = [];
        foreach ($list as $q) {
            if (! is_array($q)) {
                continue;
            }

            $questionText = trim((string) ($q['question'] ?? $q['question_text'] ?? ''));
            $choices = array_values(array_filter(
                array_map(fn ($x) => trim((string) $x), (array) ($q['choices'] ?? [])),
                fn ($x) => $x !== ''
            ));

            if ($questionText === '' || count($choices) < 2) {
                continue;
            }

            $correct = (int) ($q['correct_index'] ?? 0);
            if ($correct < 0 || $correct >= count($choices)) {
                $correct = 0;
            }

            $out[] = [
                'question_text' => $questionText,
                'choices' => $choices,
                'correct_index' => $correct,
                'difficulty' => in_array($q['difficulty'] ?? '', ['average', 'advanced'], true) ? $q['difficulty'] : 'average',
                'explanation' => trim((string) ($q['explanation'] ?? '')),
            ];
        }

        return $out;
    }
}
