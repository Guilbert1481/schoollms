<?php

namespace App\Http\Controllers\School\Settings\Organization;

use App\Http\Controllers\Base\BaseCrudController;
use Illuminate\Http\Request;

class OfficeController extends BaseCrudController
{
    public function indexOffices()
    {
        return view(
            'school.settings.master-data.organization.office.offices',
            $this->loadTableData('offices')
        );
    }

    public function storeOffices(Request $request)
    {
        return $this->storeRecord('offices', $request);
    }

    public function updateOffices(Request $request, $id)
    {
        return $this->updateRecord('offices', $request, $id);
    }

    public function destroyOffices($id)
    {
        return $this->deleteRecord('offices', $id);
    }
}
