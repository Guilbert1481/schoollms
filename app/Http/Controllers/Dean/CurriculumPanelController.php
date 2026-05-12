<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Base\BaseCrudController;
use Illuminate\Http\Request;

class CurriculumPanelController extends BaseCrudController
{
    /* ============================================================
     | Tab pages
     |============================================================*/

    public function indexCurricula()
    {
        $data = $this->loadTableData('curriculums');

        return view('dean.curricula-panel.curricula', $data);
    }

    public function indexSubjects()
    {
        $data = $this->loadTableData('subjects');

        return view('dean.curricula-panel.subjects', $data);
    }

    public function indexPrograms(Request $request)
    {
        $schoolId  = auth()->user()->school_id;
        $programId = $request->integer('program_id') ?: null;

        $programs = \Illuminate\Support\Facades\DB::table('programs')
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        // Resolve a default selection
        if (! $programId && $programs->isNotEmpty()) {
            $programId = $programs->first()->id;
        }

        $rows = collect();
        if ($programId) {
            $query = \Illuminate\Support\Facades\DB::table('program_subjects')
                ->join('subjects', 'subjects.id', '=', 'program_subjects.subject_id')
                ->where('program_subjects.program_id', $programId);

            $status = $request->query('status', 'all');
            if ($status === 'active') {
                $query->where('program_subjects.is_active', 1);
            } elseif ($status === 'inactive') {
                $query->where('program_subjects.is_active', 0);
            }

            $rows = $query
                ->orderBy('program_subjects.year_level')
                ->orderBy('program_subjects.semester_number')
                ->orderBy('subjects.name')
                ->get([
                    'program_subjects.id as id',
                    'subjects.id as subject_id',
                    'subjects.name',
                    'subjects.code',
                    'subjects.units',
                    'program_subjects.is_active',
                    'program_subjects.year_level',
                    'program_subjects.semester_number',
                ]);

            $semesterLabels = [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => 'Summer'];
            $rows = $rows->map(function ($r) use ($semesterLabels) {
                $r->semester_label  = $semesterLabels[(int) $r->semester_number] ?? $r->semester_number;
                $r->is_active_label = ((int) $r->is_active === 1) ? 'Active' : 'Inactive';
                return $r;
            });
        }

        $columns = [
            ['key' => 'name',            'label' => 'Subject'],
            ['key' => 'code',            'label' => 'Code'],
            ['key' => 'units',           'label' => 'Units'],
            ['key' => 'year_level',      'label' => 'Year'],
            ['key' => 'semester_label',  'label' => 'Semester'],
            [
                'key'    => 'is_active_label',
                'label'  => 'Status',
                'filter' => [
                    'type'    => 'dropdown',
                    'param'   => 'status',
                    'default' => 'all',
                    'options' => [
                        'all'      => 'All',
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                    ],
                ],
            ],
        ];

        // Subjects that are not yet attached to the selected program
        $availableSubjects = collect();
        if ($programId) {
            $attachedIds = $rows->pluck('subject_id');
            $availableSubjects = \Illuminate\Support\Facades\DB::table('subjects')
                ->where('school_id', $schoolId)
                ->whereNotIn('id', $attachedIds)
                ->orderBy('name')
                ->get(['id', 'code', 'name']);
        }

        return view('dean.curricula-panel.programs', [
            'programs'          => $programs,
            'programId'         => $programId,
            'data'              => $rows,
            'columns'           => $columns,
            'availableSubjects' => $availableSubjects,
        ]);
    }

    /* ============================================================
     | CRUD actions (use BaseCrudController helpers)
     |============================================================*/

    public function storeCurriculum(Request $request)
    {
        return $this->storeRecord('curriculums', $request);
    }

    public function updateCurriculum(Request $request, $id)
    {
        $request->merge(['table' => 'curriculums', 'id' => $id]);
        return $this->updateRecord($request);
    }

    public function destroyCurriculum($id)
    {
        return $this->deleteRecord('curriculums', $id);
    }

    public function storeSubject(Request $request)
    {
        return $this->storeRecord('subjects', $request);
    }

    public function updateSubject(Request $request, $id)
    {
        $request->merge(['table' => 'subjects', 'id' => $id]);
        return $this->updateRecord($request);
    }

    public function destroySubject($id)
    {
        return $this->deleteRecord('subjects', $id);
    }

    /* ============================================================
     | Program ↔ Subject pivot management
     |============================================================*/

    public function attachProgramSubject(Request $request)
    {
        $data = $request->validate([
            'program_id'      => 'required|integer|exists:programs,id',
            'subject_ids'     => 'required|array|min:1',
            'subject_ids.*'   => 'integer|exists:subjects,id',
            'year_level'      => 'required|integer|min:1|max:6',
            'semester_number' => 'required|integer|min:1|max:5',
        ]);

        $count = 0;
        foreach ($data['subject_ids'] as $subjectId) {
            \Illuminate\Support\Facades\DB::table('program_subjects')->updateOrInsert(
                [
                    'program_id'      => $data['program_id'],
                    'subject_id'      => $subjectId,
                    'year_level'      => $data['year_level'],
                    'semester_number' => $data['semester_number'],
                ],
                [
                    'is_active'  => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        return redirect()
            ->route('dean.curricula-panel.programs', ['program_id' => $data['program_id']])
            ->with('success', $count . ' subject(s) added to program.');
    }

    public function detachProgramSubject($pivotId)
    {
        $row = \Illuminate\Support\Facades\DB::table('program_subjects')->where('id', $pivotId)->first();
        \Illuminate\Support\Facades\DB::table('program_subjects')->where('id', $pivotId)->delete();

        return redirect()
            ->route('dean.curricula-panel.programs', ['program_id' => $row->program_id ?? null])
            ->with('success', 'Subject removed from program.');
    }

    public function updateProgramSubject(Request $request, $pivotId)
    {
        $data = $request->validate([
            'year_level'      => 'required|integer|min:1|max:6',
            'semester_number' => 'required|integer|min:1|max:5',
        ]);

        $row = \Illuminate\Support\Facades\DB::table('program_subjects')->where('id', $pivotId)->first();
        if (! $row) {
            return redirect()->back()->withErrors(['error' => 'Program-subject row not found.']);
        }

        // Prevent unique-constraint collision (program_id + subject_id + year + sem)
        $conflict = \Illuminate\Support\Facades\DB::table('program_subjects')
            ->where('program_id', $row->program_id)
            ->where('subject_id', $row->subject_id)
            ->where('year_level', $data['year_level'])
            ->where('semester_number', $data['semester_number'])
            ->where('id', '!=', $pivotId)
            ->exists();

        if ($conflict) {
            return redirect()
                ->route('dean.curricula-panel.programs', ['program_id' => $row->program_id])
                ->withErrors(['error' => 'This subject is already assigned to that year/semester.']);
        }

        \Illuminate\Support\Facades\DB::table('program_subjects')
            ->where('id', $pivotId)
            ->update([
                'year_level'      => $data['year_level'],
                'semester_number' => $data['semester_number'],
                'updated_at'      => now(),
            ]);

        return redirect()
            ->route('dean.curricula-panel.programs', ['program_id' => $row->program_id])
            ->with('success', 'Program subject updated.');
    }
}
