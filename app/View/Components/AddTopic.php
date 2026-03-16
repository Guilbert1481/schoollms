<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;

class AddTopic extends Component
{
    public $subjects;

    public function __construct()
    {
        $this->subjects = Subject::where('teacher_id', Auth::id())->get();
    }

    public function render()
    {
        return view('components.add-topic');
    }
}
