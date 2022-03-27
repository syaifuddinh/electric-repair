<?php

namespace App\Imports\JobOrder;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use DB;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;

class JobOrderDetailImport implements ToCollection
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
