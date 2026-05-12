<?php

namespace App\Http\Controllers\School\Settings\Organization;

use App\Http\Controllers\Controller;

class OrganizationController extends Controller
{
    public function indexOrganization()
    {
        return view('school.settings.master-data.organization.index');
    }
}