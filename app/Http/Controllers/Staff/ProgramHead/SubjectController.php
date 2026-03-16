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
    protected $subjectRepo;

    public function __construct(SubjectRepository $subjectRepo)
    {
        $this->subjectRepo = $subjectRepo;
    }

    public function index()
{
    $subjects = Subject::where('school_id', auth()->user()->school_id)
        ->withCount(['topics', 'lessons', 'competencies'])
        ->paginate(10);

    

    return view('program_head.subjects.index', compact('subjects'));
}



    public function store(Request $request)
{
    // 1. Validate (Remove the 'unique' check on name if you want duplicates)
    $request->validate([
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:32|unique:subjects,code', // Keep unique code if every sub has a unique ID
        'description' => 'nullable|string|max:1000',
        'active' => 'required|boolean',
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
        $this->subjectRepo->update($id, $request->only(['name', 'code', 'description', 'active']));
        return redirect()->route('program_head.subjects.index')->with('success', 'Subject updated!');
    }

    public function destroy($id)
    {
        $this->subjectRepo->delete($id);
        return redirect()->route('program_head.subjects.index')->with('success', 'Subject deleted!');
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