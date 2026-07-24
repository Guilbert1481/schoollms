<?php

namespace App\Services\Games;

use App\Models\Games\GameSession;
use App\Models\Games\GameSessionAnswer;
use App\Models\Games\GameSessionPlayer;
use App\Models\Games\GameSessionQuestion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the whole live Tug-of-War match: lobby -> draw -> buzz-in -> rope -> end.
 *
 * Design guarantees:
 *  - Server-authoritative grading — the correct answer is snapshotted into
 *    game_session_questions at start and compared here; clients never send or
 *    decide correctness.
 *  - First-correct-buzz arbitration — the unique (question, player) index plus
 *    the "already closed" guard mean the fastest correct answer takes the pull;
 *    a player gets one shot per question.
 *  - Tenant isolation — GameSession is school-scoped (BelongsToSchool); the
 *    controller only ever hands us a session the caller's school can see.
 */
class GameSessionService
{
    /** Minimum questions for a viable match. */
    private const MIN_QUESTIONS = 3;

    public function __construct(private GameQuestionProvider $questions) {}

    // ---------------------------------------------------------------------
    // Lobby
    // ---------------------------------------------------------------------

    /**
     * Open a lobby. `players` is a list of roster assignments the host made on
     * the config screen: [['student_id' => int, 'team' => 'A'|'B'], ...].
     * Names are re-resolved from the DB (never trusted from the client).
     *
     * @param  array<string, mixed>  $config
     */
    public function create(User $host, array $config): GameSession
    {
        $schoolId = (int) $host->school_id;
        $mode = in_array($config['mode'] ?? '', ['solo', 'opponent', 'team'], true) ? $config['mode'] : 'team';

        return DB::transaction(function () use ($host, $schoolId, $mode, $config) {
            $session = GameSession::create([
                'host_id' => $host->id,
                'slug' => 'tug-of-war',
                'join_code' => $this->uniqueJoinCode(),
                'mode' => $mode,
                'question_type' => ($config['question_type'] ?? 'mcq') === 'identification' ? 'identification' : 'mcq',
                'difficulty' => in_array($config['difficulty'] ?? '', ['average', 'advanced', 'mixed'], true) ? $config['difficulty'] : 'mixed',
                'question_count' => min(max((int) ($config['question_count'] ?? 10), self::MIN_QUESTIONS), 30),
                'round_seconds' => min(max((int) ($config['round_seconds'] ?? 20), 5), 120),
                'academic_level_id' => $config['academic_level_id'] ?? null,
                'subject_id' => $config['subject_id'] ?? null,
                'topic_id' => $config['topic_id'] ?? null,
                'lesson_id' => $config['lesson_id'] ?? null,
                'competency_id' => $config['competency_id'] ?? null,
                'team_a_label' => $this->label($config['team_a_label'] ?? null, 'Team Einstein'),
                'team_b_label' => $this->label($config['team_b_label'] ?? null, 'Team Newton'),
                'status' => 'lobby',
            ]);

            // The host always joins. Solo/opponent puts the host on Team A;
            // in team mode the host is a neutral facilitator by default.
            $session->players()->create([
                'user_id' => $host->id,
                'display_name' => $this->userName($host),
                'team' => $mode === 'team' ? 'none' : 'A',
                'role' => 'host',
                'status' => 'ready',
                'avatar_seed' => 'u'.$host->id,
            ]);

            $this->seedRosterPlayers($session, $schoolId, (array) ($config['players'] ?? []));

            // Solo always faces a CPU on Team B so the rope has two sides.
            if ($mode === 'solo') {
                $session->players()->create([
                    'user_id' => null,
                    'display_name' => 'CPU',
                    'team' => 'B',
                    'role' => 'player',
                    'status' => 'ready',
                    'is_cpu' => true,
                    'avatar_seed' => 'cpu',
                ]);
            }

            return $session->fresh('players');
        });
    }

    /**
     * Add configured roster students as players, resolving names from the DB
     * (school-scoped) so a spoofed name/student from another school is dropped.
     *
     * @param  array<int, array<string, mixed>>  $assignments
     */
    private function seedRosterPlayers(GameSession $session, int $schoolId, array $assignments): void
    {
        $wantById = [];
        foreach ($assignments as $a) {
            $sid = (int) ($a['student_id'] ?? 0);
            $team = ($a['team'] ?? null) === 'B' ? 'B' : 'A';
            if ($sid > 0) {
                $wantById[$sid] = $team;
            }
        }
        if (! $wantById) {
            return;
        }

        $students = DB::table('students')
            ->where('school_id', $schoolId)
            ->whereIn('id', array_keys($wantById))
            ->get(['id', 'user_id', 'first_name', 'preferred_name', 'last_name']);

        foreach ($students as $st) {
            $session->players()->create([
                'user_id' => $st->user_id,
                'student_id' => $st->id,
                'display_name' => $this->studentName($st),
                'team' => $wantById[$st->id],
                'role' => 'player',
                'status' => 'ready',
                'avatar_seed' => 's'.$st->id,
            ]);
        }
    }

    /**
     * A student (or host who was invited) opens the lobby by code. If they were
     * pre-assigned on the roster we link their login to that seat; otherwise we
     * add them unassigned for the host to place.
     */
    public function join(string $code, User $user): GameSessionPlayer
    {
        $session = GameSession::where('join_code', strtoupper(trim($code)))->first();
        if (! $session) {
            throw new GameSessionException('That game code was not found.');
        }
        if ($session->status === 'ended') {
            throw new GameSessionException('That game has already ended.');
        }

        // Already in? (host seat or a prior join)
        $existing = $session->players()->where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        // Link to a pre-assigned roster seat for this user's student record.
        $studentId = DB::table('students')->where('user_id', $user->id)->value('id');
        if ($studentId) {
            $seat = $session->players()->where('student_id', $studentId)->whereNull('user_id')->first();
            if ($seat) {
                $seat->update(['user_id' => $user->id]);

                return $seat;
            }
        }

        return $session->players()->create([
            'user_id' => $user->id,
            'student_id' => $studentId,
            'display_name' => $this->userName($user),
            'team' => 'none',
            'role' => 'player',
            'status' => 'ready',
            'avatar_seed' => 'u'.$user->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // Match flow
    // ---------------------------------------------------------------------

    /** Draw + snapshot the question set and flip the lobby to active. */
    public function start(GameSession $session): GameSession
    {
        if ($session->status !== 'lobby') {
            throw new GameSessionException('This game has already started.');
        }

        $drawn = $this->questions->draw([
            'school_id' => (int) $session->school_id,
            'type' => $session->question_type,
            'difficulty' => $session->difficulty,
            'count' => (int) $session->question_count,
            'subject_id' => $session->subject_id,
            'topic_id' => $session->topic_id,
            'lesson_id' => $session->lesson_id,
            'competency_id' => $session->competency_id,
            'academic_level_id' => $session->academic_level_id,
        ]);

        if (count($drawn) < self::MIN_QUESTIONS) {
            throw new GameSessionException(
                'Not enough '.($session->question_type === 'identification' ? 'identification' : 'multiple-choice')
                .' questions for this selection (found '.count($drawn).', need at least '.self::MIN_QUESTIONS
                .'). Try a different difficulty or content scope.'
            );
        }

        return DB::transaction(function () use ($session, $drawn) {
            $seq = 0;
            foreach ($drawn as $q) {
                $seq++;
                $session->questions()->create([
                    'question_id' => $q['question_id'],
                    'sequence' => $seq,
                    'question_text' => $q['question_text'],
                    'choices' => $q['choices'],
                    'correct_index' => $q['correct_index'],
                    'answer_text' => $q['answer_text'],
                    'explanation' => $q['explanation'],
                ]);
            }

            $session->update([
                'question_count' => $seq, // may be fewer than requested
                'status' => 'active',
                'current_sequence' => 1,
                'question_ends_at' => now()->addSeconds((int) $session->round_seconds),
            ]);

            $session->players()->where('role', '!=', 'host')->update(['status' => 'thinking']);

            return $session->fresh(['players', 'questions']);
        });
    }

    /**
     * A buzz. Resolves the answering player (explicit player_id for a player's
     * own device, or `team` for a single-screen host relay), grades it against
     * the server-side answer, and — if it's the first correct one — closes the
     * question and pulls the rope.
     *
     * @param  array<string, mixed>  $in  player_id|team, choice_index|answer_text
     * @return array{ok:bool, correct:bool, reason:?string, state:array<string,mixed>}
     */
    public function answer(GameSession $session, array $in): array
    {
        if ($session->status !== 'active') {
            throw new GameSessionException('This game is not accepting answers right now.');
        }

        $question = $session->currentQuestion();
        if (! $question || $question->isClosed()) {
            return $this->result(false, false, 'The question is already closed.', $session);
        }

        $player = $this->resolvePlayer($session, $in);
        if (! $player || $player->team === 'none') {
            return $this->result(false, false, 'Pick a team before answering.', $session);
        }

        $correct = $this->grade($question, $in);

        // One shot per player per question — the unique index is the guard.
        try {
            GameSessionAnswer::create([
                'game_session_id' => $session->id,
                'game_session_question_id' => $question->id,
                'game_session_player_id' => $player->id,
                'choice_index' => array_key_exists('choice_index', $in) ? (int) $in['choice_index'] : null,
                'answer_text' => $in['answer_text'] ?? null,
                'is_correct' => $correct,
            ]);
        } catch (QueryException $e) {
            return $this->result(false, false, 'You already answered this question.', $session);
        }

        $player->update(['status' => 'answered']);

        if (! $correct) {
            // Question stays open for the other side to buzz.
            return $this->result(true, false, null, $session->fresh(['players', 'questions']));
        }

        return DB::transaction(function () use ($session, $question, $player) {
            $question->update([
                'answered_by_player_id' => $player->id,
                'answered_team' => $player->team,
                'was_correct' => true,
                'answered_at' => now(),
            ]);

            $player->increment('score');
            $player->increment('correct_count');

            if ($player->team === 'A') {
                $session->increment('team_a_score');
                $session->increment('team_a_correct');
                $session->increment('rope_position');
            } else {
                $session->increment('team_b_score');
                $session->increment('team_b_correct');
                $session->decrement('rope_position');
            }

            $this->advanceOrFinish($session->fresh());

            return $this->result(true, true, null, $session->fresh(['players', 'questions']));
        });
    }

    /** Host skip / timeout: consume the current question with no award. */
    public function advance(GameSession $session): GameSession
    {
        if ($session->status !== 'active') {
            throw new GameSessionException('This game is not running.');
        }

        $question = $session->currentQuestion();
        if ($question && ! $question->isClosed()) {
            $question->update(['answered_at' => now(), 'was_correct' => false]);
        }

        $this->advanceOrFinish($session);

        return $session->fresh(['players', 'questions']);
    }

    public function end(GameSession $session): GameSession
    {
        if ($session->status !== 'ended') {
            $session->update([
                'status' => 'ended',
                'winner' => $session->winner ?: $this->decideWinner($session),
                'question_ends_at' => null,
            ]);
        }

        return $session->fresh(['players', 'questions']);
    }

    /**
     * Move to the next question, or end the match if a finish line is reached
     * or the question set is exhausted.
     */
    private function advanceOrFinish(GameSession $session): void
    {
        $finish = $this->finishLine($session);

        if ($session->rope_position >= $finish) {
            $session->update(['status' => 'ended', 'winner' => 'A', 'question_ends_at' => null]);

            return;
        }
        if ($session->rope_position <= -$finish) {
            $session->update(['status' => 'ended', 'winner' => 'B', 'question_ends_at' => null]);

            return;
        }

        $next = $session->current_sequence + 1;
        if ($next > $session->question_count) {
            $session->update([
                'status' => 'ended',
                'winner' => $this->decideWinner($session),
                'question_ends_at' => null,
            ]);

            return;
        }

        $session->update([
            'current_sequence' => $next,
            'question_ends_at' => now()->addSeconds((int) $session->round_seconds),
        ]);
        $session->players()->where('role', '!=', 'host')->update(['status' => 'thinking']);
    }

    // ---------------------------------------------------------------------
    // State / roster
    // ---------------------------------------------------------------------

    /**
     * The client-safe snapshot (no unrevealed correct answers).
     *
     * @return array<string, mixed>
     */
    public function state(GameSession $session): array
    {
        $session->loadMissing(['players', 'questions']);
        $current = $session->currentQuestion();

        return [
            'id' => $session->id,
            'join_code' => $session->join_code,
            'slug' => $session->slug,
            'mode' => $session->mode,
            'question_type' => $session->question_type,
            'status' => $session->status,
            'current_sequence' => $session->current_sequence,
            'question_count' => $session->question_count,
            'round_seconds' => $session->round_seconds,
            'question_ends_at' => optional($session->question_ends_at)->toIso8601String(),
            'rope_position' => $session->rope_position,
            'finish_line' => $this->finishLine($session),
            'winner' => $session->winner,
            'teams' => [
                'A' => [
                    'label' => $session->team_a_label,
                    'score' => $session->team_a_score,
                    'correct' => $session->team_a_correct,
                ],
                'B' => [
                    'label' => $session->team_b_label,
                    'score' => $session->team_b_score,
                    'correct' => $session->team_b_correct,
                ],
            ],
            'players' => $session->players->map(fn (GameSessionPlayer $p) => [
                'id' => $p->id,
                'display_name' => $p->display_name,
                'team' => $p->team,
                'role' => $p->role,
                'status' => $p->status,
                'is_cpu' => $p->is_cpu,
                'score' => $p->score,
                'correct_count' => $p->correct_count,
                'avatar_seed' => $p->avatar_seed,
            ])->values(),
            'question' => $current ? $current->toPublicArray() : null,
        ];
    }

    /**
     * The content scope's roster for the config screen — the classes the caller
     * belongs to (student) or teaches (teacher), each with its students. This
     * is the "classmates per year/subject/section" source. School-scoped.
     *
     * @return array<string, mixed>
     */
    public function roster(User $user): array
    {
        $schoolId = (int) $user->school_id;
        $isTeacher = strtolower((string) ($user->role ?? '')) === 'teacher';

        $classesQ = DB::table('classes as c')
            ->join('sections as s', 's.id', '=', 'c.section_id')
            ->join('subjects as sub', 'sub.id', '=', 'c.subject_id')
            ->where('c.school_id', $schoolId)
            ->where('c.is_active', 1);

        if ($isTeacher) {
            $classesQ->where(fn ($q) => $q->where('c.teacher_id', $user->id)
                ->orWhereIn('c.id', fn ($sub) => $sub->select('class_id')->from('class_teacher')->where('teacher_id', $user->id)));
        } else {
            $studentId = DB::table('students')->where('user_id', $user->id)->value('id');
            if (! $studentId) {
                return ['classes' => []];
            }
            // Same trio StudentTestAccess uses: pivot, subject enrolments
            // (higher ed), and the enrolled section's classes (basic ed).
            $classesQ->where(function ($q) use ($studentId) {
                $q->whereIn('c.id', fn ($sub) => $sub->select('class_model_id')->from('class_student')->where('student_id', $studentId))
                    ->orWhereIn('c.id', fn ($sub) => $sub->select('ses.class_id')->from('student_enrollment_subjects as ses')
                        ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
                        ->where('e.student_id', $studentId))
                    ->orWhereIn('c.section_id', fn ($sub) => $sub->select('section_id')->from('student_enrollments')
                        ->where('student_id', $studentId)
                        ->whereIn('status', ['enrolled', 'provisionally_enrolled'])
                        ->whereNotNull('section_id'));
            });
        }

        $classes = $classesQ->orderBy('sub.name')->orderBy('s.name')
            ->get(['c.id', 'c.section_id', 'sub.name as subject', 's.name as section', 's.year_level'])->all();

        if (! $classes) {
            return ['classes' => []];
        }

        $classIds = array_map(fn ($c) => $c->id, $classes);
        $sectionIds = array_values(array_unique(array_filter(array_map(fn ($c) => $c->section_id, $classes))));
        $fields = ['st.id', 'st.first_name', 'st.preferred_name', 'st.last_name'];

        // Students reach a class three ways; merge all of them per class.
        // 1) explicit class_student pivot rows
        $pivot = DB::table('class_student as cs')
            ->join('students as st', 'st.id', '=', 'cs.student_id')
            ->whereIn('cs.class_model_id', $classIds)
            ->where('st.school_id', $schoolId)
            ->get(array_merge(['cs.class_model_id as class_id'], $fields));
        // 2) higher-ed subject enrolments
        $subject = DB::table('student_enrollment_subjects as ses')
            ->join('student_enrollments as e', 'e.id', '=', 'ses.student_enrollment_id')
            ->join('students as st', 'st.id', '=', 'e.student_id')
            ->whereIn('ses.class_id', $classIds)
            ->where('st.school_id', $schoolId)
            ->get(array_merge(['ses.class_id'], $fields));
        // 3) basic ed: everyone actively enrolled in the class's section
        $bySection = ! $sectionIds ? collect() : DB::table('student_enrollments as e')
            ->join('students as st', 'st.id', '=', 'e.student_id')
            ->whereIn('e.section_id', $sectionIds)
            ->whereIn('e.status', ['enrolled', 'provisionally_enrolled'])
            ->where('st.school_id', $schoolId)
            ->get(array_merge(['e.section_id'], $fields))
            ->groupBy('section_id');

        $byClass = [];
        foreach ($classes as $c) {
            $merged = collect()
                ->concat($pivot->where('class_id', $c->id))
                ->concat($subject->where('class_id', $c->id))
                ->concat($bySection[$c->section_id] ?? collect())
                ->unique('id')
                ->sortBy([['last_name', 'asc'], ['first_name', 'asc']])
                ->values();
            $byClass[$c->id] = $merged;
        }

        return [
            'classes' => array_map(fn ($c) => [
                'id' => $c->id,
                'label' => $c->subject.' — '.$c->section,
                'year_level' => $c->year_level,
                'students' => ($byClass[$c->id] ?? collect())->map(fn ($st) => [
                    'id' => $st->id,
                    'name' => $this->studentName($st),
                    'avatar_seed' => 's'.$st->id,
                ])->values()->all(),
            ], $classes),
        ];
    }

    /**
     * Options the shared pre-game config screen needs: whether the bank holds
     * any 'advanced' items for this scope/type (so the UI can hide the tier),
     * and the hosting teacher's sections. School-scoped.
     *
     * @param  array<string, mixed>  $scope  type + subject/topic/lesson/competency/level ids
     * @return array{advanced_available:bool, sections:array<int, array{id:int,label:string}>}
     */
    public function configOptions(User $user, array $scope): array
    {
        $schoolId = (int) $user->school_id;

        $types = match ((string) ($scope['type'] ?? '')) {
            'identification' => ['identification'],
            'true_false' => ['true_false'],
            'mcq', 'multiple_choice' => ['mcq', 'multiple_choice'],
            default => ['mcq', 'multiple_choice', 'true_false', 'identification'],
        };

        $advancedAvailable = DB::table('questions')
            ->where('school_id', $schoolId)
            ->whereIn('question_type', $types)
            ->where('difficulty', 'advanced')
            ->when(! empty($scope['subject_id']), fn ($q) => $q->where('subject_id', (int) $scope['subject_id']))
            ->when(! empty($scope['topic_id']), fn ($q) => $q->where('topic_id', (int) $scope['topic_id']))
            ->when(! empty($scope['lesson_id']), fn ($q) => $q->where('lesson_id', (int) $scope['lesson_id']))
            ->when(! empty($scope['competency_id']), fn ($q) => $q->where('competency_id', (int) $scope['competency_id']))
            ->when(! empty($scope['academic_level_id']), fn ($q) => $q->where('academic_level_id', (int) $scope['academic_level_id']))
            ->exists();

        return [
            'advanced_available' => $advancedAvailable,
            'sections' => $this->teacherSections($user, $schoolId),
        ];
    }

    /** The distinct sections a teacher teaches (empty for students). */
    private function teacherSections(User $user, int $schoolId): array
    {
        if (strtolower((string) ($user->role ?? '')) !== 'teacher') {
            return [];
        }

        return DB::table('classes as c')
            ->join('sections as s', 's.id', '=', 'c.section_id')
            ->leftJoin('terms as t', 't.id', '=', 's.term_id')
            ->where('c.school_id', $schoolId)
            ->where('c.is_active', 1)
            ->where(fn ($q) => $q->where('c.teacher_id', $user->id)
                ->orWhereIn('c.id', fn ($sub) => $sub->select('class_id')->from('class_teacher')->where('teacher_id', $user->id)))
            ->distinct()
            ->orderBy('s.year_level')->orderBy('s.name')
            ->get(['s.id', 's.name', 's.year_level', 't.education_level'])
            // ADR-0006 vocabulary: basic ed says "Grade N", higher ed "Year N".
            ->map(fn ($s) => [
                'id' => (int) $s->id,
                'label' => $s->name.' ('.($s->education_level === 'basic_ed' ? 'Grade' : 'Year').' '.$s->year_level.')',
            ])
            ->values()->all();
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function resolvePlayer(GameSession $session, array $in): ?GameSessionPlayer
    {
        if (! empty($in['player_id'])) {
            return $session->players()->whereKey((int) $in['player_id'])->first();
        }
        // Single-screen host relay: attribute to a representative of the team.
        if (in_array($in['team'] ?? null, ['A', 'B'], true)) {
            return $session->players()->where('team', $in['team'])->orderBy('id')->first();
        }

        return null;
    }

    private function grade(GameSessionQuestion $question, array $in): bool
    {
        if ($question->choices !== null && $question->correct_index !== null) {
            return array_key_exists('choice_index', $in)
                && (int) $in['choice_index'] === (int) $question->correct_index;
        }

        // Identification: normalized text match.
        return $this->normalize((string) ($in['answer_text'] ?? '')) !== ''
            && $this->normalize((string) ($in['answer_text'] ?? '')) === $this->normalize((string) $question->answer_text);
    }

    private function normalize(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($s)));
    }

    private function finishLine(GameSession $session): int
    {
        return max(2, (int) ceil($session->question_count / 2));
    }

    private function decideWinner(GameSession $session): string
    {
        if ($session->rope_position > 0) {
            return 'A';
        }
        if ($session->rope_position < 0) {
            return 'B';
        }
        if ($session->team_a_score !== $session->team_b_score) {
            return $session->team_a_score > $session->team_b_score ? 'A' : 'B';
        }

        return 'draw';
    }

    private function uniqueJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (DB::table('game_sessions')->where('join_code', $code)->exists());

        return $code;
    }

    private function label(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        return $value !== '' ? Str::limit($value, 40, '') : $fallback;
    }

    private function userName(User $user): string
    {
        return trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Host';
    }

    private function studentName(object $st): string
    {
        $first = trim((string) ($st->preferred_name ?? '')) ?: trim((string) ($st->first_name ?? ''));

        return trim($first.' '.($st->last_name ?? '')) ?: 'Student';
    }

    /**
     * @return array{ok:bool, correct:bool, reason:?string, state:array<string,mixed>}
     */
    private function result(bool $ok, bool $correct, ?string $reason, GameSession $session): array
    {
        return [
            'ok' => $ok,
            'correct' => $correct,
            'reason' => $reason,
            'state' => $this->state($session),
        ];
    }
}
