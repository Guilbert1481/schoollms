<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceSetting;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\StatementOfAccount;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Finance\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Finance → Student Ledgers.
 *
 * Every student has an individual running ledger (charges vs. payments). This
 * controller lists balances across students and drills into one student's full
 * account: ledger entries, invoices, and generated Statements of Account.
 */
class LedgerController extends Controller
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $ledgerAgg = DB::table('ledger_entries')
            ->select('student_id', DB::raw('SUM(debit) as charges'), DB::raw('SUM(credit) as credits'), DB::raw('MAX(entry_date) as last_date'))
            ->where('school_id', $schoolId)
            ->groupBy('student_id');

        $records = DB::table('users as u')
            ->leftJoin('students as s', 's.user_id', '=', 'u.id')
            ->leftJoinSub($ledgerAgg, 'le', 'le.student_id', '=', 'u.id')
            ->where('u.school_id', $schoolId)
            ->where('u.role', 'student')
            ->orderBy('u.last_name')
            ->orderBy('u.first_name')
            ->get([
                'u.id as user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                's.student_number',
                DB::raw('COALESCE(le.charges, 0) as charges'),
                DB::raw('COALESCE(le.credits, 0) as credits'),
                'le.last_date',
            ]);

        $totalOutstanding = 0.0;
        $studentsWithBalance = 0;

        $rows = $records->map(function ($r) use (&$totalOutstanding, &$studentsWithBalance) {
            $balance = round((float) $r->charges - (float) $r->credits, 2);
            if ($balance > 0) {
                $totalOutstanding += $balance;
                $studentsWithBalance++;
            }

            return (object) [
                'id'             => $r->user_id,
                'name'           => trim($r->first_name.' '.$r->last_name) ?: '—',
                'student_number' => $r->student_number ?: '—',
                'charges_label'  => 'PHP '.number_format((float) $r->charges, 2),
                'paid_label'     => 'PHP '.number_format((float) $r->credits, 2),
                'balance'        => $this->balancePill($balance),
                'last_activity'  => $r->last_date ? \Carbon\Carbon::parse($r->last_date)->format('M d, Y') : '—',
            ];
        });

        return view('finance.ledger.index', [
            'rows'                => $rows,
            'columns'             => $this->columns(),
            'actions'             => $this->actions(),
            'totalOutstanding'    => $totalOutstanding,
            'studentsWithBalance' => $studentsWithBalance,
            'studentCount'        => $records->count(),
        ]);
    }

    public function show(User $student)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless((int) $student->school_id === $schoolId && $student->role === 'student', 404);

        $profile = Student::query()->where('school_id', $schoolId)->where('user_id', $student->id)->first();

        $entries = LedgerEntry::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->get();

        $invoices = Invoice::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        $statements = StatementOfAccount::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        // Enrollments that could still be invoiced (drives "Generate Invoice").
        $enrollments = collect();
        if ($profile) {
            $invoicedEnrollmentIds = $invoices->pluck('student_enrollment_id')->filter()->all();
            $enrollments = StudentEnrollment::query()
                ->where('school_id', $schoolId)
                ->where('student_id', $profile->id)
                ->with(['term:id,name', 'program:id,code,name', 'academicYear:id,name'])
                ->orderByDesc('id')
                ->get()
                ->map(function (StudentEnrollment $e) use ($invoicedEnrollmentIds) {
                    $e->has_invoice = in_array($e->id, $invoicedEnrollmentIds, true);

                    return $e;
                });
        }

        $setting = FinanceSetting::forSchool($schoolId);

        return view('finance.ledger.show', [
            'student'     => $student,
            'profile'     => $profile,
            'entries'     => $entries,
            'invoices'    => $invoices,
            'statements'  => $statements,
            'enrollments' => $enrollments,
            'balance'     => $this->ledger->currentBalance($schoolId, (int) $student->id),
            'setting'     => $setting,
        ]);
    }

    public function adjust(Request $request, User $student)
    {
        $schoolId = (int) auth()->user()->school_id;
        abort_unless((int) $student->school_id === $schoolId && $student->role === 'student', 404);

        $data = $request->validate([
            'direction'   => ['required', 'in:debit,credit'],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'description' => ['required', 'string', 'max:191'],
        ]);

        $amount = round((float) $data['amount'], 2);
        $this->ledger->postAdjustment(
            schoolId: $schoolId,
            studentId: (int) $student->id,
            debit: $data['direction'] === 'debit' ? $amount : 0,
            credit: $data['direction'] === 'credit' ? $amount : 0,
            description: $data['description'],
            actorId: (int) auth()->id(),
        );

        return back()->with('success', 'Ledger adjustment posted.');
    }

    protected function balancePill(float $balance): string
    {
        if ($balance > 0.005) {
            return '<span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">PHP '.number_format($balance, 2).' due</span>';
        }
        if ($balance < -0.005) {
            return '<span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-700">PHP '.number_format(abs($balance), 2).' credit</span>';
        }

        return '<span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700">Settled</span>';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Student', 'width' => '240px'],
            ['key' => 'student_number', 'label' => 'Student No.', 'width' => '150px'],
            ['key' => 'charges_label', 'label' => 'Total Charges', 'width' => '150px'],
            ['key' => 'paid_label', 'label' => 'Total Paid', 'width' => '150px'],
            ['key' => 'balance', 'label' => 'Balance', 'width' => '170px', 'raw' => true],
            ['key' => 'last_activity', 'label' => 'Last Activity', 'width' => '140px'],
        ];
    }

    protected function actions(): array
    {
        return [
            ['type' => 'link', 'label' => 'Open Ledger', 'route' => 'finance.ledger.show', 'class' => 'bg-indigo-600 text-white hover:bg-indigo-700'],
        ];
    }
}
