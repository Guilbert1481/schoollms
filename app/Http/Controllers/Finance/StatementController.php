<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use App\Models\LedgerEntry;
use App\Models\PenaltyRule;
use App\Models\StatementOfAccount;
use App\Models\Term;
use App\Models\User;
use App\Services\Finance\StatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Finance → Statements of Account. Lists generated SOAs, supports manual
 * (single-student) and batch (whole-school, current period) generation, and
 * renders the printable PDF. Auto-generation on the configured cadence is
 * handled by the finance:generate-soas scheduled command.
 */
class StatementController extends Controller
{
    public function __construct(private readonly StatementService $statements)
    {
    }

    public function index(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $soas = StatementOfAccount::query()
            ->where('school_id', $schoolId)
            ->with('student:id,first_name,last_name')
            ->orderByDesc('id')
            ->get();

        // Active penalty rules drive the computed late penalty per statement.
        $penaltyRules = PenaltyRule::query()
            ->where('school_id', $schoolId)->where('is_active', true)
            ->orderBy('grace_days')
            ->get(['basis', 'amount', 'grace_days']);

        $rows = $soas->map(function (StatementOfAccount $s) use ($penaltyRules) {
            $principal   = round((float) $s->closing_balance, 2);
            $due         = $s->due_date ? Carbon::parse($s->due_date)->startOfDay() : null;
            $daysOverdue = ($due && $principal > 0.005 && $due->isPast()) ? (int) $due->diffInDays(now()->startOfDay()) : 0;
            $penalty     = $this->computePenalty($principal, $daysOverdue, $penaltyRules);
            $balance     = round($principal + $penalty, 2);

            return (object) [
                'id'           => $s->id,
                'soa_number'   => $s->soa_number,
                'name'         => $s->student ? trim($s->student->first_name.' '.$s->student->last_name) : '—',
                'duration'     => $this->durationLabel($s),
                'due_date'     => $due ? $due->format('M d, Y') : '—',
                'description'  => $s->period_label ?: '—',
                'days_overdue' => $daysOverdue > 0 ? $daysOverdue.' day'.($daysOverdue === 1 ? '' : 's') : '—',
                'principal'    => $this->peso($principal),
                'penalty'      => $this->peso($penalty),
                'balance'      => $this->balanceCell($balance),
            ];
        });

        $setting = FinanceSetting::forSchool($schoolId);

        return view('finance.statements.index', [
            'rows'    => $rows,
            'columns' => $this->columns(),
            'actions' => $this->actions(),
            'setting' => $setting,
        ]);
    }

    public function show(StatementOfAccount $statement)
    {
        $this->authorizeSchool($statement);
        $statement->load(['student', 'school']);

        return view('finance.statements.show', compact('statement'));
    }

    /** Manually generate a SOA for one student. */
    public function generate(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $data = $request->validate([
            'student_id'   => ['required', 'integer'],
            'frequency'    => ['nullable', 'in:'.implode(',', array_keys(FinanceSetting::FREQUENCIES))],
            'period_start' => ['nullable', 'date'],
            'period_end'   => ['nullable', 'date', 'after_or_equal:period_start'],
            'force'        => ['nullable', 'boolean'],
        ]);

        $student = User::query()
            ->where('school_id', $schoolId)
            ->where('role', 'student')
            ->findOrFail((int) $data['student_id']);

        $setting   = FinanceSetting::forSchool($schoolId);
        $frequency = $data['frequency'] ?? $setting->soa_frequency;

        [$start, $end, $label] = $this->resolveWindow(
            $schoolId,
            (int) $student->id,
            $frequency,
            $data['period_start'] ?? null,
            $data['period_end'] ?? null,
        );

        $soa = $this->statements->generate($schoolId, (int) $student->id, $start, $end, [
            'frequency'    => $frequency,
            'period_label' => $label,
            'due_days'     => (int) $setting->invoice_due_days,
            'generated_by' => (int) auth()->id(),
            'force'        => (bool) ($data['force'] ?? false),
        ]);

        if (! $soa) {
            return back()->with('error', 'No ledger activity for that period — nothing to generate.');
        }

        return back()->with('success', "Statement {$soa->soa_number} generated.");
    }

    /** Batch-generate SOAs for the current period across all students. */
    public function runBatch(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;
        $setting  = FinanceSetting::forSchool($schoolId);
        $frequency = $setting->soa_frequency;

        if ($frequency === 'on_demand') {
            return back()->with('error', 'SOA frequency is set to On Demand. Set a recurring frequency in Finance Settings, or generate per student.');
        }

        $window = $this->statements->periodForFrequency($frequency, Carbon::now());

        // per_term has no calendar window — use the current / most-recent term.
        if (! $window && $frequency === 'per_term') {
            $term = Term::query()
                ->where('school_id', $schoolId)
                ->whereNotNull('start_date')
                ->whereNotNull('end_date')
                ->orderByDesc('is_current')
                ->orderByDesc('end_date')
                ->first();

            if ($term) {
                $window = [
                    'start'            => Carbon::parse($term->start_date),
                    'end'              => Carbon::parse($term->end_date),
                    'label'            => $term->name,
                    'term_id'          => $term->id,
                    'academic_year_id' => $term->academic_year_id,
                ];
            }
        }

        if (! $window) {
            $window = [
                'start' => Carbon::now()->startOfMonth(),
                'end'   => Carbon::now(),
                'label' => Carbon::now()->format('F Y'),
            ];
        }

        $studentIds = LedgerEntry::query()
            ->where('school_id', $schoolId)
            ->distinct()
            ->pluck('student_id');

        $created = 0;
        foreach ($studentIds as $studentId) {
            $soa = $this->statements->generate($schoolId, (int) $studentId, $window['start'], $window['end'], [
                'frequency'        => $frequency,
                'period_label'     => $window['label'],
                'term_id'          => $window['term_id'] ?? null,
                'academic_year_id' => $window['academic_year_id'] ?? null,
                'due_days'         => (int) $setting->invoice_due_days,
                'generated_by'     => (int) auth()->id(),
            ]);
            if ($soa) {
                $created++;
            }
        }

        $setting->update(['last_soa_run_at' => Carbon::now()]);

        return back()->with('success', "Generated {$created} statement(s) for {$window['label']}.");
    }

    public function downloadPdf(StatementOfAccount $statement)
    {
        $this->authorizeSchool($statement);
        $statement->load(['student', 'school']);

        $pdf = Pdf::loadView('finance.pdf.statement_of_account', [
            'statement' => $statement,
            'school'    => $statement->school,
        ])->setPaper('a4');

        return $pdf->download('SOA-'.$statement->soa_number.'.pdf');
    }

    /**
     * Decide the [start, end, label] for a manual generation: explicit dates
     * win; else the frequency's current window; else everything since the
     * student's first ledger entry up to today.
     */
    protected function resolveWindow(int $schoolId, int $studentId, string $frequency, ?string $start, ?string $end): array
    {
        if ($start && $end) {
            $s = Carbon::parse($start);
            $e = Carbon::parse($end);

            return [$s, $e, $s->format('M d').' – '.$e->format('M d, Y')];
        }

        $window = $this->statements->periodForFrequency($frequency, Carbon::now());
        if ($window) {
            return [$window['start'], $window['end'], $window['label']];
        }

        $first = LedgerEntry::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->min('entry_date');

        $s = $first ? Carbon::parse($first) : Carbon::now()->startOfMonth();
        $e = Carbon::now();

        return [$s, $e, 'As of '.$e->format('M d, Y')];
    }

    protected function authorizeSchool(StatementOfAccount $statement): void
    {
        abort_unless((int) $statement->school_id === (int) auth()->user()->school_id, 404);
    }

    protected function balancePill(float $balance): string
    {
        if ($balance > 0.005) {
            return '<span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">PHP '.number_format($balance, 2).'</span>';
        }
        if ($balance < -0.005) {
            return '<span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-700">PHP '.number_format(abs($balance), 2).' cr</span>';
        }

        return '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">PHP 0.00</span>';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'soa_number',   'label' => 'SOA #',        'width' => '150px'],
            ['key' => 'name',         'label' => 'Name',         'width' => '180px'],
            ['key' => 'duration',     'label' => 'Duration',     'width' => '160px'],
            ['key' => 'due_date',     'label' => 'Due Date',     'width' => '120px'],
            ['key' => 'description',  'label' => 'Description',  'width' => '180px'],
            ['key' => 'days_overdue', 'label' => 'Days Overdue', 'width' => '120px'],
            ['key' => 'principal',    'label' => 'Principal',    'width' => '130px'],
            ['key' => 'penalty',      'label' => 'Penalty',      'width' => '120px'],
            ['key' => 'balance',      'label' => 'Balance',      'width' => '140px', 'raw' => true],
        ];
    }

    /** ₱ amount. */
    protected function peso(float $v): string
    {
        return '₱'.number_format($v, 2);
    }

    /** The statement's billing window — date range, or its period label. */
    protected function durationLabel(StatementOfAccount $s): string
    {
        if ($s->period_start && $s->period_end) {
            return Carbon::parse($s->period_start)->format('M d').' – '.Carbon::parse($s->period_end)->format('M d, Y');
        }

        return $s->period_label ?: '—';
    }

    /**
     * Late penalty for a statement, from the school's active Penalty Rules:
     * the first rule whose grace period has lapsed (fixed amount or % of the
     * principal). Returns 0 when nothing is overdue or no rules are configured.
     */
    protected function computePenalty(float $principal, int $daysOverdue, $rules): float
    {
        if ($principal <= 0.005 || $daysOverdue <= 0 || $rules->isEmpty()) {
            return 0.0;
        }

        foreach ($rules as $rule) {
            if ($daysOverdue <= (int) $rule->grace_days) {
                continue;
            }

            return $rule->basis === 'percentage'
                ? round($principal * (float) $rule->amount / 100, 2)
                : round((float) $rule->amount, 2);
        }

        return 0.0;
    }

    /** Balance cell — coloured amount (rose = owes, emerald = settled). */
    protected function balanceCell(float $balance): string
    {
        if ($balance > 0.005) {
            return '<span class="font-bold text-rose-600">'.$this->peso($balance).'</span>';
        }
        if ($balance < -0.005) {
            return '<span class="font-bold text-sky-600">'.$this->peso(abs($balance)).' cr</span>';
        }

        return '<span class="font-bold text-emerald-600">'.$this->peso(0).'</span>';
    }

    protected function actions(): array
    {
        return [
            ['type' => 'link', 'label' => 'View', 'route' => 'finance.statements.show', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
        ];
    }
}
