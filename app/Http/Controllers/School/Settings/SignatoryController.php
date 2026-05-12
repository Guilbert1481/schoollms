<?php

namespace App\Http\Controllers\School\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Document;
use App\Models\Signatory;
use App\Models\DocumentSignatory;
use App\Models\Role;

class SignatoryController extends Controller
{
    public function indexSignatory()
    {
        $documents = Document::all();
        $signatories = Signatory::where('is_active', 1)->get();
        $roles = Role::all(); // ADD THIS

        return view('admin.assignments.index', [
            'documents' => $documents,
            'signatories' => $signatories,
            'roles' => $roles // ADD THIS
        ]);
    }

    public function storeDocument(Request $request)
    {
        $documentId = $request->document_type;
        $signatories = $request->signatories;
        $orders = $request->signing_order;

        // Convert "Teacher=1, Dean=2"
        $orderArray = [];
        $pairs = explode(',', $orders);

        foreach ($pairs as $pair) {
            [$name, $order] = explode('=', trim($pair));
            $orderArray[trim($name)] = trim($order);
        }

        foreach ($signatories as $signatoryId) {
            $signatory = Signatory::find($signatoryId);

            DocumentSignatory::create([
                'document_id' => $documentId,
                'signatory_id' => $signatoryId,
                'sign_order' => $orderArray[$signatory->name] ?? 1
            ]);
        }

        return redirect()->back()->with('success', 'Signatories assigned.');
    }

    public function storeSignatory(Request $request)
    {
        Signatory::create([
            'name' => $request->name,
            'position' => $request->position,
            'role_id' => $request->role_id,
            'is_active' => 1
        ]);

        return back()->with('success', 'Signatory added.');
    }
}