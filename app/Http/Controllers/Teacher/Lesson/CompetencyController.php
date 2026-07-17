<?php

namespace App\Http\Controllers\Teacher\Lesson;

use App\Http\Controllers\Controller;
use App\Models\Competency;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Subject;
use App\Models\TeacherHiddenCompetency;
use App\Support\SubjectScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Teacher → Lesson competencies. A teacher may author their own competencies
 * (with optional media) under a lesson in one of their assigned subjects, hide
 * the shared Course-Architect competencies from their own view, and delete only
 * the competencies they created. Every action is tenant- and assignment-scoped.
 */
class CompetencyController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'lesson_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'bloom_level' => ['nullable', Rule::in(['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'])],
            'file' => ['nullable', 'file', 'max:204800', 'mimes:mp4,mov,webm,mkv,pdf,ppt,pptx'],
        ]);

        // The lesson must be in the teacher's school and an assigned subject.
        $assignedIds = SubjectScope::restrictToTeacher(Subject::query(), $user)->pluck('id')->all();
        $lesson = Lesson::whereKey($data['lesson_id'])
            ->where('school_id', $user->school_id)
            ->whereIn('subject_id', $assignedIds)
            ->firstOrFail();

        $competency = Competency::create([
            'school_id' => $user->school_id,
            'subject_id' => $lesson->subject_id,
            'topic_id' => $lesson->topic_id,
            'lesson_id' => $lesson->id,
            'code' => $data['code'] ?? null,
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'bloom_level' => $data['bloom_level'] ?? null,
            'created_by' => $user->id,
        ]);

        if ($request->hasFile('file')) {
            $this->storeMedia($request->file('file'), $competency, $user);
        }

        return response()->json($this->present($competency->fresh(), $user));
    }

    public function show(Request $request, Competency $competency)
    {
        $user = $request->user();
        $this->authorizeCompetency($competency, $user);

        return response()->json($this->present($competency, $user));
    }

    public function destroy(Request $request, Competency $competency)
    {
        $user = $request->user();
        $this->authorizeCompetency($competency, $user);

        // Only the author may delete — Course-Architect competencies are hidden, not deleted.
        abort_unless(
            (int) $competency->created_by === (int) $user->id,
            403,
            'You can only delete competencies you created.'
        );

        foreach (LessonResource::where('competency_id', $competency->id)->get() as $resource) {
            if ($resource->file_path && Storage::disk('public')->exists($resource->file_path)) {
                Storage::disk('public')->delete($resource->file_path);
            }
            $resource->delete();
        }

        TeacherHiddenCompetency::where('competency_id', $competency->id)->delete();
        $competency->delete();

        return response()->json(['deleted' => true]);
    }

    public function hide(Request $request, Competency $competency)
    {
        $user = $request->user();
        $this->authorizeCompetency($competency, $user);

        TeacherHiddenCompetency::firstOrCreate([
            'user_id' => $user->id,
            'competency_id' => $competency->id,
        ]);

        return response()->json(['hidden' => true]);
    }

    public function unhide(Request $request, Competency $competency)
    {
        $user = $request->user();
        $this->authorizeCompetency($competency, $user);

        TeacherHiddenCompetency::where('user_id', $user->id)
            ->where('competency_id', $competency->id)
            ->delete();

        return response()->json(['hidden' => false]);
    }

    /* ---------------------------------------------------------------- */

    /** A teacher may only touch competencies in their school + assigned subjects. */
    private function authorizeCompetency(Competency $competency, $user): void
    {
        abort_unless((int) $competency->school_id === (int) $user->school_id, 403);

        $assigned = SubjectScope::restrictToTeacher(Subject::whereKey($competency->subject_id), $user)->exists();
        abort_unless($assigned, 403);
    }

    private function storeMedia($file, Competency $competency, $user): void
    {
        $path = $file->store("lesson-resources/{$user->school_id}", 'public');
        $ext = strtolower($file->getClientOriginalExtension());
        $type = in_array($ext, ['mp4', 'mov', 'webm', 'mkv'], true)
            ? 'video'
            : ($ext === 'pdf' ? 'pdf' : 'ppt');

        LessonResource::create([
            'school_id' => $user->school_id,
            'subject_id' => $competency->subject_id,
            'topic_id' => $competency->topic_id,
            'lesson_id' => $competency->lesson_id,
            'competency_id' => $competency->id,
            'file_path' => $path,
            'file_type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'created_by' => $user->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function present(Competency $competency, $user): array
    {
        $resources = LessonResource::where('competency_id', $competency->id)
            ->whereNotNull('file_path')
            ->get(['file_type', 'original_filename', 'file_path'])
            ->map(fn ($r) => [
                'type' => $r->file_type,
                'name' => $r->original_filename,
                'url' => Storage::disk('public')->url($r->file_path),
            ])->values();

        return [
            'id' => $competency->id,
            'lesson_id' => $competency->lesson_id,
            'code' => $competency->code,
            'name' => $competency->name,
            'description' => $competency->description,
            'bloom' => $competency->bloom_level,
            'mine' => $competency->created_by !== null && (int) $competency->created_by === (int) $user->id,
            'hidden' => false,
            'resources' => $resources,
        ];
    }
}
