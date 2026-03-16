@extends('layouts.app')

@section('content')
<x-dashboard-header />

<div class="card" style="max-width:720px;">
    <h4>Add Topic</h4>

    <form method="POST" action="#">
        @csrf

        <div class="form-group">
            <label>Subject</label>
            <select name="subject_id" class="form-input" required>
                <option value="">Select subject</option>
                <!-- Replace with dynamic subjects later -->
                <option value="1">Mathematics</option>
                <option value="2">Science</option>
                <option value="3">English</option>
            </select>
        </div>

        <div class="form-group">
            <label>Topic Name</label>
            <input
                type="text"
                name="name"
                class="form-input"
                placeholder="e.g. Algebra"
                required
            >
        </div>

        <div class="form-group">
            <label>Description (Optional)</label>
            <textarea
                name="description"
                class="form-input"
                rows="3"
                placeholder="Brief description of the topic"
            ></textarea>
        </div>

        <div style="display:flex; gap:12px; margin-top:16px;">
            <button type="submit" class="primary">Save Topic</button>

            <a href="{{ url('/teacher/dashboard') }}"
               class="secondary"
               style="padding:10px 18px; border-radius:10px; text-decoration:none;">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
