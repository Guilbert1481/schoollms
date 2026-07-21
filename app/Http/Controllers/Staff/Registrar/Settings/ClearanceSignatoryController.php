<?php

namespace App\Http\Controllers\Staff\Registrar\Settings;

use App\Http\Controllers\Controller;
use App\Models\ClearanceSignatory;
use App\Services\Clearance\ClearanceBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registrar Settings → Clearance Signatories: the per-school list of offices
 * that must sign a student's clearance. Seeded with the defaults (Finance /
 * Cashier, Registrar, Guidance, Librarian, Subject Teachers); the registrar
 * can add, edit, and delete rows, and scope each to basic ed / higher ed.
 * Existing clearances are untouched — items snapshot their labels.
 */
class ClearanceSignatoryController extends Controller
{
    public function index(ClearanceBuilder $builder)
    {
        $builder->seedDefaults((int) auth()->user()->school_id);

        return view('registrar.settings.clearance_signatories', [
            'signatories' => ClearanceSignatory::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        ClearanceSignatory::create($this->validated($request) + [
            'school_id' => auth()->user()->school_id,
            'sort_order' => (int) (ClearanceSignatory::query()->max('sort_order')) + 1,
        ]);

        return back()->with('success', 'Signatory added.');
    }

    public function update(Request $request, ClearanceSignatory $signatory)
    {
        $this->guardSchool($signatory->school_id);

        $signatory->update($this->validated($request));

        return back()->with('success', 'Signatory updated.');
    }

    public function destroy(ClearanceSignatory $signatory)
    {
        $this->guardSchool($signatory->school_id);

        $signatory->delete();

        return back()->with('success', 'Signatory removed. Existing clearances keep their rows.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in([ClearanceSignatory::TYPE_DEPARTMENT, ClearanceSignatory::TYPE_SUBJECT_TEACHERS])],
            'applies_to' => ['required', Rule::in([ClearanceSignatory::APPLIES_BASIC, ClearanceSignatory::APPLIES_HIGHER, ClearanceSignatory::APPLIES_BOTH])],
        ]);
    }

    private function guardSchool(int $schoolId): void
    {
        abort_unless((int) $schoolId === (int) auth()->user()->school_id || auth()->user()->isSuperadmin(), 404);
    }
}
