<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pricing;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get requested plan from URL, default to 'basic'
        $activePlan = $request->query('plan', 'basic');

        // 2. Find specific plan record or create default entry
        $pricing = Pricing::firstOrCreate(
            ['plan_name' => $activePlan],
            [
                'student_price' => 0.00,
                'teacher_price' => 0.00,
                'staff_price'   => 0.00,
                'parent_price'  => 0.00,
                'alumni_price'  => 0.00,
                'setup_fee'     => 0.00,
                'addon_price'   => 0.00,
            ]
        );

        return view('superadmin.pricing.index', compact('pricing', 'activePlan'));
    }


    
    public function update(Request $request)
    {
        

        // 3. Validate input data (matching your blade form field names)
        $validated = $request->validate([
            'plan_name'     => 'required|string|in:basic,standard,premium,enterprise',
            'student_price' => 'required|numeric|min:0',
            'teacher_price' => 'required|numeric|min:0',
            'staff_price'   => 'required|numeric|min:0',
            'parent_price'  => 'required|numeric|min:0',
            'alumni_price'  => 'required|numeric|min:0',
            'setup_fee'     => 'required|numeric|min:0',
            'addon_price'   => 'required|numeric|min:0',
        ]);

        // 4. Find the specific plan
        $pricing = Pricing::where('plan_name', $validated['plan_name'])->firstOrFail();
        
        // 5. Update only the price fields (exclude plan_name from update)
        $pricing->update([
            'student_price' => $validated['student_price'],
            'teacher_price' => $validated['teacher_price'],
            'staff_price'   => $validated['staff_price'],
            'parent_price'  => $validated['parent_price'],
            'alumni_price'  => $validated['alumni_price'],
            'setup_fee'     => $validated['setup_fee'],
            'addon_price'   => $validated['addon_price'],
        ]);

        return redirect()
            ->route('superadmin.pricing.index', ['plan' => $validated['plan_name']])
            ->with('success', ucfirst($validated['plan_name']) . ' plan updated successfully.');
    }
}