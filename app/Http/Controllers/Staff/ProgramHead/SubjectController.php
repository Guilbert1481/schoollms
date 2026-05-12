<?php

namespace App\Http\Controllers\Staff\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Repositories\Eloquent\SubjectRepository;
use App\Support\AcademicNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use App\Models\AcademicLevel;

class SubjectController extends Controller
{
    /** Allowed subject category values (kept in sync with the DB ENUM). */
    public const CATEGORIES = ['gen_ed', 'prof_ed', 'major', 'pe', 'nstp', 'internship'];

    protected $subjectRepo;

    public function __construct(SubjectRepository $subjectRepo)
    {
        $this->subjectRepo = $subjectRepo;
    }

    public function index(Request $request)
{
    $userId = auth()->id();
    $status = $request->query('status', 'all'); // all|active|inactive

    // Programs assigned to this Program Head
    $programIds = \App\Models\Program::where('program_head_id', $userId)
        ->pluck('id');

    // Pull program_subjects rows for those programs, with the pivot's is_active
    // collapsed per subject: a subject is "active" if ANY of its mappings are active.
    $pivotQuery = \DB::table('program_subjects')
        ->whereIn('program_id', $programIds)
        ->select('subject_id', \DB::raw('MAX(is_active) as ps_active'))
        ->groupBy('subject_id');

    if ($status === 'active') {
        $pivotQuery->havingRaw('MAX(is_active) = 1');
    } elseif ($status === 'inactive') {
        $pivotQuery->havingRaw('MAX(is_active) = 0');
    }

    $pivotRows = $pivotQuery->get();
    $subjectIds = $pivotRows->pluck('subject_id');
    $activeMap  = $pivotRows->pluck('ps_active', 'subject_id'); // [subject_id => 0|1]

    $subjects = Subject::query()
        ->when($programIds->isNotEmpty(), function ($q) use ($subjectIds) {
            $q->whereIn('id', $subjectIds);
        }, function ($q) {
            $q->whereRaw('1 = 0');
        })
        ->withCount(['topics', 'lessons', 'competencies'])
        ->orderBy('name')
        ->get();

    // Overlay pivot active flag onto each subject so the view shows the pivot status
    $subjects->transform(function ($s) use ($activeMap) {
        $s->is_active = (int) ($activeMap[$s->id] ?? 0);
        return $s;
    });

    return view('program_head.subjects.index', compact('subjects', 'status'))
        ->with('categories', self::CATEGORIES);
}

    /**
     * Resolve the data scope for the current user.
     * - program_head           => 'academic'
     * - training_program_head  => 'training'
     */
    protected function currentScope(): string
    {
        return auth()->user()->role === 'training_program_head'
            ? 'training'
            : 'academic';
    }



    public function store(Request $request)
{
    // 1. Validate (Remove the 'unique' check on name if you want duplicates)
    $request->validate([
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:32|unique:subjects,code', // Keep unique code if every sub has a unique ID
        'description' => 'nullable|string|max:1000',
        'active' => 'required|boolean',
        'category' => 'required|in:' . implode(',', self::CATEGORIES),
    ]);

    $name = AcademicNormalizer::normalize($request->name);

    // 2. Modified Check: Only block if the NAME AND CODE are both identical
    // You might want to create existsByNameAndCode() in your Repo instead
    /* if ($this->subjectRepo->existsByNameAndCode($name, $request->code)) {
         return back()->withErrors(['name' => 'This subject code is already taken.'])->withInput();
    }
    */

    // 3. Prepare and Save
    $data = $request->all();
    $data['name'] = $name; 
    $data['scope'] = $this->currentScope();
 

    try {
        $this->subjectRepo->create($data);
        return redirect()->route('program_head.subjects.index')->with('success', 'Subject added successfully!');
    } catch (\Exception $e) {
        // This catches database-level errors (like that unique constraint violation)
        return back()->withErrors(['error' => 'Database Error: ' . $e->getMessage()])->withInput();
    }
}

    public function update(Request $request, $id)
    {
        $request->validate([
            'category' => 'nullable|in:' . implode(',', self::CATEGORIES),
        ]);
        $subject = $this->scopedSubjectQuery()->findOrFail($id);
        $subject->update($request->only(['name', 'code', 'description', 'is_active', 'category']));
        return redirect()->route('program_head.subjects.index')->with('success', 'Subject updated!');
    }

    public function destroy($id)
    {
        $subject = $this->scopedSubjectQuery()->findOrFail($id);
        $subject->delete();
        return redirect()->route('program_head.subjects.index')->with('success', 'Subject deleted!');
    }

    /**
     * Base query for the current user's scope + school.
     */
    protected function scopedSubjectQuery()
    {
        $scope = $this->currentScope();

        return Subject::where('school_id', auth()->user()->school_id)
            ->where(function ($q) use ($scope) {
                if ($scope === 'academic') {
                    $q->where('scope', 'academic')->orWhereNull('scope');
                } else {
                    $q->where('scope', $scope);
                }
            });
    }

    public function getTopics($subjectId)
    {
        // For simple sub-queries like this, you can still use the Model 
        // or add a getTopics method to the Repo.
        return response()->json(Topic::where('subject_id', $subjectId)->get(['id', 'name']));
    }

    public function storeTopics(Request $request, $subjectId)
{
    
}
}