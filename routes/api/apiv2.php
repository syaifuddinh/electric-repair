<?php


Route::get('list_customer','Contact\ContactController@list_customer');
Route::get('list_warehouse','OperationalWarehouse\SettingController@list_warehouse');
Route::get('list_company','OperationalWarehouse\SettingController@list_company');
Route::get('list_rack','OperationalWarehouse\SettingController@list_rack');
Route::get('list_vehicle','OperationalWarehouse\SettingController@list_vehicle');
Route::get('list_staff_gudang','OperationalWarehouse\SettingController@list_staff_gudang');
Route::get('list_cost_type','OperationalWarehouse\SettingController@list_cost_type');
Route::get('list_kpi_status','OperationalWarehouse\SettingController@list_kpi_status');

Route::group([
	'prefix' => 'operational',
	'middleware' => 'compare_date'
], function(){

	Route::get('job_order_datatable','OperationalApiController@job_order_datatable');
});

Route::group([
	'prefix' => 'operational',
	'namespace' => 'Operational',
	'middleware' => 'checkExistingMaster'
], function(){
	Route::get('job_order/get_warehouse_items','JobOrderController@get_warehouse_items');
		Route::get('job_order/edit_cost/{id}','JobOrderController@edit_cost');
		Route::get('job_order/edit_cost/{id}','JobOrderController@edit_cost');
		Route::get('job_order/cari_wo/{id}','JobOrderController@cari_wo');
		Route::get('job_order/cari_address/{id}','JobOrderController@cari_address');
		Route::get('job_order/cari_item_kontrak/{id}','JobOrderController@cari_item_kontrak');
		Route::get('job_order/cari_price_list/{id}','JobOrderController@cari_price_list');
		Route::get('job_order/detail_kontrak/{id}','JobOrderController@detail_kontrak');
		Route::get('job_order/show_document/{id}','JobOrderController@show_document');
		Route::get('job_order/show_status/{id}','JobOrderController@show_status');
		Route::get('job_order/set_voyage/{id}','JobOrderController@set_voyage');
		Route::get('job_order/cari_container/{voy}','JobOrderController@cari_container');
		Route::post('job_order/add_armada/{id}','JobOrderController@add_armada');
		Route::post('job_order/add_item/{id}','JobOrderController@add_item');
		Route::post('job_order/add_item_warehouse/{id}','JobOrderController@add_item_warehouse')->middleware('checkExistingTTB');
		Route::post('job_order/add_cost/{id}','JobOrderController@add_cost');
		Route::post('job_order/add_receipt/{id}','JobOrderController@add_receipt');
		Route::post('job_order/add_status/{id}','JobOrderController@add_status');
		Route::post('job_order/upload_file/{id}','JobOrderController@upload_file');
		Route::post('job_order/store_submission/{id}','JobOrderController@store_submission');
		Route::post('job_order/store_voyage_vessel/{man}','JobOrderController@store_voyage_vessel');
		Route::post('job_order/store_revision/{id}','JobOrderController@store_revision');
		Route::post('job_order/send_notification','JobOrderController@send_notification');
		Route::post('job_order/store_archive','JobOrderController@store_archive');
		Route::put('job_order/update_status','JobOrderController@update_status');
		Route::post('job_order/change_service/{id}','JobOrderController@change_service');
		Route::post('job_order/ajukan_atasan','JobOrderController@ajukan_atasan');
		Route::post('job_order/approve_atasan','JobOrderController@approve_atasan');
		Route::post('job_order/reject_atasan','JobOrderController@reject_atasan');

		Route::post('job_order/cost_journal','JobOrderController@cost_journal');
		Route::post('job_order/submit_armada_lcl/{id}','JobOrderController@submit_armada_lcl');
		Route::delete('job_order/delete_item/{id}','JobOrderController@delete_item');
		Route::delete('job_order/delete_cost/{id}','JobOrderController@delete_cost');
		Route::delete('job_order/delete_status/{id}','JobOrderController@delete_status');
		Route::delete('job_order/delete_file/{id}','JobOrderController@delete_file');
		Route::delete('job_order/delete_armada/{id}','JobOrderController@delete_armada');	
});

Route::group([
	'middleware' => 'auth:api',
	'prefix' => 'inventory',
	'as' => 'inventory.',
	'namespace' => 'Inventory',
	'middleware' => 'checkExistingMaster'
], function(){
  // Item
	Route::get('item/surat_jalan','ItemController@surat_jalan');
	Route::get('item/rack','ItemController@rack');

	Route::get('item/cek_stok_warehouse','ItemController@cek_stok_warehouse');
});

Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'marketing',
		'middleware' => 'checkExistingMaster'
	], function(){
		Route::get('work_order_detail_datatable','MarketingApiController@work_order_detail_datatable');
	});

Route::group([
	'prefix' => 'operational_warehouse',
	'middleware' => 'compare_date'
], function(){
	Route::get('item_warehouse_datatable','OperationalWarehouseApiController@item_warehouse_datatable');
	Route::get('validasi_item_datatable/{id}','OperationalWarehouseApiController@validasi_item_datatable');
	Route::get('master_pallet_datatable','OperationalWarehouseApiController@master_pallet_datatable');
	Route::get('stocklist_datatable','OperationalWarehouseApiController@stocklist_datatable');
	Route::get('mutasi_transfer_datatable','OperationalWarehouseApiController@mutasi_transfer_datatable');
	Route::get('general_item_datatable','OperationalWarehouseApiController@general_item_datatable');
	Route::get('putaway_datatable','OperationalWarehouseApiController@putaway_datatable');
	Route::get('picking_datatable','OperationalWarehouseApiController@picking_datatable');
	Route::get('stok_opname_datatable','OperationalWarehouseApiController@stok_opname_datatable');
	Route::get('warehouse_receipt_datatable','OperationalWarehouseApiController@warehouse_receipt_datatable');
	Route::get('general_item_datatable','OperationalWarehouseApiController@general_item_datatable');

});

Route::group([
	'middleware' => 'auth:api',
	'prefix' => 'operational_warehouse',
	'as' => 'operational_warehouse.',
	'namespace' => 'OperationalWarehouse',
	'middleware' => 'checkExistingMaster'
], function(){
	Route::delete('receipt/delete_detail/{id}','ReceiptController@delete_detail');
	Route::post('receipt/store_detail/{id}','ReceiptController@store_detail');
	Route::put('receipt/update_detail/{id}','ReceiptController@update_detail');
	Route::get('receipt/print/{id}','ReceiptController@print');
	Route::post('receipt/approve/{id}','ReceiptController@approve');
	Route::post('receipt/update/{id}','ReceiptController@update');
	Route::resource('receipt','ReceiptController');

// Route handling
	Route::post('handling/store_vehicle_detail/{id}','HandlingController@store_vehicle_detail');
	Route::put('handling/update_vehicle_detail','HandlingController@update_vehicle_detail');
	Route::delete('handling/delete_vehicle_detail/{id}','HandlingController@delete_vehicle_detail');
	Route::post('handling/add_item/{id}','HandlingController@add_item');
// ===============================

// route stuffing
	Route::post('stuffing/store_vehicle_detail/{id}','StuffingController@store_vehicle_detail');
	Route::put('stuffing/update_vehicle_detail','StuffingController@update_vehicle_detail');
	Route::delete('stuffing/delete_vehicle_detail/{id}','StuffingController@delete_vehicle_detail');
	Route::post('stuffing/add_item/{id}','StuffingController@add_item');
	Route::resource('stuffing','StuffingController')->middleware('checkExistingTTB');
  // ===============================

	Route::resource('handling','HandlingController')->middleware('checkExistingTTB');
	Route::post('putaway/item_out/{id}','PutawayController@item_out');
	Route::post('putaway/item_in/{id}','PutawayController@item_in');
	Route::post('putaway/store_detail','PutawayController@store_detail');
	Route::post('putaway/delete_detail/{id}','PutawayController@delete_detail');
	Route::resource('putaway','PutawayController')->middleware('checkExistingTTB');
	Route::post('mutasi_transfer/item_out/{id}','MutasiTransferController@item_out');
	Route::post('mutasi_transfer/item_in/{id}','MutasiTransferController@item_in');
	Route::post('mutasi_transfer/store_detail','MutasiTransferController@store_detail');
	Route::post('mutasi_transfer/delete_detail/{id}','MutasiTransferController@delete_detail');
	Route::resource('mutasi_transfer','MutasiTransferController')->middleware('checkExistingTTB');
});