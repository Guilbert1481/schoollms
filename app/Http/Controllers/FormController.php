<?php

namespace App\Http\Controllers;

use App\Services\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function save(Request $request, $formKey)
    {
        $fieldsConfig = FormService::fields();
        $formConfig = FormService::form($formKey);

        if (! $formConfig) {
            return back()->with('error', 'Form config not found.');
        }

        $schoolId = auth()->user()->school_id;

        $pivotData = [];
        $multipleData = [];

        /*
        |------------------------------------------------------------------
        | LOOP THROUGH FORM LAYOUT
        |------------------------------------------------------------------
        */
        foreach ($formConfig['layout'] as $section) {
            foreach ($section as $row) {
                foreach ($row as $item) {

                    $fieldKey = $item['field'];
                    $fieldConfig = $fieldsConfig[$fieldKey] ?? null;

                    if (! $fieldConfig) {
                        continue;
                    }

                    // Locked fields are read-only — skip saves even if submitted
                    if (! empty($fieldConfig['locked'])) {
                        continue;
                    }

                    /*
                    |------------------------------------------------------------------
                    | PIVOT TABLE
                    |------------------------------------------------------------------
                    */
                    if (! empty($fieldConfig['model']) &&
                        is_string($fieldConfig['model']) &&
                        Str::startsWith($fieldConfig['model'], 'pivot:')) {

                        $pivotTable = str_replace('pivot:', '', $fieldConfig['model']);
                        $pivotData[$pivotTable] = $request->$fieldKey ?? [];

                        continue;
                    }

                    /*
                    |------------------------------------------------------------------
                    | MULTIPLE RECORDS (ex: Banks)
                    |------------------------------------------------------------------
                    */
                    if (! empty($fieldConfig['multiple'])) {
                        $model = $fieldConfig['model'] ?? null;
                        $multipleData[$model][$fieldKey] = $request->$fieldKey;

                        continue;
                    }

                    /*
                    |------------------------------------------------------------------
                    | SAVE TO MODELS
                    |------------------------------------------------------------------
                    */
                    if (! empty($fieldConfig['save_to'])) {

                        foreach ($fieldConfig['save_to'] as $saveConfig) {

                            $modelClass = $saveConfig['model'] ?? null;
                            $column = $saveConfig['column'] ?? null;
                            $where = $saveConfig['where'] ?? [];

                            if (! $modelClass || ! $column) {
                                continue;
                            }

                            // Replace special values
                            foreach ($where as $key => $value) {
                                if ($value === 'auth_school_id') {
                                    $where[$key] = $schoolId;
                                }
                            }

                            // Default where
                            if (empty($where)) {
                                $where = ['school_id' => $schoolId];
                            }

                            $model = $modelClass::firstOrNew($where);

                            /*
                            |------------------------------------------------------------------
                            | FILE UPLOAD
                            |------------------------------------------------------------------
                            */
                            $uploadConfig = $fieldConfig['upload'] ?? [];
                            $disk = $uploadConfig['disk'] ?? 'public';

                            if ($request->hasFile($fieldKey)) {

                                $file = $request->file($fieldKey);

                                $path = $uploadConfig['path'] ?? $fieldKey;

                                // Replace {school_id}
                                $path = str_replace('{school_id}', $schoolId, $path);

                                // Remove the previously stored file so we don't orphan it.
                                if (! empty($model->$column)) {
                                    Storage::disk($disk)->delete($model->$column);
                                }

                                // Validate + re-encode (H3): branding assets are
                                // images (re-encoded to strip payloads) or PDFs.
                                // SecureUpload rejects SVG/HTML/executables and
                                // assigns a random, client-name-independent path.
                                $filePath = app(\App\Services\Uploads\SecureUpload::class)
                                    ->storeImageOrDocument($file, $path, $disk);

                                // Save FILE PATH to database
                                $model->$column = $filePath;
                            } elseif (($fieldConfig['type'] ?? null) === 'file'
                                    && $request->boolean('remove_'.$fieldKey)) {
                                // Explicit delete from the UI (× button): drop the file
                                // and clear the column.
                                if (! empty($model->$column)) {
                                    Storage::disk($disk)->delete($model->$column);
                                }
                                $model->$column = null;
                            } elseif (($fieldConfig['type'] ?? null) === 'file') {
                                // No new file and no removal — keep existing value.
                            } else {
                                $model->$column = $request->$fieldKey;
                            }

                            $model->save();
                        }
                    }
                }
            }
        }

        /*
        |------------------------------------------------------------------
        | SAVE MULTIPLE MODELS (ex: BANKS)
        |------------------------------------------------------------------
        */
        foreach ($multipleData as $modelClass => $fields) {

            if (! $modelClass) {
                continue;
            }

            $data = $fields;
            $data['school_id'] = $schoolId;

            $modelClass::create($data);
        }

        /*
        |------------------------------------------------------------------
        | SAVE PIVOT TABLES
        |------------------------------------------------------------------
        */
        foreach ($pivotData as $pivotTable => $values) {

            DB::table($pivotTable)
                ->where('school_id', $schoolId)
                ->delete();

            if (is_array($values)) {
                foreach ($values as $value) {
                    DB::table($pivotTable)->insert([
                        'school_id' => $schoolId,
                        'modality_id' => $value,
                    ]);
                }
            }
        }

        return back()->with('success', 'Form saved successfully.');
    }
}
