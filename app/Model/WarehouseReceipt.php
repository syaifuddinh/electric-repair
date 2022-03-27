<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class WarehouseReceipt extends Model
{
	protected $guarded = ['id'];

	public static function boot() {
		parent::boot();

		static::creating(function(WarehouseReceipt $warehouseReceipt) {
			if($warehouseReceipt->ttd != null) {
				$receive_date = $warehouseReceipt->receive_date;
				$warehouseReceipt->stripping_done = date('Y-m-d H:i:s');
				$warehouseReceipt->receive_date = $receive_date;
			}
		});

		static::updating(function(WarehouseReceipt $warehouseReceipt) {
			if($warehouseReceipt->ttd != null) {
				if($warehouseReceipt->stripping_done == null) {
					$receive_date = $warehouseReceipt->receive_date;
					$warehouseReceipt->stripping_done = date('Y-m-d H:i:s');
					$warehouseReceipt->receive_date = $receive_date;
				}
			}
		});
	}

	public function customer()
	{
		return $this->belongsTo('App\Model\Contact','customer_id','id');
	}
	public function company()
	{
		return $this->belongsTo('App\Model\Company','company_id','id');
	}
	public function warehouse()
	{
		return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
	}
	public function sender()
	{
		return $this->belongsTo('App\Model\Contact','sender_id','id');
	}
	public function collectible()
	{
		return $this->belongsTo('App\Model\Contact','collectible_id','id');
	}
	public function receiver()
	{
		return $this->belongsTo('App\Model\Contact','receiver_id','id');
	}
	public function staff()
	{
		return $this->belongsTo('App\User','warehouse_staff_id','id');
	}
	public function warehouse_staff()
	{
		return $this->belongsTo('App\User','warehouse_staff_id','id');
	}
	public function statuses()
	{
		// $statuses = [
		// 	[
		// 		'id' => 0,
		// 		'name' => 'Draft'
		// 	],
		// 	[
		// 		'id' => 1,
		// 		'name' => 'Disetujui'
		// 	]
		// ];
		// // print_r($this);
		// // die('');
		// return (object) $statuses[$this->select('status')];
		return "ABC";
	}
}
