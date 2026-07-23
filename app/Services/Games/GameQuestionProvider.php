<?php

namespace App\Services\Games;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Draws a fixed, playable question set from a school's question bank for a
 * multiplayer game session.
 *
 * Mirrors the difficulty/scope semantics of GamesController::questions but
 * returns the correct answer alongside each item — the caller ({@see
 * GameSessionService}) snapshots it server-side so grading never trusts the
 * client. The shared /api/questions endpoint stays the client-graded practice
 * feed; this is the authoritative draw for live matches.
 */
class GameQuestionProvider
{
    /**
     * @param  array{
     *     school_id:int, type:string, difficulty?:string, count?:int,
     *     subject_id?:?int, topic_id?:?int, lesson_id?:?int,
     *     competency_id?:?int, academic_level_id?:?int
     * }  $opts
     * @return array<int, array{
     *     question_id:int, question_text:string, choices:?array<int,string>,
     *     correct_index:?int, answer_text:?string, explanation:?string
     * }>
     */
    public function draw(array $opts): array
    {
        $schoolId = (int) $opts['school_id'];
        $type = $opts['type'] === 'identification' ? 'identification' : 'mcq';
        $count = min(max((int) ($opts['count'] ?? 10), 1), 30);
        $tier = (string) ($opts['difficulty'] ?? '');

        // The MCQ builder historically wrote both spellings (see GamesController).
        $types = $type === 'mcq' ? ['mcq', 'multiple_choice'] : ['identification'];

        // Fresh base query per pull — builders are single-use.
        $base = fn () => DB::table('questions')
            ->where('school_id', $schoolId)
            ->whereIn('question_type', $types)
            ->when(! empty($opts['subject_id']), fn ($q) => $q->where('subject_id', (int) $opts['subject_id']))
            ->when(! empty($opts['topic_id']), fn ($q) => $q->where('topic_id', (int) $opts['topic_id']))
            ->when(! empty($opts['lesson_id']), fn ($q) => $q->where('lesson_id', (int) $opts['lesson_id']))
            ->when(! empty($opts['competency_id']), fn ($q) => $q->where('competency_id', (int) $opts['competency_id']))
            ->when(! empty($opts['academic_level_id']), fn ($q) => $q->where('academic_level_id', (int) $opts['academic_level_id']));

        $pull = function (?string $only, int $want) use ($base, $type) {
            if ($want <= 0) {
                return collect();
            }

            $rows = $base()
                ->when($only !== null, fn ($q) => $q->where('difficulty', $only))
                ->inRandomOrder()
                ->limit($want * 2) // headroom: malformed rows are filtered below
                ->get(['id', 'question_text', 'explanation', 'difficulty']);

            return $this->shape($rows, $type)->take($want)->values();
        };

        if ($tier === 'average' || $tier === 'advanced') {
            $questions = $pull($tier, $count);
        } elseif ($tier === 'mixed') {
            // Average block first, advanced fills the rest, then top up from
            // whichever tier still has supply (a thin tier never short-changes
            // the run — e.g. no 'advanced' authored yet falls back to average).
            $avgPool = $pull('average', $count);
            $advPool = $pull('advanced', $count);
            $half = (int) ceil($count / 2);

            $avg = $avgPool->take($half);
            $adv = $advPool->take($count - $avg->count());
            $questions = $avg->concat($adv)->values();

            if ($questions->count() < $count) {
                $usedIds = $questions->pluck('question_id')->all();
                $leftover = $avgPool->concat($advPool)
                    ->reject(fn ($q) => in_array($q['question_id'], $usedIds, true));
                $questions = $questions->concat($leftover)->take($count)->values();
            }
        } else {
            $questions = $pull(null, $count);
        }

        return $questions->values()->all();
    }

    /**
     * Shape raw rows into the game payload, dropping malformed items
     * (identification with no stored answer; MCQ without >= 2 options and
     * exactly one correct). MCQ choices are shuffled; the correct index is
     * re-derived against the shuffled order.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function shape(Collection $rows, string $type): Collection
    {
        $choicesByQuestion = DB::table('choices')
            ->whereIn('question_id', $rows->pluck('id'))
            ->get(['question_id', 'choice_text', 'is_correct'])
            ->groupBy('question_id');

        return $rows->map(function ($row) use ($type, $choicesByQuestion) {
            $choices = ($choicesByQuestion[$row->id] ?? collect())->values();

            if ($type === 'identification') {
                $answer = trim((string) optional($choices->firstWhere('is_correct', 1))->choice_text);
                if ($answer === '') {
                    return null; // malformed: no stored answer
                }

                return [
                    'question_id' => (int) $row->id,
                    'question_text' => (string) $row->question_text,
                    'choices' => null,
                    'correct_index' => null,
                    'answer_text' => $answer,
                    'explanation' => $row->explanation,
                ];
            }

            // MCQ: need >= 2 options and exactly one correct.
            if ($choices->count() < 2 || (int) $choices->where('is_correct', 1)->count() !== 1) {
                return null;
            }

            $shuffled = $choices->shuffle()->values();
            $correct = $shuffled->search(fn ($c) => (int) $c->is_correct === 1);

            return [
                'question_id' => (int) $row->id,
                'question_text' => (string) $row->question_text,
                'choices' => $shuffled->pluck('choice_text')->all(),
                'correct_index' => (int) $correct,
                'answer_text' => (string) $shuffled[$correct]->choice_text,
                'explanation' => $row->explanation,
            ];
        })->filter()->values();
    }
}
