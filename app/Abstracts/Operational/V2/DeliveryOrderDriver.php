<?php

namespace App\Abstracts\Operational\V2;

use DB;
use Carbon\Carbon;
use Exception;
use App\Model\delivery_order_driver AS M;
use App\Abstracts\Setting\JobStatus;
use App\Abstracts\Operational\DeliveryOrderStatusLog;
use App\Abstracts\Operational\DeliveryOrderDriver AS DeliveryOrderDriverV1;
use App\Abstracts\Operational\DeliveryOrderOngoingJob;


class DeliveryOrderDriver extends DeliveryOrderDriverV1
{
    /*
      Date : 19-02-2021
      Description : Menampilkan detail surat jalan driver
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = parent::show($id);
        $data = [];
        $data['id'] = $dt->id;
        $data['code'] = $dt->code;
        $data['route'] = $dt->route;
        $data['job_assign_time'] = $dt->pick_date;
        $data['status_slug'] = $dt->status_slug;
        if($dt->status_slug == 'itemLoaded') {
            $data['must_load_item'] = true;
        } else {
            $data['must_load_item'] = false;
        }

        if($dt->status_slug == 'dischargeStarted') {
            $data['must_discharge_item'] = true;
        } else {
            $data['must_discharge_item'] = false;
        }

        if($dt->status_slug == 'startedByDriver') {
            $data['must_choose_receiver'] = true;
        } else {
            $data['must_choose_receiver'] = false;
        }

        $data['status'] = $dt->status_name;
        $data['next_status'] = $dt->next_status_name;
        $data['must_scan_barcode'] = false;

        $must_upload_file = false;
        $jobStatus = JobStatus::show($dt->status);
        if($jobStatus->must_upload_file == 1) {
            $must_upload_file = true;
        }
        $data['must_upload_file'] = $must_upload_file;
        $data['to'] = parent::showReceiver($id);
        $data['from'] = parent::showOrigin($id);
        $data = (object) $data;
        return $data;
    }

    /*
      Date : 19-02-2021
      Description : Update ke status selanjutnya
      Developer : Didin
      Status : Create
    */
    public static function updateToNextStatus($id, $created_by, $job_order_id = null) {
        $dt = self::show($id);
        parent::updateToNextStatus($id, $created_by);
        if($dt->must_choose_receiver) {
            if(!$job_order_id) {
                throw new Exception('Job order is required');
            }
            DeliveryOrderOngoingJob::store($id, $job_order_id);
        }
    }
}
