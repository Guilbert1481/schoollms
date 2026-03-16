<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Program;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use App\Models\SchoolSetting;

class TestManagerController extends Controller
{
    /**
     * ============================
     * TEST MANAGER MAIN PAGE
     * ============================
     */
    public function index()
    {
        $schoolId = auth()->user()->school_id;

        $settings = SchoolSetting::where('school_id', $schoolId)->first();

        return view('admin.test-manager.index', [
            'academicLevels' => $settings?->academic_levels ?? [],
            'questionTypes'    => $settings?->question_types ?? [],
            'difficultyLevels' => $settings?->difficulty_levels ?? [],
            'assessmentTypes'  => $settings?->assessment_types ?? [],
            'subjects'         => Subject::where('school_id', $schoolId)->orderBy('name')->get(),
        ]);
    }

    /**
     * ============================
     * PROGRAM
     * ============================
     */
    public function storeProgram(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Program::firstOrCreate([
            'name'       => ucwords(strtolower(trim($request->name))),
            'owner_type' => 'school',
            'owner_id'   => auth()->user()->school_id,
        ]);

        return back()->with('success_program', 'Program saved successfully.');
    }

    /**
     * ============================
     * SUBJECT
     * ============================
     */
    public function storeSubject(Request $request)
    {
        $request->validate([
            'names'   => 'required|array',
            'names.*' => 'required|string|max:255',
        ]);

        $schoolId  = auth()->user()->school_id;
        $teacherId = auth()->id();

        foreach ($request->names as $name) {
            Subject::updateOrInsert(
                [
                    'teacher_id' => $teacherId,
                    'name'       => ucwords(strtolower(trim($name))),
                ],
                [
                    'school_id'  => $schoolId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return back()->with('success_subject', 'Subjects saved successfully.');
    }

    /**
     * ============================
     * TOPIC
     * ============================
     */
    public function storeTopic(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'names'      => 'required|array',
            'names.*'    => 'required|string|max:255',
        ]);

        $schoolId  = auth()->user()->school_id;
        $teacherId = auth()->id();

        foreach ($request->names as $name) {
            Topic::updateOrInsert(
                [
                    'teacher_id' => $teacherId,
                    'name'       => ucwords(strtolower(trim($name))),
                ],
                [
                    'subject_id' => $request->subject_id,
                    'school_id'  => $schoolId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return back()->with('success_topic', 'Topics saved successfully.');
    }

    public function getTopicsBySubject($subjectId)
    {
        $schoolId = auth()->user()->school_id;

        return response()->json(
            Topic::where('subject_id', $subjectId)
                ->where('school_id', $schoolId)
                ->orderBy('name')
                ->select('id', 'name')
                ->get()
        );
    }

    /**
     * ============================
     * LESSON
     * ============================
     */
    public function storeLesson(Request $request)
    {
        $request->validate([
            'topic_id'   => 'required|exists:topics,id',
            'subject_id' => 'required|exists:subjects,id',
            'names'      => 'required|array',
            'names.*'    => 'required|string|max:255',
        ]);

        $schoolId  = auth()->user()->school_id;
        $teacherId = auth()->id();

        foreach ($request->names as $name) {
            Lesson::firstOrCreate(
                [
                    'teacher_id' => $teacherId,
                    'topic_id'   => $request->topic_id,
                    'name'       => ucwords(strtolower(trim($name))),
                ],
                [
                    'subject_id' => $request->subject_id,
                    'school_id'  => $schoolId,
                ]
            );
        }


        return back()->with('success_lesson', 'Lessons saved successfully.');
    }

    public function getLessonsByTopic($topicId)
    {
        $schoolId = auth()->user()->school_id;

        return response()->json(
            Lesson::where('topic_id', $topicId)
                ->where('school_id', $schoolId)
                ->orderBy('name')
                ->select('id', 'name')
                ->get()
        );
    }

    /**
     * ============================
     * COMPETENCY
     * ============================
     */
    public function storeCompetency(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'topic_id'   => 'required|exists:topics,id',
            'lesson_id'  => 'required|exists:lessons,id',
            'names'      => 'required|array',
            'names.*'    => 'required|string|max:255',
        ]);

        $schoolId  = auth()->user()->school_id;
        $teacherId = auth()->id();

        foreach ($request->names as $name) {
            Competency::updateOrInsert(
                [
                    'teacher_id' => $teacherId,
                    'lesson_id'  => $request->lesson_id,
                    'name'       => ucwords(strtolower(trim($name))),
                ],
                [
                    'subject_id' => $request->subject_id,
                    'topic_id'   => $request->topic_id,
                    'school_id'  => $schoolId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return back()->with('success_competency', 'Competencies saved successfully.');
    }
}
