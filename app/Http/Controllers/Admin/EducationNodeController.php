<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationNode;
use App\Services\Tests\LevelVocabularySync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EducationNodeController extends Controller
{
    public function __construct(private LevelVocabularySync $levelSync) {}

    /**
     * Tree page (admin) or JSON of children when ?parent_id is provided.
     *
     * This page manages the education STRUCTURE only (levels / stages / tracks /
     * strands / program_types). It has no connection to the `programs` table —
     * programs are managed entirely on the Programs page.
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

        $tree = EducationNode::tree();
        $nodeTypes = EducationNode::TYPES;

        return view('admin.education_nodes.index', compact('tree', 'nodeTypes'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:education_nodes,id'],
            'node_type' => ['required', 'string', 'in:level,stage,track,strand,program_type'],
            'order_index' => ['nullable', 'integer'],
            'is_offered' => ['nullable', 'boolean'],
        ]);

        $data['order_index'] = $data['order_index'] ?? 0;
        $data['is_offered'] = (bool) ($data['is_offered'] ?? false);
        $data['is_active'] = true;

        $node = EducationNode::create($data);
        $this->levelSync->syncAllSchools();

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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'node_type' => ['sometimes', 'required', 'string', 'in:level,stage,track,strand,program_type'],
            'order_index' => ['sometimes', 'integer'],
            'is_offered' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $education_node->update($data);
        $this->levelSync->syncAllSchools();

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
        $this->levelSync->syncAllSchools();

        return response()->json([
            'ok' => true,
            'id' => $education_node->id,
            'is_offered' => $education_node->is_offered,
        ]);
    }
}
