<?php

namespace App\Exports\Receipt;

use App\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ImportItem implements FromView
{
    public function __construct($columns) {
        $this->columns = $columns;
    }

    public function view(): View
    {
        $params = [];
        $params['columns'] = $this->columns;
        return view('export.receipt.import_item', $params);
    }
}
