<?php

namespace App\Http\Controllers\Student\Services;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Student side of document requests: pick a document from the (global)
 * catalog, state the purpose, then follow the request through
 * pending → processing → ready → released as the registrar works it.
 */
class DocumentRequestController extends Controller
{
    public function index()
    {
        $student = $this->student();

        return view('student.services.documents', [
            'catalog' => Document::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'type']),
            'requests' => DocumentRequest::query()
                ->where('student_id', $student->id)
                ->with('document')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $student = $this->student();

        $validated = $request->validate([
            'document_id' => ['required', 'integer', 'exists:documents,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'copies' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $document = Document::query()->where('is_active', true)->find($validated['document_id']);
        if (! $document) {
            return back()->withErrors(['document_id' => 'That document is not available for request.']);
        }

        $alreadyOpen = DocumentRequest::query()
            ->where('student_id', $student->id)
            ->where('document_id', $document->id)
            ->whereIn('status', [DocumentRequest::STATUS_PENDING, DocumentRequest::STATUS_PROCESSING, DocumentRequest::STATUS_READY])
            ->exists();
        if ($alreadyOpen) {
            return back()->withErrors(['document_id' => 'You already have an open request for that document.']);
        }

        DocumentRequest::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'document_id' => $document->id,
            'purpose' => $validated['purpose'],
            'copies' => (int) $validated['copies'],
        ]);

        $document->increment('request_count');

        return back()->with('success', 'Document request submitted. Track its status below.');
    }

    private function student(): Student
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->where('school_id', $user->school_id)->first();
        abort_unless($student, 403);

        return $student;
    }
}
