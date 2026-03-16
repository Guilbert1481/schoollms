<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\School;
use App\Models\User;
use App\Models\Subject;
use App\Models\Program;
use App\Models\Topic;
use App\Models\Lesson;
use App\Models\Competency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuestionConfigController extends Controller
{
    /**
     * Show the Test Manager page with configurations
     */
    public function edit()
    {
        $user = auth()->user();
        
        // Determine the owner (School or Freelance Teacher)
        $owner = $this->getOwner($user);
        
        if (!$owner) {
            return back()->with('error', 'Unable to determine configuration owner.');
        }
        
        $ownerType = get_class($owner);
        $ownerId = $owner->id;
        
    
        
        $difficulties = Configuration::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('category', 'difficulty')
            ->orderBy('order_index')
            ->get();
        
        $questionTypes = Configuration::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('category', 'question_type')
            ->orderBy('order_index')
            ->get();
        
        $assessmentTypes = Configuration::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('category', 'assessment_type')
            ->orderBy('order_index')
            ->get();
        
        $termDivisions = Configuration::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('category', 'term_division')
            ->orderBy('order_index')
            ->get();
        
        // Get Test Manager data (Subjects, Programs, Topics, etc.)
        $schoolId = $user->school_id;
        
        $subjects = Subject::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
        
        $programs = Program::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
        
        $topics = Topic::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
        
        $lessons = Lesson::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
        
        $competencies = Competency::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();
        
        // For backward compatibility with old variable names
        $difficultyLevels = $difficulties;
        
        return view('admin.test-manager.index', compact(
            'academicLevels',
            'difficulties',
            'difficultyLevels',
            'questionTypes',
            'assessmentTypes',
            'termDivisions',
            'subjects',
            'programs',
            'topics',
            'lessons',
            'competencies'
        ));
    }
    
    /**
     * Update configurations (enable/disable via checkboxes)
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $owner = $this->getOwner($user);
        
        if (!$owner) {
            return back()->with('error', 'Unable to determine configuration owner.');
        }
        
        $enabledIds = $request->input('enabled_configs', []);
        
        // Disable all configurations for this owner first
        Configuration::where('owner_type', get_class($owner))
            ->where('owner_id', $owner->id)
            ->update(['is_active' => false]);
        
        // Enable selected ones
        if (!empty($enabledIds)) {
            Configuration::where('owner_type', get_class($owner))
                ->where('owner_id', $owner->id)
                ->whereIn('id', $enabledIds)
                ->update(['is_active' => true]);
        }
        
        return back()->with('success', 'Configuration updated successfully.');
    }
    
    /**
     * Store a new configuration (AJAX)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'category' => 'required|in:academic_level,difficulty,question_type,assessment_type,term_division',
                'label' => 'required|string|max:255',
            ]);
            
            $user = auth()->user();
            $owner = $this->getOwner($user);
            
            if (!$owner) {
                return response()->json(['error' => 'Unable to determine owner'], 400);
            }
            
            $value = strtolower(str_replace(' ', '_', $request->label));
            
            // Check if already exists
            $exists = Configuration::where('owner_type', get_class($owner))
                ->where('owner_id', $owner->id)
                ->where('category', $request->category)
                ->where('value', $value)
                ->exists();
            
            if ($exists) {
                return response()->json(['error' => 'This configuration already exists'], 400);
            }
            
            // Get max order index
            $maxOrder = Configuration::where('owner_type', get_class($owner))
                ->where('owner_id', $owner->id)
                ->where('category', $request->category)
                ->max('order_index');
            
            // Create new configuration
            $config = Configuration::create([
                'owner_type' => get_class($owner),
                'owner_id' => $owner->id,
                'category' => $request->category,
                'label' => $request->label,
                'value' => $value,
                'is_active' => true,
                'order_index' => ($maxOrder ?? 0) + 1,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Configuration added successfully',
                'data' => $config
            ]);
            
        } catch (\Exception $e) {
            Log::error('Configuration store error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }
    
    /**
     * Delete a configuration (AJAX)
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $owner = $this->getOwner($user);
            
            if (!$owner) {
                return response()->json(['error' => 'Unable to determine owner'], 400);
            }
            
            $config = Configuration::where('owner_type', get_class($owner))
                ->where('owner_id', $owner->id)
                ->where('id', $id)
                ->first();
            
            if (!$config) {
                return response()->json(['error' => 'Configuration not found'], 404);
            }
            
            $config->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Configuration deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Configuration delete error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }
    
    /**
     * Update configuration label (AJAX - double-click edit)
     */
    public function updateLabel(Request $request, $id)
    {
        try {
            $request->validate([
                'label' => 'required|string|max:255'
            ]);
            
            $user = auth()->user();
            $owner = $this->getOwner($user);
            
            if (!$owner) {
                return response()->json(['error' => 'Unable to determine owner'], 400);
            }
            
            $config = Configuration::where('owner_type', get_class($owner))
                ->where('owner_id', $owner->id)
                ->where('id', $id)
                ->first();
            
            if (!$config) {
                return response()->json(['error' => 'Configuration not found'], 404);
            }
            
            $config->update([
                'label' => $request->label,
                'value' => strtolower(str_replace(' ', '_', $request->label)),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $config
            ]);
            
        } catch (\Exception $e) {
            Log::error('Configuration update label error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }
    
    /**
     * Helper: Determine owner (School or Freelance Teacher)
     */
    private function getOwner($user)
    {
        // If user belongs to a school
        if ($user->school_id) {
            // Check if user is admin
            if ($user->role !== 'admin') {
                // Regular teacher in a school - no access
                abort(403, 'Only school administrators can manage configurations.');
            }
            
            return School::find($user->school_id);
        }
        
        // Freelance teacher (no school_id)
        return $user;
    }
}