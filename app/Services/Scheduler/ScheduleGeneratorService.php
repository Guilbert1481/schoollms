<?php

namespace App\Services\Scheduler;

class ScheduleGeneratorService
{
    /** @var array{start?: string, end?: string}|null */
    private ?array $currentBreak = null;

    /** End-of-day time for the run currently in progress (e.g. "17:00"). */
    private ?string $currentDayEnd = null;

    /**
     * Teacher constraints.
     * Full-time keys: max_hours_per_week, max_hours_per_day, work_days_per_week,
     * min_hours_per_day, prioritize_full_time.
     * Part-time key: part_time_min_hours_per_day.
     */
    private array $teacherConstraints = [
        'max_hours_per_week'   => 24,
        'max_hours_per_day'    => 5,
        'work_days_per_week'   => 5,
        'min_hours_per_day'    => 1.0,
        'prioritize_full_time' => true,
        'part_time_min_hours_per_day' => 1.0,
    ];

    /**
     * Generate top schedules.
     *
     * Input payload structure:
     * [
     *   'policy' => [...],
     *   'time'   => ['days_of_week'=>[], 'start_time'=>'07:00', 'end_time'=>'17:00',
     *                'slot_duration'=>60, 'break_time'=>['start'=>'12:00','end'=>'13:00']],
     *   'weights'=> ['gap'=>1,'compact'=>1,'teacher'=>1,'room'=>1],
     *   'sections' => [
     *      ['id'=>1,'name'=>'BSED-1A','size'=>40,'block'=>'morning|afternoon|auto',
     *       'subjects'=>[ ['id'=>5,'units'=>3,'hours'=>3] ]],
     *   ],
     *   'teachers' => [
     *      ['id'=>2,'name'=>'...','subjects'=>[5,6],
     *       'availability'=>[ ['day'=>'monday','start'=>'08:00','end'=>'12:00'] ] ],
     *   ],
     *   'rooms' => [ ['id'=>1,'name'=>'R101','capacity'=>50] ],
     * ]
     *
     * @return array{options: array} top 3 schedules
     */
    public function generate(array $payload, int $attempts = 8): array
    {
        $policy  = $payload['policy']   ?? [];
        $sectionPolicy = is_array($payload['section_policy'] ?? null) ? $payload['section_policy'] : [];
        $time    = $payload['time']     ?? [];
        $weights = $payload['weights']  ?? [];
        $sections = $payload['sections'] ?? [];
        $teachers = $payload['teachers'] ?? [];
        $rooms    = $payload['rooms']    ?? [];

        // Teacher constraints (full-time only).
        $tc = is_array($payload['teacher_constraints'] ?? null) ? $payload['teacher_constraints'] : [];
        $this->teacherConstraints = [
            'max_hours_per_week'   => isset($tc['max_hours_per_week'])   ? (float) $tc['max_hours_per_week']   : 24.0,
            'max_hours_per_day'    => isset($tc['max_hours_per_day'])    ? (float) $tc['max_hours_per_day']    : 5.0,
            'work_days_per_week'   => isset($tc['work_days_per_week'])   ? (int)   $tc['work_days_per_week']   : 5,
            'min_hours_per_day'    => isset($tc['min_hours_per_day'])    ? (float) $tc['min_hours_per_day']    : 1.0,
            'prioritize_full_time' => array_key_exists('prioritize_full_time', $tc) ? (bool) $tc['prioritize_full_time'] : true,
            'part_time_min_hours_per_day' => isset($tc['part_time_min_hours_per_day']) ? (float) $tc['part_time_min_hours_per_day'] : 1.0,
        ];

        $candidates = [];
        for ($i = 0; $i < max(1, $attempts); $i++) {
            $shuffledSections = $sections;
            shuffle($shuffledSections);
            $sched = $this->runOnce($policy, $sectionPolicy, $time, $shuffledSections, $teachers, $rooms, $i);
            $sched['score'] = $this->scoreSchedule($sched['sessions'], $weights, $time);
            $candidates[] = $sched;
        }

        // Rank attempts by how much of the weekly quota was actually placed,
        // not by raw conflict count (which now grows with diagnostic entries
        // and would otherwise reward attempts that DROP whole sections):
        //   1. Most VALID sessions (status='valid' = teacher+room+slot)
        //   2. Most TOTAL hours rendered (valid + soft + placeholder)
        //   3. Most distinct sections appearing on the calendar
        //   4. Fewest HARD conflicts (severity='infeasible' / status='conflict')
        //   5. Highest engine score (gap/compact/teacher/room weights)
        usort($candidates, function ($a, $b) {
            $av = collect($a['sessions'] ?? [])->where('status', 'valid')->count();
            $bv = collect($b['sessions'] ?? [])->where('status', 'valid')->count();
            if ($av !== $bv) return $bv <=> $av;

            $ah = collect($a['sessions'] ?? [])->sum('hours');
            $bh = collect($b['sessions'] ?? [])->sum('hours');
            if (abs($ah - $bh) > 0.001) return $bh <=> $ah;

            $as = collect($a['sessions'] ?? [])->pluck('section_id')->unique()->count();
            $bs = collect($b['sessions'] ?? [])->pluck('section_id')->unique()->count();
            if ($as !== $bs) return $bs <=> $as;

            $hard = fn($c) => collect($c['conflicts'] ?? [])
                ->whereIn('severity', ['infeasible', 'unplaced'])->count();
            $ac = $hard($a);
            $bc = $hard($b);
            if ($ac !== $bc) return $ac <=> $bc;

            return $b['score'] <=> $a['score'];
        });

        return [
            'options' => $this->dedupeOptions(array_slice($candidates, 0, 3)),
        ];
    }

    /**
     * Collapse options that look identical to the user. We use a two-layer
     * fingerprint:
     *   1. UI fingerprint — score, session count, conflict count.
     *      If two cards would render with the SAME stats the user sees,
     *      they're collapsed regardless of internal ordering differences.
     *   2. Deep fingerprint — sorted (section, subject, day, start, end,
     *      teacher, room, status) tuples. Catches cards with same stats
     *      but genuinely different placements (rare but possible).
     */
    private function dedupeOptions(array $options): array
    {
        $seen = [];
        $out  = [];
        foreach ($options as $opt) {
            $sessions  = $opt['sessions']  ?? [];
            $conflicts = $opt['conflicts'] ?? [];

            $uiSig = sprintf(
                '%.4f|%d|%d',
                round((float) ($opt['score'] ?? 0), 4),
                count($sessions),
                count($conflicts)
            );

            $deepSig = collect($sessions)
                ->map(fn($s) => implode('|', [
                    $s['section_id']  ?? '',
                    $s['subject_id']  ?? '',
                    $s['day_of_week'] ?? '',
                    $s['start_time']  ?? '',
                    $s['end_time']    ?? '',
                    $s['teacher_id']  ?? '',
                    $s['room_id']     ?? '',
                    $s['status']      ?? '',
                ]))
                ->sort()
                ->values()
                ->implode("\n");

            $hash = md5($uiSig . '||' . $deepSig);
            if (isset($seen[$hash])) continue;

            // Extra guard: if UI fingerprint alone matches a previous option,
            // treat as duplicate even if internal session order differs. This
            // is what the user means by "same option".
            if (isset($seen['ui:' . $uiSig])) continue;

            $seen[$hash]            = true;
            $seen['ui:' . $uiSig]   = true;
            $out[]                  = $opt;
        }
        return $out ?: $options; // never return empty
    }

    private function runOnce(array $policy, array $sectionPolicy, array $time, array $sections, array $teachers, array $rooms, int $seed): array
    {
        $days  = $time['days_of_week']  ?? ['monday','tuesday','wednesday','thursday','friday'];
        $start = $time['start_time']    ?? '07:00';
        $end   = $time['end_time']      ?? '17:00';
        $slot  = (int) ($time['slot_duration'] ?? 30); // minutes
        $break = $time['break_time']    ?? null;
        $existingSessions = $time['existing_sessions'] ?? [];
        $this->currentBreak = $break;
        $this->currentDayEnd = $end;

        // ---- Section / Subject policy extraction ----
        $maxHrsDay  = (float) ($sectionPolicy['max_hours_per_day']     ?? 8);
        $minHrsDay  = (float) ($sectionPolicy['min_hours_per_day']     ?? 0);
        $maxSubsDay = (int)   ($sectionPolicy['max_subjects_per_day']  ?? $policy['max_subjects_per_day'] ?? 6);
        $minSubsDay = (int)   ($sectionPolicy['min_subjects_per_day']  ?? 0);
        $minSesHrs  = (float) ($policy['min_session_hours']  ?? 1);
        $maxSesHrs  = (float) ($policy['max_session_hours']  ?? 2);
        $maxGapMin  = (int)   ($sectionPolicy['max_allowed_gap']       ?? $policy['max_allowed_gap']    ?? 60);
        $allowGaps  = array_key_exists('allow_gaps', $sectionPolicy)
            ? (bool) $sectionPolicy['allow_gaps']
            : (bool) ($policy['allow_gaps'] ?? true);
        if (! $allowGaps) { $maxGapMin = 0; }
        $minDays    = max(1, (int) ($sectionPolicy['min_days_per_week'] ?? $policy['min_days_per_week'] ?? 1));
        $maxDays    = max($minDays, (int) ($sectionPolicy['max_days_per_week'] ?? $policy['max_days_per_week'] ?? 3));
        $teacherMaxDay = (float) ($this->teacherConstraints['max_hours_per_day'] ?? 5);

        // Pre-build slot grids per day.
        $slots = [];
        foreach ($days as $day) {
            $slots[$day] = $this->buildSlots($start, $end, $slot, $break);
        }

        // Booking maps (global across sections within this run).
        $teacherBusy = [];
        $roomBusy    = [];
        $sectionBusy = [];
        foreach ($existingSessions as $session) {
            $day    = $session['day_of_week'] ?? null;
            $startT = $session['start_time'] ?? null;
            $endT   = $session['end_time']   ?? null;
            if (! $day || ! $startT || ! $endT) continue;
            if (! empty($session['section_id'])) $sectionBusy[$session['section_id']][$day][] = [$startT, $endT];
            if (! empty($session['teacher_id'])) $teacherBusy[$session['teacher_id']][$day][] = [$startT, $endT];
            if (! empty($session['room_id']))    $roomBusy[$session['room_id']][$day][]       = [$startT, $endT];
        }

        $sessions  = [];
        $conflicts = [];

        // Override gap defaults to match user spec:
        //   "no session gap if possible, else 30 min, max 2 gaps per day"
        $maxGapMin = 30;
        $maxGapsPerDay = 2;

        foreach ($sections as $section) {
            $secSize  = (int) ($section['size'] ?? 40);
            $block    = $section['block'] ?? 'auto';
            $subjects = $section['subjects'] ?? [];
            $sectionLabel = $section['name'] ?? ('#' . $section['id']);

            // ---- Upfront feasibility math check ----
            $totalHours = collect($subjects)->sum(
                fn($s) => (float) ($s['hours'] ?? max(1, (float) ($s['units'] ?? 3)))
            );
            if ($maxHrsDay > 0 && $totalHours > $maxHrsDay * count($days) + 0.001) {
                $conflicts[] = [
                    'section_id' => $section['id'],
                    'severity'   => 'infeasible',
                    'reason'     => sprintf(
                        'Section "%s" needs %.1f hr/week but %d active days × Max Hours/Day (%.1f) only allows %.1f.',
                        $sectionLabel, $totalHours, count($days), $maxHrsDay, count($days) * $maxHrsDay
                    ),
                ];
            }

            // No more shape/dayCombo lock — section uses every active day from
            // time settings; each subject independently picks its own 1-3 days
            // via the priority split plans inside placeSectionBudgetFirst.
            $result = $this->placeSectionBudgetFirst(
                $section, $subjects, $days, $slots, $slot, $break, $block, $secSize,
                $teachers, $rooms,
                $sectionBusy, $teacherBusy, $roomBusy,
                $maxHrsDay, $minHrsDay, $maxSubsDay, $minSubsDay,
                $minSesHrs, $maxSesHrs, $maxGapMin, $allowGaps, $teacherMaxDay,
                $maxGapsPerDay
            );
            foreach ($result['sessions']  as $s) $sessions[]  = $s;
            foreach ($result['conflicts'] as $c) $conflicts[] = $c;
        }

        $this->consolidateFullTimeUnderMinDays($sessions, $teachers, $teacherBusy);
        $conflicts = array_merge($conflicts, $this->fullTimeWeeklyTargetConflicts($teachers, $teacherBusy));
        $conflicts = array_merge($conflicts, $this->fullTimeDailyMinimumConflicts($teachers, $teacherBusy));

        return [
            'sessions'  => $sessions,
            'conflicts' => $conflicts,
            'attempt'   => $seed,
        ];
    }

    /**
     * Subject-driven placement: each subject is split into 1-3 daily chunks
     * via priority plans (see buildSubjectSplits) — most days first, then
     * smallest variance, single day last. Plans are tried atomically; on
     * failure the whole plan is rolled back and the next plan is attempted.
     * If no plan fits, the subject's full weekly quota is rendered as
     * placeholder boxes so the user always sees the unmet hours.
     *
     * @return array{sessions:array, conflicts:array}
     */
    private function placeSectionBudgetFirst(
        array $section, array $subjects, array $activeDays,
        array $slots, int $slot, ?array $break, string $block, int $secSize,
        array $teachers, array $rooms,
        array &$sectionBusy, array &$teacherBusy, array &$roomBusy,
        float $maxHrsDay, float $minHrsDay, int $maxSubsDay, int $minSubsDay,
        float $minSesHrs, float $maxSesHrs, int $maxGapMin, bool $allowGaps, float $teacherMaxDay,
        int $maxGapsPerDay = 2
    ): array {
        $sectionId = $section['id'];
        $sectionLabel = $section['name'] ?? ('#' . $sectionId);

        // (1) Subject budgets — units × 1hr.
        $budgets = collect($subjects)
            ->mapWithKeys(fn($s) => [
                $s['id'] => (float) ($s['hours'] ?? max(1, (float) ($s['units'] ?? 3))),
            ])
            ->all();

        // Per-section per-attempt trackers.
        $tentative          = [];
        $sectionDayHours    = [];
        $sectionDaySubjects = [];
        $subjectTeacherPin  = [];
        $sectionRoomPin     = $this->preselectSectionRoom($rooms, $secSize, $roomBusy);
        // sticky room for the whole section/week — picked up-front so EVERY
        // chunk for this section prefers the same room.
        $failureReasons     = [];

        // Order subjects by scarcity of qualified teachers (hardest first).
        $subjectOrder = collect($subjects)
            ->sortBy(function ($s) use ($teachers) {
                return collect($teachers)
                    ->filter(fn($t) => in_array($s['id'], $t['subjects'] ?? [], true))
                    ->count();
            })
            ->values()
            ->all();

        foreach ($subjectOrder as $subj) {
            $sid    = $subj['id'];
            $isLab  = ! empty($subj['is_lab']);
            $effMin = $isLab ? 2.0 : $minSesHrs;
            $effMax = $isLab ? 3.0 : max($minSesHrs, $maxSesHrs);
            $weekly = (float) ($budgets[$sid] ?? 0);
            if ($weekly <= 0.001) continue;

            // Build priority-ordered split plans. Per user spec:
            //   3 units → [1,1,1] > [1.5,1.5] > [2,1] > [1,2] > [3]
            //   2 units → [1,1] > [2]
            //   ...     ranked: most days first, then equal split, then single.
            $plans = $this->buildSubjectSplits($weekly, $effMin, $effMax);

            $subjectPlaced = false;
            foreach ($plans as $plan) {
                // Atomic snapshot — restore everything if this plan fails.
                $snap = [
                    'sectionBusy'        => $sectionBusy,
                    'teacherBusy'        => $teacherBusy,
                    'roomBusy'           => $roomBusy,
                    'sectionDayHours'    => $sectionDayHours,
                    'sectionDaySubjects' => $sectionDaySubjects,
                    'subjectTeacherPin'  => $subjectTeacherPin,
                    'sectionRoomPin'     => $sectionRoomPin,
                    'tentativeCount'     => count($tentative),
                ];

                $usedDays = [];
                $planOk   = true;

                foreach ($plan as $chunk) {
                    $placed = false;

                    foreach ($activeDays as $day) {
                        if (in_array($day, $usedDays, true)) continue; // one chunk per day
                        if (($sectionDayHours[$day] ?? 0) + $chunk > $maxHrsDay + 0.001) continue;
                        if (in_array($sid, $sectionDaySubjects[$day] ?? [], true)) continue;
                        if (count($sectionDaySubjects[$day] ?? []) >= $maxSubsDay) continue;

                        // Two-tier window search: prefer abutting (no gap),
                        // fall back to ≤30-min gap, max 2 gaps/day.
                        $win = $this->findWindowTwoTier(
                            $day, $chunk, $slot, $slots, $block,
                            $sectionBusy[$sectionId][$day] ?? [],
                            $allowGaps, $maxGapMin, $maxGapsPerDay, $break
                        );
                        if (! $win) continue;

                        $teacher = $this->pickTeacher(
                            $teachers, $sid, $day, $win['start'], $win['end'], $teacherBusy,
                            $subjectTeacherPin[$sid] ?? null
                        );
                        // Prefer the section's sticky room first, then the
                        // subject's configured preferred_room_id, so the same
                        // section uses the same room throughout the week when
                        // capacity & availability allow.
                        $room = $this->pickRoom(
                            $rooms, $secSize, $day, $win['start'], $win['end'], $roomBusy,
                            $sectionRoomPin ?? ($subj['preferred_room_id'] ?? null)
                        );

                        if ($teacher) {
                            $tHrs = $this->teacherHoursOnDay($teacher, $day, $teacherBusy);
                            if ($tHrs + $chunk > $teacherMaxDay + 0.001) {
                                $teacher = null; // soft drop → needs_teacher
                            }
                        }

                        // Per spec: room is always assigned (pickRoom never
                        // returns null when rooms exist). Only the teacher
                        // can be missing.
                        $status  = 'valid';
                        $reasons = [];
                        if (! $teacher) {
                            $status = 'needs_teacher';
                            $reasons = ['No qualified/available teacher within daily cap'];
                        }

                        // Commit chunk.
                        $sectionBusy[$sectionId][$day][] = [$win['start'], $win['end']];
                        if ($teacher) $teacherBusy[$teacher['id']][$day][] = [$win['start'], $win['end']];
                        if ($room)    $roomBusy[$room['id']][$day][]       = [$win['start'], $win['end']];
                        $sectionDayHours[$day]      = ($sectionDayHours[$day] ?? 0) + $chunk;
                        $sectionDaySubjects[$day][] = $sid;
                        $usedDays[]                 = $day;
                        if ($teacher && ! isset($subjectTeacherPin[$sid])) {
                            $subjectTeacherPin[$sid] = $teacher['id'];
                        }
                        if ($room && $sectionRoomPin === null) {
                            $sectionRoomPin = (int) $room['id'];
                        }

                        $tentative[] = [
                            'section_id'              => $sectionId,
                            'section_name'            => $section['name'] ?? null,
                            'subject_id'              => $sid,
                            'subject_name'            => $subj['name'] ?? null,
                            'is_lab'                  => $isLab,
                            'teacher_id'              => $teacher['id'] ?? null,
                            'teacher_name'            => $teacher['name'] ?? null,
                            'teacher_employment_type' => $teacher['employment_type'] ?? null,
                            'teacher_is_regular'      => $teacher ? $this->isRegularTeacher($teacher) : null,
                            'room_id'                 => $room['id']   ?? null,
                            'room_name'               => $room['name'] ?? null,
                            'day_of_week'             => $day,
                            'start_time'              => $win['start'],
                            'end_time'                => $win['end'],
                            'hours'                   => $chunk,
                            'status'                  => $status,
                            'conflict_reasons'        => $reasons,
                        ];
                        $placed = true;
                        break;
                    }

                    if (! $placed) {
                        $planOk = false;
                        break;
                    }
                }

                if ($planOk) {
                    $budgets[$sid] = 0;
                    $subjectPlaced = true;
                    break;
                }

                // Rollback this plan.
                $sectionBusy        = $snap['sectionBusy'];
                $teacherBusy        = $snap['teacherBusy'];
                $roomBusy           = $snap['roomBusy'];
                $sectionDayHours    = $snap['sectionDayHours'];
                $sectionDaySubjects = $snap['sectionDaySubjects'];
                $subjectTeacherPin  = $snap['subjectTeacherPin'];
                $sectionRoomPin     = $snap['sectionRoomPin'];
                $tentative          = array_slice($tentative, 0, $snap['tentativeCount']);
            }

            if (! $subjectPlaced) {
                // No priority split fit → render full weekly quota as placeholder
                // boxes so the user sees the unmet hours.
                $failureReasons[] = sprintf(
                    'Subject "%s": no priority split (%dx 1hr / 1.5+1.5 / 2+1 / 1 day) fit; rendered as placeholder.',
                    $subj['name'] ?? ('#' . $sid), (int) round($weekly)
                );
                $this->renderPlaceholderHours(
                    $tentative, $section, $subj, $weekly, $effMin, $effMax,
                    $activeDays, $sectionDayHours, $maxHrsDay
                );
                $budgets[$sid] = 0;
            }
        }

        // Informational conflicts.
        $localConflicts = [];
        foreach ($failureReasons as $fr) {
            $localConflicts[] = [
                'section_id' => $sectionId,
                'severity'   => 'unplaced_hours',
                'reason'     => '[' . $sectionLabel . '] ' . $fr,
            ];
        }

        if ($minHrsDay > 0) {
            foreach ($sectionDayHours as $d => $h) {
                if ($h > 0 && $h + 0.001 < $minHrsDay) {
                    $localConflicts[] = [
                        'section_id' => $sectionId,
                        'severity'   => 'short_day',
                        'reason'     => sprintf(
                            '[%s] %s has only %.1f hr (minimum %.1f) — consider consolidating subjects on this day.',
                            $sectionLabel, ucfirst($d), $h, $minHrsDay
                        ),
                    ];
                }
            }
        }
        if ($minSubsDay > 0) {
            foreach ($sectionDaySubjects as $d => $subs) {
                if (count($subs) < $minSubsDay) {
                    $localConflicts[] = [
                        'section_id' => $sectionId,
                        'severity'   => 'short_day',
                        'reason'     => sprintf(
                            '[%s] %s has only %d subject(s) (minimum %d).',
                            $sectionLabel, ucfirst($d), count($subs), $minSubsDay
                        ),
                    ];
                }
            }
        }

        return ['sessions' => $tentative, 'conflicts' => $localConflicts];
    }

    /**
     * Build priority-ordered split plans for a subject.
     * Each plan is an ordered list of session lengths whose sum ≈ weekly hours.
     * Each entry must lie in [effMin, effMax]; total entries (= days used) ≤ 3.
     *
     * Priority (per user spec):
     *   1. Most days first  (e.g. 1+1+1 before 1.5+1.5 before 3)
     *   2. Within same day count: smallest variance first (1.5+1.5 before 2+1)
     *   3. Then ascending order (2+1 before 1+2 — placement order is flexible)
     */
    private function buildSubjectSplits(float $weekly, float $effMin, float $effMax): array
    {
        $w     = round($weekly * 2) / 2; // snap to 0.5
        $step  = 0.5;
        $plans = [];

        // 3-day splits  (a ≤ b ≤ c)
        for ($a = $effMin; $a <= $effMax + 0.001; $a += $step) {
            for ($b = $a; $b <= $effMax + 0.001; $b += $step) {
                $c = round($w - $a - $b, 2);
                if ($c + 0.001 < $b)        continue;       // canonical
                if ($c + 0.001 < $effMin)   continue;
                if ($c > $effMax + 0.001)   continue;
                $plans[] = [round($a, 2), round($b, 2), round($c, 2)];
            }
        }
        // 2-day splits (a ≤ b)
        for ($a = $effMin; $a <= $effMax + 0.001; $a += $step) {
            $b = round($w - $a, 2);
            if ($b + 0.001 < $a)        continue;
            if ($b + 0.001 < $effMin)   continue;
            if ($b > $effMax + 0.001)   continue;
            $plans[] = [round($a, 2), round($b, 2)];
        }
        // 1-day plan
        if ($w + 0.001 >= $effMin && $w <= $effMax + 0.001) {
            $plans[] = [round($w, 2)];
        }

        // Sort: more days first, then smallest variance, then ascending.
        usort($plans, function ($a, $b) {
            if (count($a) !== count($b)) return count($b) <=> count($a);
            $va = max($a) - min($a);
            $vb = max($b) - min($b);
            if (abs($va - $vb) > 0.001) return $va <=> $vb;
            return $a[0] <=> $b[0];
        });

        // Fallback: if the strict splits produced nothing (e.g. weekly=2 but
        // effMin=2.5), still emit a single-day plan equal to weekly so the
        // quota can at least be rendered (it'll likely become a placeholder
        // if the day can't host it).
        if (empty($plans)) {
            $plans[] = [round($w, 2)];
        }

        return $plans;
    }

    /**
     * Two-tier window search:
     *   tier 1 : abutting (no gap) — preferred
     *   tier 2 : gap ≤ $maxGapMin minutes, no more than $maxGapsPerDay gaps/day
     *
     * If $allowGaps is false, only tier 1 is consulted.
     */
    private function findWindowTwoTier(
        string $day, float $hours, int $slot, array $slots, string $block,
        array $existingSectionDay, bool $allowGaps, int $maxGapMin, int $maxGapsPerDay, ?array $break
    ): ?array {
        // Tier 1 — strictly no gap (only when there's something to abut against,
        // or the day is empty).
        $w = $this->findWindow($day, $hours, $slot, $slots, $block, $existingSectionDay, false, 0, $break);
        if ($w) return $w;
        if (empty($existingSectionDay)) {
            // No abut needed; any valid window works.
            return $this->findWindow($day, $hours, $slot, $slots, $block, $existingSectionDay, true, 0, $break);
        }

        if (! $allowGaps) return null;

        // Tier 2 — limited gap.
        $gapsToday = $this->countGaps($existingSectionDay);
        if ($gapsToday >= $maxGapsPerDay) return null;

        return $this->findWindow($day, $hours, $slot, $slots, $block, $existingSectionDay, true, $maxGapMin, $break);
    }

    /**
     * Count the number of distinct gaps between consecutive occupied blocks
     * already booked on a given day (ignoring tiny touches).
     */
    private function countGaps(array $existing): int
    {
        if (count($existing) < 2) return 0;
        $sorted = $existing;
        usort($sorted, fn($a, $b) => strcmp($a[0], $b[0]));
        $gaps = 0;
        for ($i = 1; $i < count($sorted); $i++) {
            $prevEnd  = strtotime($sorted[$i-1][1]);
            $thisStart = strtotime($sorted[$i][0]);
            if ($thisStart - $prevEnd > 0) $gaps++;
        }
        return $gaps;
    }

    /**
     * Choose a session length that:
     *   - is within [effMin, effMax]
     *   - is ≤ remaining budget
     *   - leaves a feasible remainder (0 OR ≥ effMin)
     *
     * Tries the largest valid chunk first (e.g. 4hr remaining w/ effMax=3 → 2hr
     * so that next chunk = 2hr; avoids leaving an unplaceable 1hr stub).
     */
    private function chooseChunk(float $remaining, float $effMin, float $effMax): ?float
    {
        if ($remaining + 0.001 < $effMin) return null;

        $maxC = min($effMax, $remaining);
        // Walk down in 0.5hr steps to find the biggest chunk that leaves a
        // legal remainder.
        for ($c = $maxC; $c + 0.001 >= $effMin; $c -= 0.5) {
            $rem = round($remaining - $c, 2);
            if (abs($rem) < 0.01) return $c;          // exact fit
            if ($rem + 0.001 >= $effMin) return $c;   // remainder is placeable
        }
        return null;
    }

    /**
     * Build a list of feasible session lengths (largest → smallest) for the
     * placement loop to try in order. Each returned chunk size:
     *   - is within [effMin, effMax]
     *   - is ≤ $remaining
     *   - either fully consumes $remaining OR leaves a remainder ≥ effMin.
     *
     * Returning multiple sizes lets the loop fall back to a shorter session if
     * the largest chunk has no host day.
     */
    private function candidateChunks(float $remaining, float $effMin, float $effMax): array
    {
        if ($remaining + 0.001 < $effMin) return [];
        $maxC = min($effMax, $remaining);
        $out  = [];
        for ($c = $maxC; $c + 0.001 >= $effMin; $c -= 0.5) {
            $rem = round($remaining - $c, 2);
            if (abs($rem) < 0.01 || $rem + 0.001 >= $effMin) {
                $out[] = round($c, 2);
            }
        }
        // Final fallback: exact effMin even if it leaves a stub remainder
        // (the stub itself will become a placeholder box).
        if (empty($out) && $remaining + 0.001 >= $effMin) {
            $out[] = $effMin;
        }
        return $out;
    }

    /**
     * Append placeholder sessions for hours that the time grid couldn't host.
     * These render on the calendar with a non-time slot (start_time = end_time
     * = null) and status `needs_teacher_room` so the user sees the unmet
     * weekly quota instead of having it silently drop.
     *
     * Frontend should display these in a "Pending / Unscheduled" lane with
     * crimson styling.
     */
    private function renderPlaceholderHours(
        array &$tentative, array $section, array $subj, float $hours,
        float $effMin, float $effMax, array $activeDays, array $sectionDayHours, float $maxHrsDay
    ): void {
        if ($hours <= 0.001) return;

        // Split the leftover into placeholder chunks no larger than effMax so
        // the calendar boxes are visually digestible.
        $chunks = [];
        $left   = $hours;
        while ($left > 0.001) {
            $c = $left >= $effMax ? $effMax : max($effMin, $left);
            if ($c > $left) $c = $left;
            $chunks[] = round($c, 2);
            $left = round($left - $c, 2);
        }

        foreach ($chunks as $c) {
            $tentative[] = [
                'section_id'              => $section['id'],
                'section_name'            => $section['name'] ?? null,
                'subject_id'              => $subj['id'],
                'subject_name'            => $subj['name'] ?? null,
                'is_lab'                  => ! empty($subj['is_lab']),
                'teacher_id'              => null,
                'teacher_name'            => null,
                'teacher_employment_type' => null,
                'teacher_is_regular'      => null,
                'room_id'                 => null,
                'room_name'               => null,
                'day_of_week'             => null, // unscheduled lane
                'start_time'              => null,
                'end_time'                => null,
                'hours'                   => $c,
                'status'                  => 'needs_teacher',
                'conflict_reasons'        => [
                    'Quota leftover: time grid exhausted within active days / max hours per day',
                ],
            ];
        }
    }

    /**
     * Find an available time-window on $day for a session of $hours respecting
     * the section's gap policy. Returns ['start','end'] or null.
     */
    private function findWindow(
        string $day, float $hours, int $slot, array $slots, string $block,
        array $existingSectionDay, bool $allowGaps, int $maxGapMin, ?array $break
    ): ?array {
        $needSlots = max(1, (int) ceil(($hours * 60) / $slot));
        $candidateSlots = $this->filterByBlock($slots[$day] ?? [], $block);

        $candidates = [];
        for ($i = 0; $i + $needSlots <= count($candidateSlots); $i++) {
            $window = array_slice($candidateSlots, $i, $needSlots);
            if (! $this->isContiguous($window, $slot)) continue;
            $startT = $window[0]['start'];
            $endT   = $window[count($window) - 1]['end'];
            if ($this->overlaps($existingSectionDay, $startT, $endT)) continue;

            // (5) Gap policy.
            if (! empty($existingSectionDay)) {
                if (! $allowGaps) {
                    // Must abut an existing block exactly.
                    $abuts = false;
                    foreach ($existingSectionDay as [$es, $ee]) {
                        if ($ee === $startT || $endT === $es) { $abuts = true; break; }
                    }
                    if (! $abuts) continue;
                } else {
                    if ($this->maxConsecutiveGap($existingSectionDay, $startT, $endT, $break) > $maxGapMin) continue;
                }
            }

            $candidates[] = [
                'start' => $startT,
                'end'   => $endT,
                'rank'  => $this->adjacencyRank($existingSectionDay, $startT, $endT),
            ];
        }
        if (empty($candidates)) return null;
        usort($candidates, fn($a, $b) => $a['rank'] <=> $b['rank']);
        return $candidates[0];
    }

    private function buildSlots(string $start, string $end, int $slotMin, ?array $break): array
    {
        $slots = [];
        $cur = strtotime($start);
        $endTs = strtotime($end);
        $bs = $break['start'] ?? null;
        $be = $break['end']   ?? null;
        $bsTs = $bs ? strtotime($bs) : null;
        $beTs = $be ? strtotime($be) : null;

        while ($cur + $slotMin * 60 <= $endTs) {
            $next = $cur + $slotMin * 60;
            if ($bsTs && $beTs && $cur < $beTs && $next > $bsTs) {
                $cur = $next;
                continue;
            }
            $slots[] = [
                'start' => date('H:i', $cur),
                'end'   => date('H:i', $next),
            ];
            $cur = $next;
        }
        return $slots;
    }

    private function filterByBlock(array $slots, string $block): array
    {
        if ($block === 'morning') {
            return array_values(array_filter($slots, fn($s) => $s['end'] <= '12:30'));
        }
        if ($block === 'afternoon') {
            return array_values(array_filter($slots, fn($s) => $s['start'] >= '12:00'));
        }
        return $slots;
    }

    /**
     * Order days so days that already host this section's sessions come first
     * (same-day grouping bias), with remaining days shuffled to keep variety.
     */
    private function orderDaysByGrouping(array $days, array $sectionByDay): array
    {
        $hasSessions = [];
        $empty = [];
        foreach ($days as $d) {
            if (! empty($sectionByDay[$d] ?? [])) $hasSessions[] = $d;
            else $empty[] = $d;
        }
        // shuffle within each group for diversity
        shuffle($hasSessions);
        shuffle($empty);
        return array_merge($hasSessions, $empty);
    }

    /**
     * Lower rank = better. Reward windows immediately adjacent to existing
     * sessions on the same day (compact placement); penalize gap distance.
     */
    private function adjacencyRank(array $existing, string $start, string $end): int
    {
        if (empty($existing)) return 1000; // neutral cost when day is empty
        $startTs = strtotime($start);
        $endTs   = strtotime($end);
        $best = PHP_INT_MAX;
        foreach ($existing as [$s, $e]) {
            $sTs = strtotime($s);
            $eTs = strtotime($e);
            if ($endTs <= $sTs)      $gap = ($sTs - $endTs) / 60;
            elseif ($startTs >= $eTs) $gap = ($startTs - $eTs) / 60;
            else                      $gap = 0;
            if ($gap < $best) $best = (int) $gap;
        }
        return $best;
    }

    private function isContiguous(array $window, int $slotMin): bool
    {
        for ($i = 1; $i < count($window); $i++) {
            if ($window[$i]['start'] !== $window[$i - 1]['end']) return false;
        }
        return true;
    }

    private function overlaps(array $busy, string $start, string $end): bool
    {
        foreach ($busy as [$s, $e]) {
            if (! ($end <= $s || $start >= $e)) return true;
        }
        return false;
    }

    /**
     * Build prioritized split plans for a subject. Each plan is an array of
     * session lengths (in hours) that must each be placed on a different day.
     *
     * Priority for 3-hour subjects:
     *   1. [1.5, 1.5]  (two days, balanced)
     *   2. [1, 1, 1]   (three days)
     *   3. [2, 1]      (two days, uneven)
     *   4. [3]         (single block, last resort)
     */
    private function planSplits(float $totalHrs, float $minSes, float $maxSes): array
    {
        $h = round($totalHrs, 2);
        $plans = [];

        if (abs($h - 3.0) < 0.01) {
            $plans = [[1.5, 1.5], [1.0, 1.0, 1.0], [2.0, 1.0], [3.0]];
        } elseif (abs($h - 1.5) < 0.01) {
            $plans = [[1.5]];
        } elseif (abs($h - 1.0) < 0.01) {
            $plans = [[1.0]];
        } elseif (abs($h - 2.0) < 0.01) {
            $plans = [[1.0, 1.0], [2.0]];
        } elseif (abs($h - 4.0) < 0.01) {
            $plans = [[2.0, 2.0], [1.5, 1.5, 1.0], [1.0, 1.0, 1.0, 1.0]];
        } elseif (abs($h - 4.5) < 0.01) {
            $plans = [[1.5, 1.5, 1.5], [2.0, 1.5, 1.0]];
        } elseif (abs($h - 5.0) < 0.01) {
            $plans = [[2.0, 2.0, 1.0], [1.5, 1.5, 1.0, 1.0]];
        } elseif (abs($h - 6.0) < 0.01) {
            $plans = [[2.0, 2.0, 2.0], [1.5, 1.5, 1.5, 1.5], [3.0, 3.0]];
        } else {
            // Generic: greedy split into preferred-max chunks, last chunk takes remainder
            $generic = [];
            $rem = $h;
            $cap = max($minSes, min($maxSes, 2.0));
            while ($rem > 0.0001) {
                $take = $rem >= $cap ? $cap : max($minSes, $rem);
                if ($take > $rem) $take = $rem;
                $generic[] = round($take, 2);
                $rem = round($rem - $take, 2);
            }
            $plans[] = $generic;
        }

        // Filter plans whose chunks fall outside the absolute min/max window.
        $absoluteMax = max($maxSes, 3.0);
        $valid = [];
        foreach ($plans as $p) {
            $sum = 0.0;
            $ok = true;
            foreach ($p as $x) {
                if ($x < $minSes - 0.01 || $x > $absoluteMax + 0.01) { $ok = false; break; }
                $sum += $x;
            }
            if ($ok && abs($sum - $h) < 0.01) $valid[] = $p;
        }
        return $valid ?: [[$h]];
    }

    /**
     * Lab subjects keep contiguous 2-3 hour blocks; split only if total > 3.
     */
    private function labSplits(float $totalHrs, float $minSes, float $maxSes): array
    {
        $rem = $totalHrs;
        $cap = min(max($maxSes, 3.0), 3.0);
        $out = [];
        while ($rem > 0.0001) {
            $take = $rem >= $cap ? $cap : max($minSes, $rem);
            if ($take > $rem) $take = $rem;
            $out[] = round($take, 2);
            $rem = round($rem - $take, 2);
        }
        return $out;
    }

    /**
     * Generate all combinations of $k days from $days, ordered by "spread"
     * (largest minimum gap between consecutive picks first). E.g. for k=3 of
     * Mon-Fri, returns Mon/Wed/Fri before Mon/Tue/Wed.
     *
     * @return array<int, array<int, string>>
     */
    private function pickDayCombos(array $days, int $k): array
    {
        $days = array_values($days);
        $n = count($days);
        if ($k >= $n) return [$days];
        if ($k <= 0) return [[]];

        $combos = [];
        $combine = function (int $start, array $current) use (&$combine, &$combos, $days, $n, $k) {
            if (count($current) === $k) { $combos[] = $current; return; }
            for ($i = $start; $i < $n; $i++) {
                $combine($i + 1, array_merge($current, [$days[$i]]));
            }
        };
        $combine(0, []);

        // Score by minimum index-gap between consecutive picks (higher = more spread).
        $idx = array_flip($days);
        usort($combos, function ($a, $b) use ($idx) {
            $score = function (array $combo) use ($idx) {
                if (count($combo) <= 1) return 0;
                $positions = array_map(fn($d) => $idx[$d], $combo);
                sort($positions);
                $min = PHP_INT_MAX;
                for ($i = 1; $i < count($positions); $i++) {
                    $g = $positions[$i] - $positions[$i - 1];
                    if ($g < $min) $min = $g;
                }
                return $min;
            };
            return $score($b) <=> $score($a);
        });

        return $combos;
    }

    /**
     * Largest consecutive gap (in minutes) on a day if we add [start,end] to
     * the existing busy list. Used to enforce the policy "max gap between
     * classes <= maxAllowedGap".
     */
    private function maxConsecutiveGap(array $existing, string $start, string $end, ?array $break = null): int
    {
        $blocks = $existing;
        $blocks[] = [$start, $end];
        usort($blocks, fn($a, $b) => strcmp($a[0], $b[0]));
        $bs = $break['start'] ?? null;
        $be = $break['end']   ?? null;
        $maxGap = 0;
        for ($i = 1; $i < count($blocks); $i++) {
            $g = (strtotime($blocks[$i][0]) - strtotime($blocks[$i - 1][1])) / 60;
            // Subtract lunch-break portion that falls inside this gap.
            if ($bs && $be) {
                $overlap = max(0,
                    (min(strtotime($blocks[$i][0]), strtotime($be))
                     - max(strtotime($blocks[$i - 1][1]), strtotime($bs))) / 60
                );
                $g -= max(0, $overlap);
            }
            if ($g > $maxGap) $maxGap = (int) $g;
        }
        return $maxGap;
    }

    /**
     * Build a session-length list for a single subject under the section-level
     * day-shape (e.g. shapeDays=2 means the subject must occupy exactly 2 days).
     *
     * Strategy:
     *   - Lab subjects keep 2-3 hour blocks; only fit shapes where the lab
     *     can be expressed as N blocks of 2-3 hours each (typically shape=1).
     *   - Lecture subjects: prefer the most-balanced split (e.g. 1.5+1.5),
     *     fall back to uneven (1+2) if a balanced split lies outside [min..max].
     *
     * Returns a prioritized list of plans (each plan = array<float>). The
     * caller should try plans in order and fall back to the next if a plan
     * cannot be physically placed on the section's chosen days.
     *
     * Returns array<int, array<float>>. Empty array means "this subject cannot fit this shape".
     */
    private function buildShapeSplits(float $totalHrs, int $shapeDays, float $minSes, float $maxSes): array
    {
        $shapeDays = max(1, $shapeDays);
        $totalHrs  = round($totalHrs, 2);
        $absMax    = max($maxSes, 3.0);

        // Single-day shape: one continuous block.
        if ($shapeDays === 1) {
            if ($totalHrs > $absMax + 0.001) return [];
            return [[$totalHrs]];
        }

        $plans = [];
        $seen  = [];
        $push = function (array $p) use (&$plans, &$seen, $minSes, $absMax, $totalHrs) {
            $sum = 0.0;
            foreach ($p as $x) {
                if ($x < $minSes - 0.001 || $x > $absMax + 0.001) return;
                $sum += $x;
            }
            if (abs($sum - $totalHrs) > 0.01) return;
            $key = implode(',', array_map(fn($x) => number_format($x, 2), $p));
            if (isset($seen[$key])) return;
            $seen[$key] = true;
            $plans[] = $p;
        };

        // Plan 1: balanced split (e.g. 3h/2 = 1.5+1.5).
        $even = round($totalHrs / $shapeDays, 2);
        $balanced = array_fill(0, $shapeDays, $even);
        $balanced[$shapeDays - 1] = round($balanced[$shapeDays - 1] + ($totalHrs - array_sum($balanced)), 2);
        $push($balanced);

        // Plan 2..N: uneven splits trying every integer combination of session
        // lengths in 0.5h steps that sums to totalHrs across $shapeDays sessions.
        // Sorted by deviation from balanced (smaller deviation = preferred).
        $stepHalves = 1; // 0.5h granularity
        $minHalves = (int) round($minSes * 2);
        $maxHalves = (int) round($maxSes * 2);
        $totalHalves = (int) round($totalHrs * 2);

        $combos = [];
        $gen = function (int $remaining, int $slotsLeft, array $current) use (&$gen, &$combos, $minHalves, $maxHalves) {
            if ($slotsLeft === 0) {
                if ($remaining === 0) $combos[] = $current;
                return;
            }
            $lo = $minHalves;
            $hi = min($maxHalves, $remaining - ($slotsLeft - 1) * $minHalves);
            for ($v = $lo; $v <= $hi; $v++) {
                $gen($remaining - $v, $slotsLeft - 1, array_merge($current, [$v]));
            }
        };
        $gen($totalHalves, $shapeDays, []);

        // Order each combo's "canonical" form (descending) only for dedup; keep
        // both ascending and descending placements so callers see e.g. [2,1] and [1,2].
        $byScore = [];
        foreach ($combos as $c) {
            $sortedDesc = $c;
            rsort($sortedDesc);
            $key = implode(',', $sortedDesc);
            if (isset($byScore[$key])) continue;
            // Score = sum of squared deviations from balanced (lower = more balanced)
            $dev = 0.0;
            foreach ($c as $v) $dev += ($v / 2 - $totalHrs / $shapeDays) ** 2;
            $byScore[$key] = ['combo' => $sortedDesc, 'score' => $dev];
        }
        usort($byScore, fn($a, $b) => $a['score'] <=> $b['score']);

        foreach ($byScore as $entry) {
            $hours = array_map(fn($v) => $v / 2.0, $entry['combo']);
            // Variant 1: descending (e.g. 2,1)
            $push($hours);
            // Variant 2: ascending (e.g. 1,2)
            $asc = $hours; sort($asc);
            $push($asc);
        }

        return $plans;
    }

    private function pickTeacher(array $teachers, int $subjectId, string $day, string $start, string $end, array &$teacherBusy, ?int $preferredTeacherId = null): ?array
    {
        $candidates = array_values(array_filter($teachers, fn($t) =>
            in_array($subjectId, $t['subjects'] ?? [], true)
        ));

        // If a teacher is already pinned for this subject in this section,
        // try them first so the same subject doesn't end up split across two
        // teachers within the same section.
        if ($preferredTeacherId !== null) {
            usort($candidates, function ($a, $b) use ($preferredTeacherId) {
                $ap = (($a['id'] ?? null) === $preferredTeacherId) ? 0 : 1;
                $bp = (($b['id'] ?? null) === $preferredTeacherId) ? 0 : 1;
                return $ap <=> $bp;
            });
            // Quick path: if preferred is free here, return immediately.
            foreach ($candidates as $t) {
                if (($t['id'] ?? null) !== $preferredTeacherId) break;
                if ($this->overlaps($teacherBusy[$t['id']][$day] ?? [], $start, $end)) continue;
                if (! $this->teacherAvailable($t, $day, $start, $end)) continue;
                if (! $this->respectsTeacherPreferences($t, $day, $start, $end, $teacherBusy)) continue;
                if ($this->isRegularTeacher($t)
                    && ! $this->respectsFullTimeConstraints($t, $day, $start, $end, $teacherBusy)) continue;
                return $t;
            }
        }

        // Daily-target for regular/full-time teachers: try to load them up to
        // this many hours/day before assigning the slot to a part-timer.
        $regularDailyTarget = 6.0;

        // Sort priority (per user spec):
        //   1. Part-time teachers FIRST — pack their fixed availability windows.
        //   2. Among part-timers, prefer those still below their weekly target.
        //   3. Full-time teachers fill the remaining slots.
        //   4. Among full-timers, prefer those further below weekly target,
        //      then those already on duty today and still under the daily target.
        //   5. Primary-for-subject before non-primary.
        //   6. Least total weekly hours (load balancing tie-break).
        usort($candidates, function ($a, $b) use ($subjectId, $teacherBusy, $day, $regularDailyTarget) {
            $aRegular = $this->isRegularTeacher($a) ? 1 : 0; // 0 = part-time (preferred)
            $bRegular = $this->isRegularTeacher($b) ? 1 : 0;
            if ($aRegular !== $bRegular) return $aRegular <=> $bRegular;

            $target = (float) ($this->teacherConstraints['max_hours_per_week'] ?? 0);
            $aHrs = $this->teacherTotalHours($a, $teacherBusy);
            $bHrs = $this->teacherTotalHours($b, $teacherBusy);

            // Within the same employment tier: the one further below target first.
            if ($target > 0) {
                $aRemaining = max(0, $target - $aHrs);
                $bRemaining = max(0, $target - $bHrs);
                if (abs($aRemaining - $bRemaining) > 0.001) {
                    return $bRemaining <=> $aRemaining;
                }
            }

            // Both same type: if full-time, prefer the one already on duty today
            // and below the daily target (consolidates regular workloads).
            if ($aRegular === 1) {
                $aDay = $this->teacherHoursOnDay($a, $day, $teacherBusy);
                $bDay = $this->teacherHoursOnDay($b, $day, $teacherBusy);
                $aActive = ($aDay > 0 && $aDay < $regularDailyTarget) ? 0 : 1;
                $bActive = ($bDay > 0 && $bDay < $regularDailyTarget) ? 0 : 1;
                if ($aActive !== $bActive) return $aActive <=> $bActive;
                if ($aActive === 0) {
                    if ($aDay !== $bDay) return $bDay <=> $aDay; // larger day-load first
                }
            }

            $ap = in_array($subjectId, $a['primary_subjects'] ?? [], true) ? 0 : 1;
            $bp = in_array($subjectId, $b['primary_subjects'] ?? [], true) ? 0 : 1;
            if ($ap !== $bp) return $ap <=> $bp;

            $la = $this->teacherTotalHours($a, $teacherBusy);
            $lb = $this->teacherTotalHours($b, $teacherBusy);
            return $la <=> $lb;
        });

        $sessionHours = (strtotime($end) - strtotime($start)) / 3600;

        // Per-employment-type gap rules:
        //   Regular: tolerate up to 2hr (120min) gaps between sessions on the same
        //   day (they're at school 9hrs anyway with 1hr break + 2hr prep window).
        //   Part-time: ideally zero gap; tolerate at most one ≤60min gap per week.
        $regularDayGapMax = 120; // minutes
        $partTimeWeekGapMax = 60; // single allowed gap, minutes

        // Three-pass selection. Each pass is "preferred -> permissive":
        //   Pass 1: regulars at <= 6hr/day, gap on day <= 2hr.
        //   Pass 2: regulars at > 6hr/day (overflow up to max_hours_per_day cap)
        //           OR part-timers with NO new gap and no existing weekly gaps.
        //   Pass 3: part-timers allowing one ≤1hr gap per week.
        //   Pass 4: any teacher (last-resort).
        $checkRegular = function (array $t) use ($day, $start, $end, $teacherBusy, $regularDayGapMax) {
            if (! $this->isRegularTeacher($t)) return false;
            $existingDay = $teacherBusy[$t['id']][$day] ?? [];
            if ($this->maxConsecutiveGap($existingDay, $start, $end, $this->currentBreak) > $regularDayGapMax) {
                return false;
            }
            if (! $this->respectsFullTimeMinimum($t, $day, $start, $end, $teacherBusy)) {
                return false;
            }
            return true;
        };

        $checkPartTimeNoGap = function (array $t) use ($day, $start, $end, $teacherBusy) {
            if ($this->isRegularTeacher($t)) return false;
            $existingDay = $teacherBusy[$t['id']][$day] ?? [];
            // No new gap on this day.
            if ($this->maxConsecutiveGap($existingDay, $start, $end, $this->currentBreak) > 0) return false;
            // No existing gap days elsewhere in the week.
            if ($this->teacherWeeklyGapDays($t, $teacherBusy, $day) > 0) return false;
            if (! $this->respectsPartTimeMinimum($t, $day, $start, $end, $teacherBusy)) return false;
            return true;
        };

        $checkPartTimeOneGap = function (array $t) use ($day, $start, $end, $teacherBusy, $partTimeWeekGapMax) {
            if ($this->isRegularTeacher($t)) return false;
            $existingDay = $teacherBusy[$t['id']][$day] ?? [];
            $newGap = $this->maxConsecutiveGap($existingDay, $start, $end, $this->currentBreak);
            $existingWeekGaps = $this->teacherWeeklyGapDays($t, $teacherBusy, $day);
            $totalGaps = $existingWeekGaps + ($newGap > 0 ? 1 : 0);
            if ($totalGaps > 1) return false;
            if ($newGap > $partTimeWeekGapMax) return false;
            if (! $this->respectsPartTimeMinimum($t, $day, $start, $end, $teacherBusy)) return false;
            return true;
        };

        $passes = [
            // 1. Preferred: regular within 6hr daily target, gap on day <= 2hr.
            fn(array $t) => $checkRegular($t)
                && $this->respectsFullTimeConstraints($t, $day, $start, $end, $teacherBusy)
                && ($this->teacherHoursOnDay($t, $day, $teacherBusy) + $sessionHours) <= $regularDailyTarget + 0.001,
            // 2. Part-timer with zero new gap and no existing weekly gaps.
            fn(array $t) => $checkPartTimeNoGap($t),
            // 3. Part-timer with at most one ≤60min gap per week.
            fn(array $t) => $checkPartTimeOneGap($t),
            // 4. Last-resort: any teacher (regulars must still respect hard FT caps
            //    AND the configured daily minimum).
            fn(array $t) => $this->isRegularTeacher($t)
                ? ($this->respectsFullTimeConstraints($t, $day, $start, $end, $teacherBusy)
                    && $this->respectsFullTimeMinimum($t, $day, $start, $end, $teacherBusy))
                : $this->respectsPartTimeMinimum($t, $day, $start, $end, $teacherBusy),
        ];

        foreach ($passes as $accept) {
            foreach ($candidates as $t) {
                if (! $accept($t)) continue;
                if (! $this->teacherAvailable($t, $day, $start, $end)) continue;
                if ($this->overlaps($teacherBusy[$t['id']][$day] ?? [], $start, $end)) continue;
                if (! $this->respectsTeacherPreferences($t, $day, $start, $end, $teacherBusy)) continue;
                return $t;
            }
        }
        return null;
    }

    /**
     * Count days in the week where this teacher already has a non-zero gap
     * between consecutive sessions (lunch break excluded). Optionally exclude
     * a specific day from the count (e.g. when evaluating a candidate slot
     * on that day separately).
     */
    private function teacherWeeklyGapDays(array $t, array $teacherBusy, ?string $excludeDay = null): int
    {
        $tid = $t['id'] ?? null;
        if (! $tid) return 0;
        $count = 0;
        foreach (($teacherBusy[$tid] ?? []) as $d => $blocks) {
            if ($excludeDay !== null && $d === $excludeDay) continue;
            if (count($blocks) < 2) continue;
            $sorted = $blocks;
            usort($sorted, fn($a, $b) => strcmp($a[0], $b[0]));
            $bs = $this->currentBreak['start'] ?? null;
            $be = $this->currentBreak['end']   ?? null;
            $hasGap = false;
            for ($i = 1; $i < count($sorted); $i++) {
                $g = (strtotime($sorted[$i][0]) - strtotime($sorted[$i - 1][1])) / 60;
                if ($bs && $be) {
                    $overlap = max(0,
                        (min(strtotime($sorted[$i][0]), strtotime($be))
                         - max(strtotime($sorted[$i - 1][1]), strtotime($bs))) / 60
                    );
                    $g -= max(0, $overlap);
                }
                if ($g > 0) { $hasGap = true; break; }
            }
            if ($hasGap) $count++;
        }
        return $count;
    }

    /**
     * A teacher is considered "regular" (full-time) unless their employment_type
     * explicitly contains "part" (e.g. "Part-time", "part time", "Contractual"
     * counts as regular by default since only "part" triggers the exclusion).
     */
    private function isRegularTeacher(array $t): bool
    {
        $type = strtolower(trim((string) ($t['employment_type'] ?? '')));
        if ($type === '') return true; // unknown defaults to regular
        return ! str_contains($type, 'part');
    }

    /**
     * Hard caps applied ONLY to full-time/regular teachers, sourced from the
     * Teacher Constraints UI section. Part-time teachers are unaffected here
     * (their per-teacher 'preferences' continue to govern any caps they have).
     */
    private function respectsFullTimeConstraints(array $teacher, string $day, string $start, string $end, array $teacherBusy): bool
    {
        if (! $this->isRegularTeacher($teacher)) return true;

        $tc = $this->teacherConstraints;
        $hours = (strtotime($end) - strtotime($start)) / 3600;

        $maxDay = (float) ($tc['max_hours_per_day'] ?? 0);
        if ($maxDay > 0) {
            $dayHrs = $this->teacherHoursOnDay($teacher, $day, $teacherBusy);
            if ($dayHrs + $hours > $maxDay + 0.001) return false;
        }

        $maxWeek = (float) ($tc['max_hours_per_week'] ?? 0);
        if ($maxWeek > 0) {
            $wkHrs = $this->teacherTotalHours($teacher, $teacherBusy);
            if ($wkHrs + $hours > $maxWeek + 0.001) return false;
        }

        $maxDays = (int) ($tc['work_days_per_week'] ?? 0);
        if ($maxDays > 0) {
            $tid = $teacher['id'] ?? null;
            $daysWorked = [];
            foreach (($teacherBusy[$tid] ?? []) as $d => $blocks) {
                if (! empty($blocks)) $daysWorked[$d] = true;
            }
            // If teacher isn't already on this day, scheduling here adds a new day.
            if (! isset($daysWorked[$day]) && count($daysWorked) >= $maxDays) {
                return false;
            }
        }

        return true;
    }

    /**
     * Full-time teachers should not be given a brand-new reporting day unless
     * the placement already satisfies the configured minimum teaching hours for
     * that day. If they already have classes on that day, follow-up placements
     * are allowed so the day can build up to the target.
     */
    private function respectsFullTimeMinimum(array $teacher, string $day, string $start, string $end, array $teacherBusy): bool
    {
        if (! $this->isRegularTeacher($teacher)) return true;

        $minDay = (float) ($this->teacherConstraints['min_hours_per_day'] ?? 0);
        if ($minDay <= 0) return true;

        $dayHours = $this->teacherHoursOnDay($teacher, $day, $teacherBusy);
        $sessionHours = (strtotime($end) - strtotime($start)) / 3600;

        // Already reporting today — allow follow-up sessions to build up the day.
        if ($dayHours > 0) return true;

        // Brand-new reporting day: if the placement alone meets the minimum,
        // accept. Otherwise allow optimistically only if enough teaching time
        // remains in the day after this slot to reach the minimum.
        if (($dayHours + $sessionHours) >= ($minDay - 0.001)) return true;

        $dayWindowEnd = $this->currentDayEnd ?? null;
        if ($dayWindowEnd) {
            $remainingAfter = max(0, (strtotime($dayWindowEnd) - strtotime($end)) / 3600);
            $needed = $minDay - $sessionHours;
            if ($remainingAfter + 0.001 >= $needed) return true;
        }

        return false;
    }

    /**
     * Part-time teachers should not be given a brand-new reporting day unless
     * the placement already satisfies the configured minimum hours for that day.
     * If they already have hours on that day, additional classes are allowed
     * so the day can be built up to the target.
     */
    private function respectsPartTimeMinimum(array $teacher, string $day, string $start, string $end, array $teacherBusy): bool
    {
        if ($this->isRegularTeacher($teacher)) return true;

        $minDay = (float) ($this->teacherConstraints['part_time_min_hours_per_day'] ?? 0);
        if ($minDay <= 0) return true;

        $dayHours = $this->teacherHoursOnDay($teacher, $day, $teacherBusy);
        $sessionHours = (strtotime($end) - strtotime($start)) / 3600;

        // Once the teacher is already reporting on that day, allow follow-up
        // placements so the day can grow toward or beyond the minimum.
        if ($dayHours > 0) return true;

        // A brand-new day must be worth the trip on its own.
        return ($dayHours + $sessionHours) >= ($minDay - 0.001);
    }

    private function teacherHoursOnDay(array $t, string $day, array $teacherBusy): float
    {
        $tid = $t['id'] ?? null;
        if (! $tid) return 0;
        $total = 0.0;
        foreach (($teacherBusy[$tid][$day] ?? []) as [$s, $e]) {
            $total += (strtotime($e) - strtotime($s)) / 3600;
        }
        return $total;
    }

    private function teacherTotalHours(array $t, array $teacherBusy): float
    {
        $tid = $t['id'] ?? null;
        if (! $tid) return 0;
        $total = 0.0;
        foreach (($teacherBusy[$tid] ?? []) as $day => $blocks) {
            foreach ($blocks as [$s, $e]) {
                $total += (strtotime($e) - strtotime($s)) / 3600;
            }
        }
        return $total;
    }

    private function fullTimeWeeklyTargetConflicts(array $teachers, array $teacherBusy): array
    {
        $target = (float) ($this->teacherConstraints['max_hours_per_week'] ?? 0);
        if ($target <= 0) return [];

        $conflicts = [];
        foreach ($teachers as $teacher) {
            if (! $this->isRegularTeacher($teacher)) continue;

            $hours = $this->teacherTotalHours($teacher, $teacherBusy);
            // Ignore untouched teachers; validate teachers who were actually scheduled.
            if ($hours <= 0) continue;

            if ($hours + 0.001 < $target) {
                $conflicts[] = [
                    'teacher_id' => $teacher['id'] ?? null,
                    'teacher_name' => $teacher['name'] ?? null,
                    'reason' => sprintf(
                        'Full-time teacher "%s" assigned %.2f / %.2f weekly teaching hours.',
                        $teacher['name'] ?? ('#' . ($teacher['id'] ?? '?')),
                        $hours,
                        $target
                    ),
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Strict daily-minimum enforcement: for any full-time teacher with a day
     * under min_hours_per_day, try to reassign each of that day's sessions to
     * another qualified teacher (FT first) who can take it without violating
     * caps/availability/preferences. The session's day/time/section/room are
     * preserved so this is purely a teacher-swap.
     *
     * Sessions are mutated in place; teacherBusy is rebuilt from sessions.
     */
    private function consolidateFullTimeUnderMinDays(array &$sessions, array $teachers, array &$teacherBusy): void
    {
        $minDay = (float) ($this->teacherConstraints['min_hours_per_day'] ?? 0);
        if ($minDay <= 0 || empty($sessions) || empty($teachers)) return;

        $teachersById = [];
        foreach ($teachers as $t) {
            if (! empty($t['id'])) $teachersById[$t['id']] = $t;
        }

        // Build day-hour totals per teacher.
        $totalsByTeacherDay = function (array $sess) {
            $out = [];
            foreach ($sess as $s) {
                $tid = $s['teacher_id'] ?? null;
                if (! $tid) continue;
                $d = $s['day_of_week'] ?? null;
                if (! $d) continue;
                $out[$tid][$d] = ($out[$tid][$d] ?? 0) + ((strtotime($s['end_time']) - strtotime($s['start_time'])) / 3600);
            }
            return $out;
        };

        // One reconciliation pass; could be iterated but a single pass is
        // typically sufficient and avoids ping-ponging.
        $totals = $totalsByTeacherDay($sessions);

        foreach ($totals as $tid => $byDay) {
            $teacher = $teachersById[$tid] ?? null;
            if (! $teacher || ! $this->isRegularTeacher($teacher)) continue;

            foreach ($byDay as $day => $hrs) {
                if ($hrs + 0.001 >= $minDay) continue;

                // Find this teacher's sessions for that day and try to reassign each.
                foreach ($sessions as $idx => $s) {
                    if (($s['teacher_id'] ?? null) !== $tid) continue;
                    if (($s['day_of_week'] ?? null) !== $day) continue;

                    $subjectId = $s['subject_id'] ?? null;
                    if (! $subjectId) continue;

                    $start = $s['start_time'];
                    $end   = $s['end_time'];

                    $candidates = array_values(array_filter($teachers, function ($t) use ($subjectId, $tid) {
                        if (($t['id'] ?? null) === $tid) return false;
                        return in_array($subjectId, $t['subjects'] ?? [], true);
                    }));

                    // Prefer FT teachers already reporting that day so we don't open new under-min days elsewhere.
                    usort($candidates, function ($a, $b) use ($day, $teacherBusy) {
                        $aReg = $this->isRegularTeacher($a) ? 0 : 1;
                        $bReg = $this->isRegularTeacher($b) ? 0 : 1;
                        if ($aReg !== $bReg) return $aReg <=> $bReg;
                        $aDay = ! empty($teacherBusy[$a['id']][$day] ?? []) ? 0 : 1;
                        $bDay = ! empty($teacherBusy[$b['id']][$day] ?? []) ? 0 : 1;
                        return $aDay <=> $bDay;
                    });

                    foreach ($candidates as $cand) {
                        $cid = $cand['id'] ?? null;
                        if (! $cid) continue;
                        if ($this->overlaps($teacherBusy[$cid][$day] ?? [], $start, $end)) continue;
                        if (! $this->teacherAvailable($cand, $day, $start, $end)) continue;
                        if (! $this->respectsTeacherPreferences($cand, $day, $start, $end, $teacherBusy)) continue;
                        if ($this->isRegularTeacher($cand)
                            && ! $this->respectsFullTimeConstraints($cand, $day, $start, $end, $teacherBusy)
                        ) continue;

                        // Reassign: update session + busy maps.
                        $sessions[$idx]['teacher_id']   = $cid;
                        $sessions[$idx]['teacher_name'] = $cand['name'] ?? null;
                        $sessions[$idx]['teacher_employment_type'] = $cand['employment_type'] ?? null;
                        $sessions[$idx]['teacher_is_regular'] = $this->isRegularTeacher($cand);

                        // Remove block from original teacher.
                        if (isset($teacherBusy[$tid][$day])) {
                            $teacherBusy[$tid][$day] = array_values(array_filter(
                                $teacherBusy[$tid][$day],
                                fn($b) => ! ($b[0] === $start && $b[1] === $end)
                            ));
                            if (empty($teacherBusy[$tid][$day])) unset($teacherBusy[$tid][$day]);
                        }
                        $teacherBusy[$cid][$day][] = [$start, $end];
                        break;
                    }
                }
            }
        }
    }

    private function fullTimeDailyMinimumConflicts(array $teachers, array $teacherBusy): array
    {
        $minDay = (float) ($this->teacherConstraints['min_hours_per_day'] ?? 0);
        if ($minDay <= 0) return [];

        $conflicts = [];
        foreach ($teachers as $teacher) {
            if (! $this->isRegularTeacher($teacher)) continue;
            $tid = $teacher['id'] ?? null;
            if (! $tid) continue;

            foreach (($teacherBusy[$tid] ?? []) as $day => $blocks) {
                if (empty($blocks)) continue;
                $hours = 0.0;
                foreach ($blocks as [$s, $e]) {
                    $hours += (strtotime($e) - strtotime($s)) / 3600;
                }
                if ($hours + 0.001 < $minDay) {
                    $conflicts[] = [
                        'teacher_id'   => $tid,
                        'teacher_name' => $teacher['name'] ?? null,
                        'day'          => $day,
                        'reason' => sprintf(
                            'Full-time teacher "%s" assigned only %.2f hr on %s (minimum %.2f).',
                            $teacher['name'] ?? ('#' . $tid),
                            $hours,
                            ucfirst($day),
                            $minDay
                        ),
                    ];
                }
            }
        }
        return $conflicts;
    }

    private function respectsTeacherPreferences(array $teacher, string $day, string $start, string $end, array $teacherBusy): bool
    {
        $pref = $teacher['preferences'] ?? null;
        if (! $pref) return true;

        // Preferred block
        $block = strtolower((string) ($pref['preferred_block'] ?? 'any'));
        if ($block === 'morning'   && $end   > '12:30') return false;
        if ($block === 'afternoon' && $start < '12:00') return false;

        $hours = (strtotime($end) - strtotime($start)) / 3600;

        // Max per day
        if (! empty($pref['max_hours_per_day'])) {
            $dayHrs = 0;
            foreach (($teacherBusy[$teacher['id']][$day] ?? []) as [$s, $e]) {
                $dayHrs += (strtotime($e) - strtotime($s)) / 3600;
            }
            if ($dayHrs + $hours > (float) $pref['max_hours_per_day'] + 0.001) return false;
        }
        // Max per week
        if (! empty($pref['max_hours_per_week'])) {
            $wkHrs = $this->teacherTotalHours($teacher, $teacherBusy);
            if ($wkHrs + $hours > (float) $pref['max_hours_per_week'] + 0.001) return false;
        }

        return true;
    }

    private function teacherAvailable(array $teacher, string $day, string $start, string $end): bool
    {
        $av = $teacher['availability'] ?? [];
        if (empty($av)) return true; // no constraints => available
        foreach ($av as $w) {
            if (strtolower($w['day'] ?? '') !== strtolower($day)) continue;
            if ($start >= ($w['start'] ?? '00:00') && $end <= ($w['end'] ?? '23:59')) return true;
        }
        return false;
    }

    /**
     * Pre-pick a sticky room for a whole section so every chunk lands in the
     * same room when capacity & availability allow. Strategy: among rooms
     * with capacity ≥ section size, pick the least-loaded; tie-break by
     * smallest capacity (best-fit). Falls back to any room if none fit.
     */
    private function preselectSectionRoom(array $rooms, int $size, array $roomBusy): ?int
    {
        if (empty($rooms)) return null;

        $fit = array_values(array_filter($rooms, fn($r) => (int) ($r['capacity'] ?? 0) >= $size));
        $pool = ! empty($fit) ? $fit : array_values($rooms);

        usort($pool, function ($a, $b) use ($roomBusy) {
            $aLoad = $this->roomTotalHours((int) ($a['id'] ?? 0), $roomBusy);
            $bLoad = $this->roomTotalHours((int) ($b['id'] ?? 0), $roomBusy);
            if ($aLoad !== $bLoad) return $aLoad <=> $bLoad;
            return (int) ($a['capacity'] ?? 0) <=> (int) ($b['capacity'] ?? 0);
        });

        return isset($pool[0]['id']) ? (int) $pool[0]['id'] : null;
    }

    private function pickRoom(array $rooms, int $size, string $day, string $start, string $end, array &$roomBusy, ?int $preferredRoomId = null): ?array
    {
        if (empty($rooms)) return null;

        // Two-pass selection:
        //   Pass 1: rooms whose capacity >= section size, load-balanced.
        //   Pass 2: any free room (best-effort, even if slightly under capacity).
        $byCapacity = array_values(array_filter($rooms, fn($r) => (int)($r['capacity'] ?? 0) >= $size));
        $sortByLoad = function (array $a, array $b) use ($preferredRoomId, $roomBusy) {
            if ($preferredRoomId) {
                $ap = ((int) ($a['id'] ?? 0)) === $preferredRoomId ? 0 : 1;
                $bp = ((int) ($b['id'] ?? 0)) === $preferredRoomId ? 0 : 1;
                if ($ap !== $bp) return $ap <=> $bp;
            }
            // Least-loaded first across the whole week (spreads usage).
            $aLoad = $this->roomTotalHours($a['id'] ?? null, $roomBusy);
            $bLoad = $this->roomTotalHours($b['id'] ?? null, $roomBusy);
            if ($aLoad !== $bLoad) return $aLoad <=> $bLoad;
            // Tie-break: smallest suitable capacity (best-fit).
            return (int) ($a['capacity'] ?? 0) <=> (int) ($b['capacity'] ?? 0);
        };
        usort($byCapacity, $sortByLoad);

        foreach ($byCapacity as $r) {
            if ($this->overlaps($roomBusy[$r['id']][$day] ?? [], $start, $end)) continue;
            return $r;
        }

        // Fallback: any free room (under capacity is acceptable rather than dropping session).
        $anyFree = array_values($rooms);
        usort($anyFree, $sortByLoad);
        foreach ($anyFree as $r) {
            if ($this->overlaps($roomBusy[$r['id']][$day] ?? [], $start, $end)) continue;
            return $r;
        }

        // Hard fallback: per spec "room will not be an error anymore" —
        // every session must carry a room label even if all rooms are
        // technically busy at this slot. Prefer the section's pinned room,
        // else the preferred subject room, else the first room available.
        if ($preferredRoomId !== null) {
            foreach ($rooms as $r) {
                if ((int) ($r['id'] ?? 0) === $preferredRoomId) return $r;
            }
        }
        return $rooms[0] ?? null;
    }

    private function roomTotalHours(?int $roomId, array $roomBusy): float
    {
        if (! $roomId) return 0;
        $total = 0.0;
        foreach (($roomBusy[$roomId] ?? []) as $blocks) {
            foreach ($blocks as [$s, $e]) {
                $total += (strtotime($e) - strtotime($s)) / 3600;
            }
        }
        return $total;
    }

    public function scoreSchedule(array $sessions, array $weights, array $time): float
    {
        $w = array_merge(['gap' => 1, 'compact' => 1, 'teacher' => 1, 'room' => 1], $weights);
        $score = 1000.0;

        $bySectionDay = [];
        $teacherLoad = [];
        $teacherRegularity = [];
        $roomUse = [];
        $daysPerSection = [];
        foreach ($sessions as $s) {
            if ($s['status'] === 'conflict') $score -= 50;
            $bySectionDay[$s['section_id']][$s['day_of_week']][] = $s;
            $daysPerSection[$s['section_id']][$s['day_of_week']] = true;
            if (!empty($s['teacher_id'])) {
                $teacherLoad[$s['teacher_id']] = ($teacherLoad[$s['teacher_id']] ?? 0) + ($s['hours'] ?? 1);
                $teacherRegularity[$s['teacher_id']] = (bool) ($s['teacher_is_regular'] ?? false);
            }
            if (!empty($s['room_id']))    $roomUse[$s['room_id']]       = ($roomUse[$s['room_id']]       ?? 0) + ($s['hours'] ?? 1);

            // Penalize 3-hour straight sessions (last-resort split).
            if (! empty($s['hours']) && (float) $s['hours'] >= 2.99 && empty($s['is_lab'])) {
                $score -= 20;
            }
            // Reward the preferred 1.5 + 1.5 split shape.
            if (! empty($s['hours']) && abs((float) $s['hours'] - 1.5) < 0.01) {
                $score += 4;
            }
        }

        // gap penalties + compactness
        foreach ($bySectionDay as $sec => $days) {
            foreach ($days as $list) {
                usort($list, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
                for ($i = 1; $i < count($list); $i++) {
                    $gap = (strtotime($list[$i]['start_time']) - strtotime($list[$i - 1]['end_time'])) / 60;
                    if ($gap > 0) {
                        $score -= $w['gap'] * ($gap / 30);
                        if ($gap > 60) $score -= 25; // hard penalty for >1h gaps
                    }
                }
                $score += $w['compact'] * 2;
            }
        }

        // Reward fewer days-per-section (compact week).
        foreach ($daysPerSection as $sec => $dmap) {
            $score -= $w['compact'] * (count($dmap) - 1) * 1.5;
        }

        // teacher load balance (penalize variance)
        if ($teacherLoad) {
            $avg = array_sum($teacherLoad) / count($teacherLoad);
            $var = 0;
            foreach ($teacherLoad as $h) $var += ($h - $avg) ** 2;
            $score -= $w['teacher'] * sqrt($var);
        }

        // Full-time teachers should hit the configured weekly total strictly.
        $weeklyTarget = (float) ($this->teacherConstraints['max_hours_per_week'] ?? 0);
        if ($weeklyTarget > 0) {
            foreach ($teacherLoad as $teacherId => $hours) {
                if (! ($teacherRegularity[$teacherId] ?? false)) continue;
                $deviation = abs($weeklyTarget - $hours);
                if ($deviation > 0.001) {
                    $score -= 200 * $deviation;
                }
            }
        }

        // room efficiency reward
        $score += $w['room'] * count($roomUse);

        return round($score, 2);
    }
}
