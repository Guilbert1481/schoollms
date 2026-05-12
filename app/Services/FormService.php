<?php

namespace App\Services;

class FormService
{
    protected static $fields = null;

    /*
    |--------------------------------------------------------------------------
    | Load All Fields (from multiple files)
    |--------------------------------------------------------------------------
    */
    public static function fields()
    {
        if (self::$fields !== null) {
            return self::$fields;
        }

        $fields = [];

        $path = config_path('forms/fields');

        foreach (glob($path . '/*.php') as $file) {

            $data = require $file;

            if (is_array($data)) {
                $fields = array_merge($fields, $data);
            }
        }

        self::$fields = $fields;

        return $fields;
    }

    /*
    |--------------------------------------------------------------------------
    | Load Sections
    |--------------------------------------------------------------------------
    */
    public static function sections()
    {
        return config('forms.sections');
    }

    /*
    |--------------------------------------------------------------------------
    | Load Forms
    |--------------------------------------------------------------------------
    */
    public static function forms()
    {
        return config('forms.forms');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Single Form
    |--------------------------------------------------------------------------
    */
    public static function form($formKey)
    {
        $forms = self::forms();
        return $forms[$formKey] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Layout
    |--------------------------------------------------------------------------
    */
    public static function layout($formKey)
    {
        $form = self::form($formKey);
        return $form['layout'] ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Fields Used In Form
    |--------------------------------------------------------------------------
    */
    public static function fieldsForForm($formKey)
    {
        $layout = self::layout($formKey);
        $allFields = self::fields();

        $usedFields = [];

        foreach ($layout as $section => $rows) {
            foreach ($rows as $row) {
                foreach ($row as $col) {
                    $fieldKey = $col['field'];

                    if (isset($allFields[$fieldKey])) {
                        $usedFields[$fieldKey] = $allFields[$fieldKey];
                    }
                }
            }
        }

        return $usedFields;
    }

    /*
    |--------------------------------------------------------------------------
    | Group Layout With Field Definitions
    |--------------------------------------------------------------------------
    */
    public static function buildForm($formKey)
    {
        $layout = self::layout($formKey);
        $sections = self::sections();
        $fields = self::fields();

        $form = [];

        foreach ($layout as $sectionKey => $rows) {

            $sectionTitle = $sections[$sectionKey] ?? $sectionKey;

            $form[$sectionKey] = [
                'title' => $sectionTitle,
                'rows' => []
            ];

            foreach ($rows as $row) {

                $rowFields = [];

                foreach ($row as $col) {
                    $fieldKey = $col['field'];

                    if (!isset($fields[$fieldKey])) {
                        continue;
                    }

                    $field = $fields[$fieldKey];
                    $field['col_span'] = $col['col_span'] ?? 1;
                    $field['name'] = $fieldKey;

                    $rowFields[] = $field;
                }

                $form[$sectionKey]['rows'][] = $rowFields;
            }
        }

        return $form;
    }
}