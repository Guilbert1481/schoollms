<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\FormService;

class FormController extends Controller
{
    public function save(Request $request, $formKey)
    {
        $fieldsConfig = FormService::fields();
        $formConfig   = FormService::form($formKey);

        if (!$formConfig) {
            return back()->with('error', 'Form config not found.');
        }

        $schoolId = auth()->user()->school_id;

        $pivotData     = [];
        $multipleData  = [];

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

                    if (!$fieldConfig) continue;

                    // Locked fields are read-only — skip saves even if submitted
                    if (!empty($fieldConfig['locked'])) continue;

                    /*
                    |------------------------------------------------------------------
                    | PIVOT TABLE
                    |------------------------------------------------------------------
                    */
                    if (!empty($fieldConfig['model']) &&
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
                    if (!empty($fieldConfig['multiple'])) {
                        $model = $fieldConfig['model'] ?? null;
                        $multipleData[$model][$fieldKey] = $request->$fieldKey;
                        continue;
                    }

                    /*
                    |------------------------------------------------------------------
                    | SAVE TO MODELS
                    |------------------------------------------------------------------
                    */
                    if (!empty($fieldConfig['save_to'])) {

                        foreach ($fieldConfig['save_to'] as $saveConfig) {

                            $modelClass = $saveConfig['model'] ?? null;
                            $column     = $saveConfig['column'] ?? null;
                            $where      = $saveConfig['where'] ?? [];

                            if (!$modelClass || !$column) continue;

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
                            if ($request->hasFile($fieldKey)) {

                                $file = $request->file($fieldKey);

                                $extension = $file->getClientOriginalExtension();
                                $filename = $fieldKey . '_' . $schoolId . '_' . time() . '.' . $extension;

                                $uploadConfig = $fieldConfig['upload'] ?? [];
                                $disk = $uploadConfig['disk'] ?? 'public';
                                $path = $uploadConfig['path'] ?? $fieldKey;

                                // Replace {school_id}
                                $path = str_replace('{school_id}', $schoolId, $path);

                                // Store file
                                $filePath = $file->storeAs($path, $filename, $disk);

                                // Save FILE PATH to database
                                $model->$column = $filePath;
                            }
                            elseif (($fieldConfig['type'] ?? null) === 'file') {
                                // No new file uploaded — keep existing value, do not overwrite.
                            }
                            else {
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

            if (!$modelClass) continue;

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
                        'modality_id' => $value
                    ]);
                }
            }
        }

        return back()->with('success', 'Form saved successfully.');
    }
}