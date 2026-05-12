<?php

namespace App\Http\Controllers\School\Settings;

use App\Http\Controllers\Controller;
use App\Models\SubjectOffering;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubjectOfferingController extends Controller
{
    public function store(Request $request)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $validated = $request->validate([
            'term_id'          => ['required', 'exists:terms,id'],
            'subject_id'       => ['required', 'exists:subjects,id'],
            'program_id'       => ['nullable', 'exists:programs,id'],
            'year_level'       => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_open'          => ['nullable', 'boolean'],
            'is_for_irregular' => ['nullable', 'boolean'],
        ]);

        // Multi-tenant guard
        $term = Term::where('id', $validated['term_id'])
            ->where('school_id', $schoolId)
            ->firstOrFail();

        // Duplicate prevention (matches the unique index)
        $exists = SubjectOffering::where([
            'term_id'    => $validated['term_id'],
            'subject_id' => $validated['subject_id'],
            'program_id' => $validated['program_id'] ?? null,
            'year_level' => $validated['year_level'] ?? null,
        ])->exists();

        if ($exists) {
            return back()->with('error', 'This subject is already offered for the selected term/program/year level.');
        }

        $subject = Subject::findOrFail($validated['subject_id']);

        SubjectOffering::create([
            'term_id'          => $validated['term_id'],
            'subject_id'       => $validated['subject_id'],
            'program_id'       => $validated['program_id'] ?? null,
            'year_level'       => $validated['year_level'] ?? null,
            'offering_code'    => $this->generateOfferingCode($subject->code, $term->id),
            'delivery_mode'    => 'pure_online',
            'status'           => 'draft',
            'is_open'          => (bool) $request->input('is_open', true),
            'is_for_irregular' => (bool) $request->input('is_for_irregular', true),
        ]);

        return back()->with('success', 'Subject offering added.');
    }

    public function destroy($id)
    {
        $schoolId = auth()->user()?->school_id;
        abort_unless($schoolId, 404);

        $offering = SubjectOffering::with('term')->findOrFail($id);
        abort_unless($offering->term && $offering->term->school_id === $schoolId, 403);

        $offering->delete();

        return back()->with('success', 'Subject offering removed.');
    }

    private function generateOfferingCode(?string $subjectCode, int $termId): string
    {
        $base = strtoupper(($subjectCode ?: 'SUBJ') . '-T' . $termId);
        $code = $base;
        $i    = 1;
        while (SubjectOffering::withTrashed()->where('offering_code', $code)->exists()) {
            $code = $base . '-' . Str::upper(Str::random(3)) . $i++;
        }
        return $code;
    }
}
