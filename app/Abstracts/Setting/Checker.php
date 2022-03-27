<?php

namespace App\Abstracts\Setting;

use DB;
use Exception;
use App\Abstracts\Setting\Company;
use App\Abstracts\Inventory\Warehouse;
use App\Abstracts\Inventory\Item;
use App\Abstracts\Marketing\PriceList;
use App\Abstracts\Marketing\QuotationDetail;
use App\Abstracts\Depo\GateInContainer;
use App\Abstracts\Contact;

class Checker 
{
    /*
      Date : 29-08-2020
      Description : Validasi apakah nilai sama dengan nol atau bukan
      Developer : Didin
      Status : Create
    */
    public static function validateIsZero($qty) {
        $qty = $qty ?? 0;
        if($qty == 0) {
            throw new Exception('Qty harus lebih dari 0');
        }
    }

    /*
      Date : 29-08-2020
      Description : Validasi tanggal
      Developer : Didin
      Status : Create
    */
    public static function checkDate($value) {
        if(!$value) {
            throw new Exception('Date paramater is required');
        }
        $date1 = '/\d{2}-\d{2}-\d{4}/';
        $date2 = '/\d{4}-\d{2}-\d{2}/';

        if(!preg_match($date1, $value)) {        
            if(!preg_match($date2, $value)) {
                throw new Exception('Date not valid');
            }
        }

    }

    /*
      Date : 16-03-2021
      Description : Validasi branch / company
      Developer : Didin
      Status : Create
    */
    public static function checkCompany($value) {
        if(!$value) {
            throw new Exception('Company / branch is required');
        }
        Company::validate($value);
    }

    /*
      Date : 16-03-2021
      Description : Validasi item
      Developer : Didin
      Status : Create
    */
    public static function checkItem($value) {
        if(!$value) {
            throw new Exception('Item is required');
        }
        Item::validate($value);
    }

    /*
      Date : 16-03-2021
      Description : Validasi item
      Developer : Didin
      Status : Create
    */
    public static function checkGateInContainer($value) {
        if(!$value) {
            throw new Exception('Gate in container is required');
        }
        GateInContainer::validate($value);
    }

    /*
      Date : 16-03-2021
      Description : Validasi keberadaan data contact
      Developer : Didin
      Status : Create
    */
    public static function checkContact($value) {
        if(!$value) {
            throw new Exception('Contact is required');
        }
        Contact::validate($value);
    }

    /*
      Date : 19-03-2021
      Description : Validasi warehouse
      Developer : Didin
      Status : Create
    */
    public static function checkWarehouse($value) {
        if(!$value) {
            throw new Exception('Warehouse is required');
        }
        Warehouse::validate($value);
    }

    /*
      Date : 19-03-2021
      Description : Validasi quotation detail
      Developer : Didin
      Status : Create
    */
    public static function checkQuotationDetail($value) {
        QuotationDetail::validate($value);
    }

    /*
      Date : 19-03-2021
      Description : Validasi price list
      Developer : Didin
      Status : Create
    */
    public static function checkPriceList($value) {
        PriceList::validate($value);
    }

    /*
      Date : 19-03-2021
      Description : Validasi pengenaan harga
      Developer : Didin
      Status : Create
      Description : 1 = Per Volume, 2 = Per Tonase, 3 = Per Item, 4 = Borongan
    */
    public static function checkImposition($value) {
        if($value < 1 || $value > 4) {
            throw new Exception('Imposition not found');
        }
    }

    /*
      Date : 19-03-2021
      Description : Validasi type urutan
      Developer : Didin
      Status : Create
    */
    public static function checkOrderType($value) {
        $value = strtolower($value);
        if($value != 'asc' && $value != 'desc') {
            throw new Exception('Order type not found');
        }
    }


}
