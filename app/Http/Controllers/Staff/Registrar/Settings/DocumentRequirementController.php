<?php

namespace App\Http\Controllers\Staff\Registrar\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequirement;
use App\Support\EducationLevels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Registrar → Settings → Documents.
 * CRUD for the per-student-type document requirements shown on the enrollment
 * form (transferee / returnee / shifter uploads). Table mirrors the finance
 * pages: education-level tabs on top, reusable <x-table.table> below.
 */
class DocumentRequirementController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = (int) auth()->user()->school_id;

        $levels = DB::table('education_nodes')
            ->whereNull('parent_id')
            ->where('is_offered', 1)
            ->where('is_active', 1)
            ->orderBy('order_index')
            ->get(['id', 'name']);

        $levelParam    = $request->query('level');
        $showAll       = $levelParam === null || $levelParam === '' || strtolower((string) $levelParam) === 'all';
        $activeLevelId = $showAll ? 0 : (int) $levelParam;

        $activeLevel    = $levels->firstWhere('id', $activeLevelId);
        $singleLevel    = $levels->count() === 1 ? $levels->first() : null;
        $effectiveLevel = $showAll ? $singleLevel : $activeLevel;
        $activeLevelIsBasic = $effectiveLevel
            && str_contains(strtolower((string) $effectiveLevel->name), 'basic');

        $rootNameById = $levels->pluck('name', 'id')->all();
        $basicRootIds = $levels
            ->filter(fn ($l) => str_contains(strtolower((string) $l->name), 'basic'))
            ->pluck('id')->map(fn ($v) => (int) $v)->all();

        $requirements = DocumentRequirement::query()
            ->where('school_id', $schoolId)
            ->when(! $showAll, fn ($q) => $q->where('education_node_id', $activeLevelId))
            ->orderBy('student_type')->orderBy('year_level')
            ->get();

        $programNames = DB::table('programs')
            ->where('school_id', $schoolId)
            ->pluck('name', 'id')->all();
        $programCodes = DB::table('programs')
            ->where('school_id', $schoolId)
            ->pluck('code', 'id')->all();

        $rows = $requirements->map(function (DocumentRequirement $r) use ($rootNameById, $basicRootIds, $programCodes, $programNames) {
            $isBasic = $r->education_node_id && in_array((int) $r->education_node_id, $basicRootIds, true);
            $year    = $r->year_level
                ? (($isBasic ? 'Grade ' : 'Year ').$r->year_level)
                : 'Any';
            $docs = array_values(array_filter((array) $r->documents));

            return (object) [
                'id'           => $r->id,
                'student_type' => DocumentRequirement::STUDENT_TYPES[$r->student_type] ?? ucfirst($r->student_type),
                'level'        => $r->education_node_id ? ($rootNameById[$r->education_node_id] ?? '—') : 'Any',
                'program'      => $r->program_id
                    ? ($programCodes[$r->program_id] ?: ($programNames[$r->program_id] ?? '—'))
                    : 'Any',
                'documents'    => $this->documentsCell($docs),
                'year_grade'   => $year,
                'actions'      => $this->actionsCell($r),
            ];
        })->values();

        // Column set mirrors the finance tables: Level only on All Levels,
        // Program only on higher-ed tabs (Basic Ed has no programs).
        $columns = [['key' => 'student_type', 'label' => 'Student Type', 'width' => '140px']];
        if ($showAll) {
            $columns[] = ['key' => 'level', 'label' => 'Level', 'width' => '170px'];
        }
        if (! $activeLevelIsBasic) {
            $columns[] = ['key' => 'program', 'label' => 'Program', 'width' => '140px'];
        }
        $columns[] = ['key' => 'year_grade', 'label' => 'Year/Grade', 'width' => '110px'];
        $columns[] = ['key' => 'documents',  'label' => 'Documents',  'width' => '320px', 'raw' => true];
        $columns[] = ['key' => 'actions',    'label' => 'Actions',    'width' => '140px', 'raw' => true];

        // Options for the Add/Edit modal.
        $programsByRoot = [];
        $nodeRoot = EducationLevels::nodeRootMap();
        foreach (DB::table('programs')->where('school_id', $schoolId)->orderBy('code')->get(['id', 'code', 'name', 'education_node_id']) as $p) {
            $rootId = (int) ($nodeRoot[$p->education_node_id ?? null] ?? 0);
            if ($rootId) {
                $programsByRoot[$rootId][] = ['id' => (int) $p->id, 'label' => $p->code ?: $p->name];
            }
        }

        $yearOptionsByRoot = [];
        foreach ($levels as $l) {
            $isBasic = in_array((int) $l->id, $basicRootIds, true);
            $opts    = $isBasic
                ? EducationLevels::basicGradeOptions()
                : (EducationLevels::yearLevelOptions((int) $l->id) ?: [1 => 'Year 1', 2 => 'Year 2', 3 => 'Year 3', 4 => 'Year 4']);
            $yearOptionsByRoot[(int) $l->id] = collect($opts)
                ->map(fn ($label, $val) => ['value' => (string) $val, 'label' => $label])
                ->values()->all();
        }

        return view('registrar.settings.documents', [
            'levels'            => $levels,
            'activeLevelId'     => $activeLevelId,
            'showAll'           => $showAll,
            'rows'              => $rows,
            'columns'           => $columns,
            'studentTypes'      => DocumentRequirement::STUDENT_TYPES,
            'programsByRoot'    => $programsByRoot,
            'yearOptionsByRoot' => $yearOptionsByRoot,
            'basicRootIds'      => $basicRootIds,
            'requirementsJson'  => $requirements->map(fn ($r) => [
                'id'                => $r->id,
                'student_type'      => $r->student_type,
                'education_node_id' => $r->education_node_id,
                'program_id'        => $r->program_id,
                'year_level'        => $r->year_level,
                'documents'         => array_values(array_filter((array) $r->documents)),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['school_id'] = (int) auth()->user()->school_id;

        DocumentRequirement::create($data);

        return back()->with('success', 'Document requirement added.');
    }

    public function update(Request $request, DocumentRequirement $requirement)
    {
        abort_unless((int) $requirement->school_id === (int) auth()->user()->school_id, 404);

        $requirement->update($this->validated($request));

        return back()->with('success', 'Document requirement updated.');
    }

    public function destroy(DocumentRequirement $requirement)
    {
        abort_unless((int) $requirement->school_id === (int) auth()->user()->school_id, 404);

        $requirement->delete();

        return back()->with('success', 'Document requirement deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'student_type'      => ['required', 'in:'.implode(',', array_keys(DocumentRequirement::STUDENT_TYPES))],
            'education_node_id' => ['nullable', 'integer', 'exists:education_nodes,id'],
            'program_id'        => ['nullable', 'integer', 'exists:programs,id'],
            'year_level'        => ['nullable', 'integer', 'min:1', 'max:12'],
            'documents'         => ['required', 'array', 'min:1'],
            'documents.*'       => ['required', 'string', 'max:150'],
        ]);

        $data['documents'] = array_values(array_unique(array_filter(array_map('trim', $data['documents']))));
        abort_if(empty($data['documents']), 422, 'At least one document is required.');

        return $data;
    }

    private function documentsCell(array $docs): string
    {
        if (empty($docs)) {
            return '<span class="text-slate-400">—</span>';
        }

        $badges = array_map(
            fn ($d) => '<span class="mb-1 mr-1 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">'.e($d).'</span>',
            $docs
        );

        return implode('', $badges);
    }

    private function actionsCell(DocumentRequirement $r): string
    {
        $id = (int) $r->id;

        return '<div class="flex items-center gap-2">'
            .'<button type="button" onclick="viewRequirement('.$id.')" class="text-slate-500 hover:text-indigo-600" title="View"><i data-lucide="eye" class="h-4 w-4"></i></button>'
            .'<button type="button" onclick="editRequirement('.$id.')" class="text-slate-500 hover:text-indigo-600" title="Edit"><i data-lucide="pencil" class="h-4 w-4"></i></button>'
            .'<button type="button" onclick="deleteRequirement('.$id.')" class="text-slate-500 hover:text-rose-600" title="Delete"><i data-lucide="trash-2" class="h-4 w-4"></i></button>'
            .'</div>';
    }
}
