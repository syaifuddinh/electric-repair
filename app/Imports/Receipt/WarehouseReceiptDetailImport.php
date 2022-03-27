<?php

namespace App\Imports\Receipt;

use App\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use DB;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class WarehouseReceiptDetailImport implements WithCalculatedFormulas
{
    use Importable;
    /**
    * @param Collection $collection
    */
    
    public function collection(Collection $collection)
    {
        return $collection;
    }

    //  public function model(array $row)
    // {
    //     return $row;
    // }
}
