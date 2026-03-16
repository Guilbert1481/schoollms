<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestInfoRequest extends FormRequest
{
    public function authorize()
    {
        // Only authenticated teachers should be allowed; adjust role check if you have roles
        return $this->user() != null && $this->user()->role === 'teacher';
    }

    public function rules()
    {
        return [
            'subject_id' => 'required|integer|exists:subjects,id',
            'topic_id' => 'nullable|integer|exists:topics,id',
            'lesson_id' => 'nullable|integer|exists:lessons,id',
            'competency_id' => 'nullable|integer|exists:competencies,id',
            'academic_level' => 'required|string|max:64',
            'question_type' => 'required|string|max:64',
            'title' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'subject_id.required' => 'Please select a subject.',
            'topic_id.required' => 'Please select a topic.',
            'lesson_id.required' => 'Please select a lesson.',
            'competency_id.required' => 'Please select a competency.',
        ];
    }
}