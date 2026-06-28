<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\EducationNode;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EducationNodeController extends Controller
{
    /**
     * Tree page (admin) or JSON of children when ?parent_id is provided.
     */
    public function index(Request $request): View|JsonResponse
    {
        // Optional helper API: GET ?parent_id=X returns offered children.
        if ($request->has('parent_id')) {
            $children = EducationNode::where('parent_id', $request->integer('parent_id'))
                ->where('is_offered', true)
                ->where('is_active', true)
                ->orderBy('order_index')
                ->orderBy('id')
                ->get(['id', 'name', 'node_type', 'parent_id']);

            return response()->json($children);
        }

        $tree      = EducationNode::tree();
        $nodeTypes = EducationNode::TYPES;

        // Programs created by Deans / Admin — surfaced as children of whichever
        // education_node they link to (`programs.education_node_id`). Legacy
        // rows with NULL fall back to a virtual "Undergraduate / College" root
        // bucket so they stay visible.
        $schoolId = $request->user()?->school_id;
        $programsAll = Program::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'active', 'education_node_id']);

        $programsByNode = $programsAll->groupBy(
            fn ($p) => (int) ($p->education_node_id ?? 0)
        );

        // Colleges (this school) for the "Add Program" modal's College picker.
        $colleges = College::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.education_nodes.index', compact('tree', 'nodeTypes', 'programsByNode', 'colleges'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'parent_id'   => ['nullable', 'integer', 'exists:education_nodes,id'],
            'node_type'   => ['required', 'string', 'in:level,stage,track,strand,program_type'],
            'order_index' => ['nullable', 'integer'],
            'is_offered'  => ['nullable', 'boolean'],
        ]);

        $data['order_index'] = $data['order_index'] ?? 0;
        $data['is_offered']  = (bool) ($data['is_offered'] ?? false);
        $data['is_active']   = true;

        $node = EducationNode::create($data);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'node' => $node], 201);
        }

        return redirect()
            ->route('admin.education-nodes.index')
            ->with('status', 'Node created.');
    }

    public function update(Request $request, EducationNode $education_node): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'node_type'   => ['sometimes', 'required', 'string', 'in:level,stage,track,strand,program_type'],
            'order_index' => ['sometimes', 'integer'],
            'is_offered'  => ['sometimes', 'boolean'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $education_node->update($data);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'node' => $education_node->fresh()]);
        }

        return redirect()
            ->route('admin.education-nodes.index')
            ->with('status', 'Node updated.');
    }

    public function destroy(EducationNode $education_node): JsonResponse|RedirectResponse
    {
        $education_node->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('admin.education-nodes.index')
            ->with('status', 'Node deleted.');
    }

    public function toggleOffered(Request $request, EducationNode $education_node): JsonResponse
    {
        $request->validate([
            'is_offered' => ['nullable', 'boolean'],
        ]);

        $next = $request->has('is_offered')
            ? (bool) $request->boolean('is_offered')
            : ! $education_node->is_offered;

        $education_node->update(['is_offered' => $next]);

        return response()->json([
            'ok'         => true,
            'id'         => $education_node->id,
            'is_offered' => $education_node->is_offered,
        ]);
    }

    /* ---------------------------------------------------------------
     | Program proxy actions — surfaces dean/admin programs inside the
     | tree with the same UX (checkbox toggle / edit / delete).
     | --------------------------------------------------------------- */

    public function toggleProgramActive(Request $request, Program $program): JsonResponse
    {
        $request->validate(['is_offered' => ['nullable', 'boolean']]);
        $next = $request->has('is_offered')
            ? (bool) $request->boolean('is_offered')
            : ! ((bool) $program->active);
        $program->update(['active' => $next]);

        return response()->json(['ok' => true, 'id' => $program->id, 'is_offered' => $next]);
    }

    public function updateProgram(Request $request, Program $program): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64'],
        ]);

        $program->update($data);

        return response()->json(['ok' => true, 'program' => $program->fresh()]);
    }

    public function destroyProgram(Program $program): JsonResponse
    {
        $program->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Create a program FROM the education tree (the "reverse" creation flow).
     *
     * Programs remain rows in the `programs` table — the global tree just gains
     * a second creation entry point. Allowed only under a higher-education
     * `level` node (never Basic Education). De-duplicates by school_id + code so
     * it never twins a manually-created program.
     */
    public function storeProgram(Request $request): JsonResponse
    {
        $schoolId = (int) $request->user()->school_id;

        $data = $request->validate([
            'code'              => ['required', 'string', 'max:50'],
            'name'              => ['required', 'string', 'max:255'],
            'college_id'        => ['required', 'integer'],
            'education_node_id' => ['required', 'integer', 'exists:education_nodes,id'],
        ]);

        // College must belong to this school.
        $college = College::where('school_id', $schoolId)->find($data['college_id']);
        if (! $college) {
            return response()->json(['ok' => false, 'message' => 'Please choose a college that belongs to your school.'], 422);
        }

        // Target node must be a higher-education level (programs are not added
        // under Basic Education, tracks, strands, etc.).
        $node = EducationNode::findOrFail($data['education_node_id']);
        if ($node->node_type !== 'level' || str_contains(strtolower((string) $node->name), 'basic')) {
            return response()->json(['ok' => false, 'message' => 'Programs can only be added under a higher-education level.'], 422);
        }

        // De-dup: reuse an existing program with the same code for this school,
        // otherwise create it. Either way it ends up linked to this node.
        $program = Program::firstOrNew([
            'school_id' => $schoolId,
            'code'      => $data['code'],
        ]);
        $created = ! $program->exists;
        $program->fill([
            'name'              => $data['name'],
            'college_id'        => $college->id,
            'education_node_id' => $node->id,
            'active'            => true,
        ])->save();

        return response()->json([
            'ok'      => true,
            'created' => $created,
            'program' => $program->only(['id', 'code', 'name', 'college_id', 'education_node_id']),
        ], $created ? 201 : 200);
    }

    /**
     * Quick-create a college from the "Add Program" modal when the school has
     * none yet (so a program always has a college to attach to).
     */
    public function storeCollege(Request $request): JsonResponse
    {
        $schoolId = (int) $request->user()->school_id;

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $college = College::create([
            'school_id' => $schoolId,
            'code'      => $data['code'],
            'name'      => $data['name'],
            'is_active' => true,
        ]);

        return response()->json([
            'ok'      => true,
            'college' => $college->only(['id', 'name', 'code']),
        ], 201);
    }
}
