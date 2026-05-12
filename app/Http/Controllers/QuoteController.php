<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index(Request $request)
{
    $search = trim($request->input('search'));

    // Build quotes list (searchable)
    $quotes = Quote::query()
        ->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('theme', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        })
        ->latest();

    // Get active quote for banner (unchanged)
    $bannerQuote = Quote::where('is_active', true)
        ->orderBy('activated_at', 'desc')
        ->first();

    $columns = [
        ['key' => 'theme',   'label' => 'Category'],
        ['key' => 'author',  'label' => 'Author'],
        ['key' => 'content', 'label' => 'Content'],
    ];

    return view('admin.quotes.index', [
        'quotes'      => $quotes->get(),
        'columns'     => $columns,
        'bannerQuote' => $bannerQuote,
    ]);
}

    /**
     * Save new quotes and activate their theme.
     */
    public function store(Request $request)
    {
        if (!$request->has('quotes')) {
            dd('No quotes received');
        }

        Quote::where('is_active', true)->update([
            'is_active' => false,
            'activated_at' => null,
        ]);

        foreach ($request->quotes as $quoteData) {
            if (!empty($quoteData['content'])) {
                Quote::create([
                    'content' => $quoteData['content'],
                    'author' => $quoteData['author'] ?? null,
                    'theme' => $quoteData['theme'],
                    'display_duration' => 1,
                    'is_active' => true,
                    'activated_at' => now(),
                ]);
            }
        }

        return back()->with('success', 'Quotes saved and activated!');
    }

    /**
     * Updates which display is currently "Active".
     */
    public function updateDisplay(Request $request)
    {
        $selectedDisplay = $request->display;
        $duration = max(1, (int) ($request->display_duration ?? 1));

        Quote::where('is_active', true)->update([
            'is_active' => false,
            'activated_at' => null
        ]);

        // Stamp all quotes of the selected theme with the same activation timestamp
        // so the daily rotation cycles consistently across the requested duration.
        Quote::where('theme', $selectedDisplay)->update([
            'is_active' => true,
            'display_duration' => $duration,
            'activated_at' => now()
        ]);

        return back()->with('success', "Dashboard display changed to $selectedDisplay for $duration day(s)!");
    }

    public static function getDailyQuote()
    {
        return Quote::where('is_active', true)
            ->orderBy('activated_at', 'desc')
            ->first();
    }

    public function edit(Quote $quote)
    {
        return redirect()->route('admin.quotes.index', ['edit' => $quote->id]);
    }

    public function update(Request $request, Quote $quote)
    {
        $request->validate([
            'theme' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
        $quote->update($request->only('theme', 'author', 'content'));
        return redirect()->route('admin.quotes.index')->with('success', 'Quote updated!');
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();
        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote deleted successfully.');
    }
}