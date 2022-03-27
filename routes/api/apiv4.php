<?php

Route::get('list_customer','Contact\ContactController@listCustomer')->middleware('keywordValidation');
Route::get('list_warehouse','OperationalWarehouse\SettingController@list_warehouse')->middleware('keywordValidation');
Route::get('list_company','OperationalWarehouse\SettingController@list_company')->middleware('keywordValidation');
Route::get('list_rack','OperationalWarehouse\SettingController@list_rack')->middleware('keywordValidation');
Route::get('list_storage_type','OperationalWarehouse\SettingController@listStorageType')->middleware('keywordValidation');
Route::get('list_vehicle','OperationalWarehouse\SettingController@list_vehicle')->middleware('keywordValidation');
Route::get('list_staff_gudang','OperationalWarehouse\SettingController@list_staff_gudang')->middleware('keywordValidation');
Route::get('list_cost_type','OperationalWarehouse\SettingController@list_cost_type')->middleware('keywordValidation');
Route::get('list_kpi_status','OperationalWarehouse\SettingController@list_kpi_status')->middleware('keywordValidation');

Route::get('notification/get_notif','NotificationController@get_notif');
Route::put('notification/view_notif','NotificationController@view_notif');

Route::group([
	'prefix' => 'operational',
	'middleware' => 'compare_date'
], function(){

	Route::get('job_order_datatable','OperationalApiController@job_order_datatable');
    Route::get('manifest_ftl_datatable','OperationalApiController@manifest_ftl_datatable');
    Route::get('stuffing_datatable','OperationalApiController@stuffing_datatable');
    Route::get('crossdocking_datatable','OperationalApiController@crossdocking_datatable');
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

        Route::get('stuffing/{id}/detail', 'StuffingController@indexDetail');
        Route::post('stuffing/{id}/detail', 'StuffingController@storeDetail');
        Route::delete('stuffing/{id}/detail/{manifest_detail_id}', 'StuffingController@destroyDetail');
        Route::apiResource('stuffing', 'StuffingController');

        Route::get('crossdocking/{id}/detail', 'CrossdockingController@indexDetail');
        Route::post('crossdocking/{id}/detail', 'CrossdockingController@storeDetail');
        Route::delete('crossdocking/{id}/detail/{manifest_detail_id}', 'CrossdockingController@destroyDetail');
        Route::apiResource('crossdocking', 'CrossdockingController');
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
    Route::get('item/barcode/{barcode}', 'ItemController@showByBarcode');

	Route::get('picking_order_datatable','PickingOrderController@picking_order_datatable');
	Route::post('picking_order/realisation/{id}','PickingOrderController@realisation');
	Route::post('picking_order/cancel_realisation/{id}','PickingOrderController@cancel_realisation');
	Route::post('picking_order/posting/{id}','PickingOrderController@posting');
	Route::post('picking_order/update_qty','PickingOrderController@update_qty');
	Route::resource('picking_order','PickingOrderController');
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

    Route::get('item_in_picking_datatable','OperationalWarehouseApiController@item_in_picking_datatable');

	Route::get('validasi_item_datatable/{id}','OperationalWarehouseApiController@validasi_item_datatable');
	Route::get('master_pallet_datatable','OperationalWarehouseApiController@master_pallet_datatable');
	Route::get('stocklist_datatable','OperationalWarehouseApiController@stocklist_datatable');
	Route::get('customer_stock_datatable','OperationalWarehouseApiController@customerStockDatatable');
	Route::get('mutasi_transfer_datatable','OperationalWarehouseApiController@mutasi_transfer_datatable');
	Route::get('general_item_datatable','OperationalWarehouseApiController@general_item_datatable');
	Route::get('putaway_datatable','OperationalWarehouseApiController@putaway_datatable');
	Route::get('picking_datatable','OperationalWarehouseApiController@picking_datatable');
	Route::get('stok_opname_datatable','OperationalWarehouseApiController@stok_opname_datatable');
	Route::get('warehouse_receipt_datatable','OperationalWarehouseApiController@warehouse_receipt_datatable');
	Route::get('warehouse_stock_datatable','OperationalWarehouseApiController@warehouseStockDatatable');
	Route::get('general_item_datatable','OperationalWarehouseApiController@general_item_datatable');

	Route::get('warehouse_datatable','OperationalWarehouseApiController@warehouseDatatable');

});

Route::group([
	'middleware' => 'auth:api',
	'prefix' => 'operational_warehouse',
	'as' => 'operational_warehouse.',
	'namespace' => 'OperationalWarehouse'
], function(){
	Route::get('item/{item_id}/stock', 'ItemController@showStock');

    Route::post('picking/store_detail','PickingController@store_detail');
    Route::put('picking/{id}/approve','PickingController@approve');
    Route::resource('picking','PickingController');
});

Route::group([
	'middleware' => 'auth:api',
	'prefix' => 'operational_warehouse',
	'as' => 'operational_warehouse.',
	'namespace' => 'OperationalWarehouse',
	'middleware' => 'checkExistingMaster'
], function(){

    Route::get('packaging/{id}/new_item','PackagingController@showNewItem');
    Route::get('packaging/{id}/old_item','PackagingController@showOldItem');
    Route::get('packaging/{id}/old_item','PackagingController@showOldItem');
    Route::put('packaging/{id}/approve','PackagingController@approve');
    Route::resource('packaging','PackagingController');

	Route::delete('receipt/delete_detail/{id}','ReceiptController@delete_detail');
	Route::post('receipt/{id}/detail','ReceiptController@store_detail');
	Route::put('receipt/update_detail/{id}','ReceiptController@update_detail');
	Route::get('receipt/print/{id}','ReceiptController@print');

    Route::get('receipt/barcode','ReceiptController@scanBarcode');

	Route::post('receipt/{id}/attachment','ReceiptController@storeAttachment');
	Route::delete('receipt/{receipt_id}/attachment/{delivery_order_photo_id}','ReceiptController@destroyAttachment');


	Route::post('receipt/approve/{id}','ReceiptController@approve');
	Route::post('receipt/update/{id}','ReceiptController@update');
	Route::get('receipt/{id}/item','ReceiptController@item');
	Route::post('receipt/alternative','ReceiptController@store_alternative');
	Route::resource('receipt','ReceiptController');

// Route handling
	Route::post('handling/store_vehicle_detail/{id}','HandlingController@store_vehicle_detail');
	Route::put('handling/update_vehicle_detail','HandlingController@update_vehicle_detail');
	Route::delete('handling/delete_vehicle_detail/{id}','HandlingController@delete_vehicle_detail');
	Route::post('handling/add_item/{id}','HandlingController@add_item');
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

// route dashboard
	Route::get('dashboard', 'SettingController@dashboard');

// route setting
	Route::get('setting/warehouse/amount', 'SettingController@warehouse_amount');
	Route::post('setting/warehouse/rack', 'SettingController@add_rack_warehouse');
	Route::get('setting/warehouse/{id}/rack', 'SettingController@get_rack_warehouse');
	Route::get('setting/warehouse/rack/{id_rack}', 'SettingController@show_rack_warehouse');
	Route::put('setting/warehouse/rack/{id}', 'SettingController@update_rack_warehouse');
	Route::delete('setting/warehouse/rack/{id}', 'SettingController@delete_rack_warehouse');
	Route::get('setting/warehouse/{id}', 'SettingController@detail_warehouse');
	Route::get('setting/operator/amount', 'SettingController@operator_amount');

    Route::put('stok_opname/{id}/approve','StokOpnameWarehouseController@approve');
    Route::resource('stok_opname','StokOpnameWarehouseController');
});

Route::group([
	'prefix' => 'contact',
	'namespace' => 'Contact'
], function(){

	Route::get('contact/{id}','ContactController@show_contact');
	Route::get('contact_datatable','ContactApiController@contactDatatable');
});

Route::group([
    'prefix' => 'setting',
    'namespace' => 'Setting'
], function(){
    Route::get('type_transaction/{id}','TypeTransactionController@show');
    Route::resource('vehicle_type','VehicleTypeController');

    Route::get('route/cost/{id}','RouteController@cost');
    Route::post('route/store_cost/{id?}','RouteController@store_cost');
    Route::post('route/store_detail_cost/{id}','RouteController@store_detail_cost');
    Route::delete('route/delete_detail_cost/{id}','RouteController@delete_detail_cost');
    Route::delete('route/delete_cost/{id}','RouteController@delete_cost');
    Route::resource('route','RouteController');
});

Route::group([
    'prefix' => 'inventory',
    'namespace' => 'Inventory'
], function(){
    Route::get('receipt_type/slug/{slug}','ReceiptTypeController@showBySlug');
    Route::resource('receipt_type','ReceiptTypeController');
    Route::resource('purchase_order','PurchaseOrderController');
});

Route::group([
  'prefix' => 'vehicle',
  'namespace' => 'Vehicle',
], function(){
    Route::resource('vehicle','VehicleController');
});