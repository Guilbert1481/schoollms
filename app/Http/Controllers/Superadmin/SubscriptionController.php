<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    // 1. ADD THIS: List all subscriptions
    public function index()
    {
        $schools = School::with('modules')->get();
        return view('superadmin.subscriptions.index', compact('schools'));
    }

    // 2. ADD THIS: Show pricing plans
    public function plans()
    {
        return view('superadmin.subscriptions.plans');
    }

    // 3. YOUR EXISTING UPDATE METHOD
    public function update(Request $request)
    {
        // ... (keep your existing update code here)
    }

    // 4. ADD THIS: Handle Invoices
    public function invoice($id)
    {
        $school = School::findOrFail($id);
        return view('superadmin.subscriptions.invoice', compact('school'));
    }

    // 5. ADD THIS: Handle Cancellations
    public function cancel($id)
    {
        $school = School::findOrFail($id);
        $school->update(['plan_name' => 'basic']); // Downgrade instead of delete
        return back()->with('success', 'Subscription cancelled.');
    }
}