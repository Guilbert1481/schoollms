<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Base\BaseCrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Principal Curricula Panel.
 *
 * The principal is the basic-ed counterpart of the dean + program head.
 * They manage:
 *  - Subjects (school-wide masterlist)
 *  - Grade-Level Subjects (assigning subjects to Kinder / Grades 1-12)
 *
 * Grade levels are stored as `education_nodes` of node_type = 'stage'
 * under the "Basic Education" level node. The pivot is
 * `grade_level_subjects` (education_node_id <-> subject_id).
 */
class CurriculumPanelController extends BaseCrudController
{
    /* ============================================================
     | Tab pages
     |============================================================*/

    public function indexSubjects()
    {
        $schoolId = auth()->user()->school_id;

        $data = DB::table('subjects')
            ->where('school_id', $schoolId)
            ->where('is_basic_ed', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($r) {
                $r->is_active_label = ((int) $r->is_active === 1) ? 'Active' : 'Inactive';

                return $r;
            });

        $columns = [
            ['key' => 'name',            'label' => 'Subject Name'],
            ['key' => 'code',            'label' => 'Code'],
            ['key' => 'is_active_label', 'label' => 'Status'],
        ];

        $actions = [
            [
                'name' => 'edit',
                'label' => 'Edit',
                'class' => 'bg-blue-500 text-white hover:bg-blue-600',
                'type' => 'modal',
                'modal' => 'subjectEditModal',
            ],
            [
                'name' => 'delete',
                'label' => 'Delete',
                'class' => 'bg-red-500 text-white hover:bg-red-600',
                'type' => 'delete',
            ],
        ];

        $formColumns = ['name', 'code', 'description'];
        $labels = [
            'name' => 'Subject Name',
            'code' => 'Code',
            'description' => 'Description',
        ];

        return view('principal.curricula-panel.subjects', [
            'data' => $data,
            'columns' => $columns,
            'actions' => $actions,
            'table' => 'subjects',
            'formColumns' => $formColumns,
            'labels' => $labels,
        ]);
    }

    public function indexGradeLevels(Request $request)
    {
        $schoolId = auth()->user()->school_id;
        $nodeId = $request->integer('education_node_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;

        // Basic-ed education-level categories (Preschool, Elementary, Junior High,
        // Senior High) are the direct "stage" children of the "Basic Education"
        // level node. The grade levels are the stage descendants beneath the chosen
        // category, so the grade dropdown narrows to the selected category.
        $basicEd = DB::table('education_nodes')
            ->where('node_type', 'level')
            ->where('name', 'Basic Education')
            ->first();

        $categories = $basicEd ? $this->basicEdCategories((int) $basicEd->id) : collect();

        // Resolve which category is active: an explicit pick, else the category the
        // chosen grade level lives under, else the first category.
        if (! $categoryId && $nodeId && $basicEd) {
            $categoryId = $this->categoryOfNode($nodeId, (int) $basicEd->id);
        }
        if (! $categoryId && $categories->isNotEmpty()) {
            $categoryId = (int) $categories->first()->id;
        }

        $gradeLevels = $categoryId ? $this->collectStageDescendants($categoryId) : collect();

        // Drop a grade level that is not in the active category, then fall back to
        // the category's first grade so the subjects table has a selection.
        $gradeIds = $gradeLevels->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($nodeId && ! in_array($nodeId, $gradeIds, true)) {
            $nodeId = null;
        }
        if (! $nodeId && $gradeLevels->isNotEmpty()) {
            $nodeId = (int) $gradeLevels->first()->id;
        }

        $rows = collect();
        if ($nodeId) {
            $query = DB::table('grade_level_subjects')
                ->join('subjects', 'subjects.id', '=', 'grade_level_subjects.subject_id')
                ->where('grade_level_subjects.education_node_id', $nodeId);

            $status = $request->query('status', 'all');
            if ($status === 'active') {
                $query->where('grade_level_subjects.is_active', 1);
            } elseif ($status === 'inactive') {
                $query->where('grade_level_subjects.is_active', 0);
            }

            $rows = $query
                ->orderBy('subjects.name')
                ->get([
                    'grade_level_subjects.id as id',
                    'subjects.id as subject_id',
                    'subjects.name',
                    'subjects.code',
                    'grade_level_subjects.is_active',
                ])
                ->map(function ($r) {
                    $r->is_active_label = ((int) $r->is_active === 1) ? 'Active' : 'Inactive';

                    return $r;
                });
        }

        $columns = [
            ['key' => 'name',  'label' => 'Subject'],
            ['key' => 'code',  'label' => 'Code'],
            [
                'key' => 'is_active_label',
                'label' => 'Status',
                'filter' => [
                    'type' => 'dropdown',
                    'param' => 'status',
                    'default' => 'all',
                    'options' => [
                        'all' => 'All',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ],
                ],
            ],
        ];

        // Subjects in this school not yet attached to the selected grade level
        $availableSubjects = collect();
        if ($nodeId) {
            $attachedIds = $rows->pluck('subject_id');
            $availableSubjects = DB::table('subjects')
                ->where('school_id', $schoolId)
                ->where('is_basic_ed', 1)
                ->whereNotIn('id', $attachedIds)
                ->orderBy('name')
                ->get(['id', 'code', 'name']);
        }

        return view('principal.curricula-panel.grade-levels', [
            'categories' => $categories,
            'categoryId' => $categoryId,
            'gradeLevels' => $gradeLevels,
            'nodeId' => $nodeId,
            'data' => $rows,
            'columns' => $columns,
            'availableSubjects' => $availableSubjects,
        ]);
    }

    /* ============================================================
     | Subject CRUD (delegates to BaseCrudController)
     |============================================================*/

    public function storeSubject(Request $request)
    {
        // Basic-ed subjects start INACTIVE by default. They are activated
        // automatically once admission opens enrollment for that subject's
        // grade level.
        $request->merge([
            'is_basic_ed' => 1,
            'is_active' => $request->has('is_active') ? (int) $request->boolean('is_active') : 0,
        ]);

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
     | Grade Level ↔ Subject pivot
     |============================================================*/

    public function attachGradeLevelSubject(Request $request)
    {
        $data = $request->validate([
            'education_node_id' => 'required|integer|exists:education_nodes,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $count = 0;
        foreach ($data['subject_ids'] as $subjectId) {
            DB::table('grade_level_subjects')->updateOrInsert(
                [
                    'education_node_id' => $data['education_node_id'],
                    'subject_id' => $subjectId,
                ],
                [
                    'is_active' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        return redirect()
            ->route('principal.curricula-panel.grade-levels', [
                'education_node_id' => $data['education_node_id'],
            ])
            ->with('success', $count.' subject(s) added to grade level.');
    }

    public function detachGradeLevelSubject($pivotId)
    {
        $row = DB::table('grade_level_subjects')->where('id', $pivotId)->first();
        DB::table('grade_level_subjects')->where('id', $pivotId)->delete();

        return redirect()
            ->route('principal.curricula-panel.grade-levels', [
                'education_node_id' => $row->education_node_id ?? null,
            ])
            ->with('success', 'Subject removed from grade level.');
    }

    public function toggleGradeLevelSubject($pivotId)
    {
        $row = DB::table('grade_level_subjects')->where('id', $pivotId)->first();
        if (! $row) {
            return redirect()->back()->withErrors(['error' => 'Row not found.']);
        }

        DB::table('grade_level_subjects')
            ->where('id', $pivotId)
            ->update([
                'is_active' => $row->is_active ? 0 : 1,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('principal.curricula-panel.grade-levels', [
                'education_node_id' => $row->education_node_id,
            ])
            ->with('success', 'Subject status updated.');
    }

    /* ============================================================
     | CSV import for grade_level_subjects
     |
     | Headers: subject_code [, is_active]
     |============================================================*/
    public function importGradeLevelSubjects(Request $request)
    {
        $request->validate([
            'education_node_id' => 'required|integer|exists:education_nodes,id',
            'csv' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $schoolId = auth()->user()->school_id;
        $nodeId = (int) $request->input('education_node_id');

        $path = $request->file('csv')->getRealPath();
        $handle = fopen($path, 'r');
        if (! $handle) {
            return redirect()->back()->withErrors(['error' => 'Unable to read CSV file.']);
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return redirect()->back()->withErrors(['error' => 'CSV file is empty.']);
        }
        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        if (! in_array('subject_code', $headers, true)) {
            fclose($handle);

            return redirect()->back()->withErrors([
                'error' => 'CSV must include a "subject_code" column (is_active optional).',
            ]);
        }

        $idx = array_flip($headers);
        $inserted = 0;
        $updated = 0;
        $skipped = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $code = trim((string) ($row[$idx['subject_code']] ?? ''));
            $isAct = isset($idx['is_active'])
                ? (int) filter_var($row[$idx['is_active']] ?? 1, FILTER_VALIDATE_BOOLEAN)
                : 1;

            if ($code === '') {
                $skipped[] = "Line {$line}: blank subject_code.";

                continue;
            }

            $subject = DB::table('subjects')
                ->where('school_id', $schoolId)
                ->where('is_basic_ed', 1)
                ->where('code', $code)
                ->first();

            if (! $subject) {
                $skipped[] = "Line {$line}: subject code '{$code}' not found.";

                continue;
            }

            $existing = DB::table('grade_level_subjects')
                ->where('education_node_id', $nodeId)
                ->where('subject_id', $subject->id)
                ->first();

            if ($existing) {
                DB::table('grade_level_subjects')
                    ->where('id', $existing->id)
                    ->update(['is_active' => $isAct, 'updated_at' => now()]);
                $updated++;
            } else {
                DB::table('grade_level_subjects')->insert([
                    'education_node_id' => $nodeId,
                    'subject_id' => $subject->id,
                    'is_active' => $isAct,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }
        }
        fclose($handle);

        $msg = "Imported grade-level subjects — added: {$inserted}, updated: {$updated}";
        if (! empty($skipped)) {
            $msg .= '. Skipped '.count($skipped).' row(s).';
        }

        $redirect = redirect()->route('principal.curricula-panel.grade-levels', [
            'education_node_id' => $nodeId,
        ])->with('success', $msg);

        if (! empty($skipped)) {
            $redirect->withErrors(['import' => $skipped]);
        }

        return $redirect;
    }

    /* ============================================================
     | Helpers
     |============================================================*/

    /**
     * The basic-ed education-level categories — the direct "stage" children of the
     * "Basic Education" level node (Preschool, Elementary, Junior High, Senior High).
     * These drive the category dropdown; the grade levels narrow to the pick.
     */
    protected function basicEdCategories(int $basicEdId)
    {
        return DB::table('education_nodes')
            ->where('parent_id', $basicEdId)
            ->where('node_type', 'stage')
            ->orderBy('order_index')
            ->get(['id', 'name']);
    }

    /**
     * The category (direct stage child of Basic Education) a grade-level node lives
     * under, found by walking up its ancestry. Null when not under Basic Education.
     */
    protected function categoryOfNode(int $nodeId, int $basicEdId): ?int
    {
        $byId = DB::table('education_nodes')->get(['id', 'parent_id'])->keyBy('id');

        $cur = $byId->get($nodeId);
        for ($i = 0; $i < 32 && $cur; $i++) {
            if ((int) $cur->parent_id === $basicEdId) {
                return (int) $cur->id;
            }
            $cur = $cur->parent_id ? $byId->get($cur->parent_id) : null;
        }

        return null;
    }

    /**
     * Recursively collect all "stage" descendants of an education node
     * (used to enumerate Kinder / Grade 1-12 under Basic Education).
     */
    protected function collectStageDescendants(int $rootId)
    {
        $all = DB::table('education_nodes')
            ->orderBy('order_index')
            ->get();

        $byId = $all->keyBy('id');
        $byParent = $all->groupBy('parent_id');

        $stages = collect();
        $walk = function ($parentId, array $ancestry) use (&$walk, $byParent, &$stages) {
            $children = $byParent->get($parentId, collect());
            foreach ($children as $child) {
                $childAncestry = $ancestry;
                if ($child->node_type === 'stage') {
                    // Build a readable label including non-stage/non-level ancestors.
                    // Strand-scoped SHS stages read as "Grade 11 — STEM", while
                    // elementary/JHS stages remain plain ("Grade 1").
                    $context = array_values(array_filter(
                        $ancestry,
                        fn ($a) => ! in_array($a->node_type, ['stage', 'level'], true)
                    ));
                    $label = $child->name;
                    if (! empty($context)) {
                        $label = $child->name.' — '.implode(' / ', array_map(fn ($a) => $a->name, $context));
                    }
                    $child->label = $label;
                    $stages->push($child);
                    $childAncestry[] = $child;
                } else {
                    $childAncestry[] = $child;
                }
                $walk($child->id, $childAncestry);
            }
        };
        $walk($rootId, []);

        return $stages;
    }
}
