<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Finance\Concerns\BuildsInvoiceList;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    use BuildsInvoiceList;

    public function index(Request $request)
    {
        // The Billing page hosts two tabs: Invoices (the list) and Billing Run.
        return view('finance.billing', $this->invoiceListData($request));
    }
}
