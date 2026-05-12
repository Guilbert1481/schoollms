<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\SubjectScope;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::with(['subject','topic'])
            ->orderBy('question_text')
            ->paginate(20);

        return view('teacher.questions.index', compact('questions'));
    }

    public function create()
    {
        $subjects = SubjectScope::applyTo(Subject::query())->orderBy('name')->get();
        $topics   = Topic::whereIn('subject_id', $subjects->pluck('id'))->get();

        return view('teacher.questions.create', compact('subjects','topics'));
    }

    // ❌ DO NOT USE THIS FOR MCQ PAGE
    public function store(Request $request)
    {
        abort(403, 'Direct question creation is disabled.');
    }
}
