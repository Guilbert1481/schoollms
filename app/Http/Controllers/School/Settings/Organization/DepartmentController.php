<?php

namespace App\Http\Controllers\School\Settings\Organization;

use App\Http\Controllers\Base\BaseCrudController;
use Illuminate\Http\Request;

class DepartmentController extends BaseCrudController
{
    public function indexDepartments()
    {
        $data = $this->loadTableData('departments');

        return view(
            'school.settings.master-data.organization.department.departments',
            $data
        );
    }

    public function indexAssignmentsDepartments()
    {
        $data = $this->loadTableData('departments');

        return view('admin.assignments.index', $data);
    }

    public function storeDepartments(Request $request)
    {
        return $this->storeRecord('departments', $request);
    }

    public function updateDepartments(Request $request, $id)
    {
        return $this->updateRecord('departments', $request, $id);
    }

    public function destroyDepartments($id)
    {
        return $this->deleteRecord('departments', $id);
    }
}