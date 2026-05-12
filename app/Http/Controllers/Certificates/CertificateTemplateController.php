<?php

namespace App\Http\Controllers\Certificates;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class CertificateTemplateController extends Controller
{
    private function persistAsset(?string $value, string $prefix): ?string
    {
        if (!$value) {
            return null;
        }

        if (!str_starts_with($value, 'data:image/')) {
            return $value;
        }

        if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $value, $matches)) {
            return $value;
        }

        $extension = strtolower($matches[1] ?? 'png');
        $payload = base64_decode($matches[2], true);

        if ($payload === false) {
            return null;
        }

        $fileName = $prefix.'-'.Str::uuid().'.'.$extension;
        $path = 'certificates/assets/'.$fileName;

        Storage::disk('public')->put($path, $payload);

        return Storage::url($path);
    }


public function index()
{
    $config = config('tables.certificate.certificate_templates');

    $columns = $config['columns'];
    $formColumns = $config['form'];
    $labels = $config['labels'];

    $data = \App\Models\CertificateTemplate::get();

    return view('school.settings.master-data.certificates.index', compact(
        'columns',
        'formColumns',
        'labels',
        'data'
    ));
}

    /**
     * STORE (CREATE TEMPLATE)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'certificate_type' => 'required|in:internal,external',
            'category' => 'required|in:training,pageant,quiz,participation,program,other',
            'training_type_id' => 'nullable|exists:training_types,id',
        ]);

        CertificateTemplate::create([
            'name' => $request->name,
            'certificate_type' => $request->certificate_type,
            'category' => $request->category,
            'training_type_id' => $request->category === 'training' ? $request->training_type_id : null,
            'orientation' => 'landscape',
            'paper_size' => 'a4',
        ]);

        return redirect()
            ->route('school.settings.master-data.certificates.index')
            ->with('success', 'Certificate template created successfully.');
    }

    /**
     * UPDATE TEMPLATE
     */
    public function update(Request $request, $id)
    {
        $template = CertificateTemplate::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'certificate_type' => 'required|in:internal,external',
            'category' => 'required|in:training,pageant,quiz,participation,program,other',
            'training_type_id' => 'nullable|exists:training_types,id',
        ]);

        $template->update([
            'name' => $request->name,
            'certificate_type' => $request->certificate_type,
            'category' => $request->category,
            'training_type_id' => $request->category === 'training' ? $request->training_type_id : null,
        ]);

        return redirect()
            ->route('school.settings.master-data.certificates.index')
            ->with('success', 'Template updated successfully.');
    }

    /**
     * DELETE TEMPLATE
     */
    public function destroy($id)
    {
        $template = CertificateTemplate::findOrFail($id);
        $template->delete();

        return redirect()
            ->route('school.settings.master-data.certificates.index')
            ->with('success', 'Template deleted successfully.');
    }

    /**
     * BUILDER PAGE (optional - keep your existing logic if any)
     */
    public function builder($id = null)
{
    $template = null;

    if (!$id) {
        $id = request()->query('id');
    }

    if (!$id) {
        foreach (array_keys(request()->query()) as $queryKey) {
            if (is_numeric($queryKey)) {
                $id = (int) $queryKey;
                break;
            }
        }
    }

    if ($id) {
        $template = CertificateTemplate::find($id);
    }

    return view('school.settings.master-data.certificates.builder', compact('template'));
}

    /**
     * EDIT BUILDER
     */
    public function edit($id)
    {
        $template = CertificateTemplate::findOrFail($id);
        return view('school.settings.master-data.certificates.builder', compact('template'));
    }

    /**
     * SAVE BUILDER (JSON/HTML layout)
     */
    public function saveTemplate(Request $request)
    {
        $data = $request->validate([
            'template_id' => 'required|integer|exists:certificate_templates,id',
            'layout_json' => 'nullable|array',
            'orientation' => 'nullable|in:landscape,portrait',
            'paper_size' => 'nullable|in:a4,a5,letter,legal',
            'background_image' => 'nullable|string',
            'logo' => 'nullable|string',
        ]);

        $template = CertificateTemplate::findOrFail($data['template_id']);
        $elements = $data['layout_json'] ?? [];

        $template->layout_json = $elements;
        $template->elements = $elements;
        $template->orientation = $data['orientation'] ?? $template->orientation ?? 'landscape';
        $template->paper_size = $data['paper_size'] ?? $template->paper_size ?? 'a4';

        // Respect explicit null from the client so users can remove saved assets.
        if (array_key_exists('background_image', $data)) {
            $template->background_image = $this->persistAsset($data['background_image'], 'background');
        }

        if (array_key_exists('logo', $data)) {
            $template->logo = $this->persistAsset($data['logo'], 'logo');
        }

        $template->save();

        return response()->json([
            'success' => true,
            'template_id' => $template->id,
            'orientation' => $template->orientation,
            'paper_size' => $template->paper_size,
            'background_image' => $template->background_image,
            'logo' => $template->logo,
        ]);
    }

    /**
     * PREVIEW TEMPLATE
     */
    public function preview($id)
    {
        $template = CertificateTemplate::findOrFail($id);

        return view('school.settings.master-data.certificates.template', [
            'template' => $template,
            'trainee' => (object)[
                'profile' => (object)['name' => 'Juan Dela Cruz']
            ],
            'course' => (object)['title' => 'Sample Training'],
            'certificateNumber' => 'CERT-0001'
        ]);
    }

    /**
     * SAMPLE PREVIEW (no DB)
     */
    public function previewSample()
    {
        return view('school.settings.master-data.certificates.template', [
            'template' => null,
            'trainee' => (object)[
                'profile' => (object)['name' => 'Sample Trainee']
            ],
            'course' => (object)['title' => 'Sample Course'],
            'certificateNumber' => 'SAMPLE-0001'
        ]);
    }

    
}
