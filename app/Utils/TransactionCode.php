<?php

namespace App\Utils;

use App\Model\CompanyNumbering;
use App\Model\TypeTransaction;
use Carbon\Carbon;

// use App\Entities\StockCard;

class TransactionCode
{
    protected $compay_id;
    protected $item_id;
    protected $type_transaction_id;
    protected $type_transaction_name;
    protected $date;
    protected $code;

    public function __construct($company_id, $type_transaction_name)
    {
        $this->compay_id = $company_id;
        //this->item_id = $item_id;
        $this->type_transaction_name = $type_transaction_name;
        $this->date = Carbon::now();
    }

    protected function romanic_number($format)
    {
        $table = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
        $return = '';

        $month = $this->date->format($format);
        while($month > 0)
        {
            foreach($table as $rom=>$arb)
            {
                if($month >= $arb)
                {
                    $month -= $arb;
                    $return .= $rom;
                    break;
                }
            }
        }

        return $return;
    }

    protected function date_format($format)
    {
        return $this->date->format($format);
    }

    protected function counter($value)
    {
        $last_value = $value->last_value;

        $angka = '0000';

        $counter = str_repeat("0", 4 - strlen($angka+$last_value)).($angka+$last_value);

        $value->last_value = $value->last_value + 1;
        $value->save();

        return $counter;
    }

    public function setCode()
    {
        $typetrx = TypeTransaction::where('slug', $this->type_transaction_name)->first();
        if (isset($typetrx)) {
          $trx_id = $typetrx->id;
        } else {
          $trx_id = 0;
        }
        $numbering = CompanyNumbering::where('company_id', $this->compay_id)
            ->where('type_transaction_id', $trx_id)
            ->orderBy('urut','ASC')->get();
        $code = "";

        foreach ($numbering as $value){
            $code .= $value->prefix;

            if($value->type == 'roman'){
                $code .= $this->romanic_number($value->format_data);
            }

            if($value->type == 'date'){
                $code .= $this->date_format($value->format_data);
            }

            if($value->type == 'counter'){
                $code .= $this->counter($value);
            }
        }

        $this->code = $code;


    }

    public function getCode()
    {
        return $this->code;
    }
}
