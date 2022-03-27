<?php

use Illuminate\Http\Request;
// api php
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
	return $request->user();
});

Route::group([
	'namespace' => 'Api',
	'middleware' => 'auth:api'
], function(){
	Route::get('notification/get_notif','NotificationController@get_notif');
	Route::get('notification/detail_notification','NotificationController@detail_notification');
	Route::post('notification/view_notif','NotificationController@view_notif');

	Route::get('journal_notification/get_notif','JournalNotificationController@get_notif');
	Route::get('journal_notification/detail_notification','JournalNotificationController@detail_notification');
	Route::post('journal_notification/{journal_id}','JournalNotificationController@viewNotif');
});

Route::post('customer/login','Api\CustomerController@login');
Route::post('login','Api\CustomerController@login_user');
Route::post('logout','Api\CustomerController@logout');
Route::post('cek_token','Api\CustomerController@cek_token');
Route::group([
	'namespace' => 'Api',
	'middleware' => 'auth:api'
], function(){
	Route::get('customer/contract/{id}/item','CustomerController@show_item_contract');
	Route::get('customer/job_order/{id}','CustomerController@show_job_order');
	Route::get('customer/job_order/{id}/document','CustomerController@job_order_document');
	Route::get('customer/work_order/{id}','CustomerController@show_work_order');
	Route::get('customer/invoice/{id}','CustomerController@show_invoice');
	Route::get('customer/quotation/{id}','CustomerController@show_quotation');
	Route::get('customer/quotation/{id}/document','CustomerController@quotation_document');
	Route::post('customer/get_user','CustomerController@get_user');
	Route::post('customer/get_contact','CustomerController@get_contact');
	Route::post('customer/change_password','CustomerController@change_password');
	Route::get('customer/inquery/{id}','CustomerController@show_inquery');
	Route::post('customer/store_inquery','CustomerController@store_inquery');

	Route::post('customer/logout','CustomerController@logout');
	Route::post('customer/logout_customer_channel','CustomerController@logout_customer_channel');
});

Route::post('vendor/login','Api\VendorApiController@login');
Route::group([
	'namespace' => 'Api',
	'middleware' => 'auth:contact-api',
	'prefix' => 'vendor'
], function(){
	Route::get('add_vehicle','VendorApiController@add_vehicle');
	Route::get('show_vehicle/{id}','VendorApiController@show_vehicle');
	Route::get('show_driver/{id}','VendorApiController@show_driver');
	Route::get('vehicle_datatable','VendorApiController@vehicle_datatable');
	Route::get('driver_datatable','VendorApiController@driver_datatable');
	Route::get('vehicle_driver_datatable','VendorApiController@vehicle_driver_datatable');
	Route::get('order_datatable','VendorApiController@order_datatable');
	Route::get('vehicle_list','VendorApiController@vehicle_list');
	Route::get('detail_order/{id}','VendorApiController@detail_order');
	Route::get('get_drivers','VendorApiController@get_drivers');
	Route::get('get_vehicle_drivers','VendorApiController@get_vehicle_drivers');

	Route::post('store_vehicle/{id?}','VendorApiController@store_vehicle');
	Route::post('store_driver/{id?}','VendorApiController@store_driver');
	Route::post('store_vehicle_driver','VendorApiController@store_vehicle_driver');
	Route::post('assign_driver/{id}','VendorApiController@assign_driver');
	Route::post('reject_job/{id}','VendorApiController@reject_job');

	Route::delete('delete_vehicle_driver/{id}','VendorApiController@delete_vehicle_driver');
	Route::delete('delete_vehicle/{id}','VendorApiController@delete_vehicle');

	Route::get('cek_user',function(Request $request){
		return $request->user();
	});
});

Route::post('vendor_mobile/login','Api\VendorMobileController@login');
Route::group([
	'middleware' => ['auth:contact-api'],
	'namespace' => 'Api',
	'prefix' => 'vendor_mobile'
],function(){
	Route::get('get_user','VendorMobileController@get_user');
	Route::get('get_list_vehicle','VendorMobileController@get_list_vehicle');
	Route::post('post_vehicle','VendorMobileController@post_vehicle');
	Route::post('update_status_job','VendorMobileController@update_status_job');
	Route::post('reject_job','VendorMobileController@reject_job');
	Route::get('get_list_job','VendorMobileController@get_list_job');
	Route::get('detail_job','VendorMobileController@detail_job');
	Route::post('logout','VendorMobileController@logout');
	Route::post('update_location','VendorMobileController@update_location');
	Route::post('change_password','VendorMobileController@change_password');
	Route::post('send_message','VendorMobileController@send_message');
});

Route::group([
	'namespace' => 'Api',
	'middleware' => 'auth:api'
], function(){
	Route::get('setting/company_datatable','SettingApiController@company_datatable');
	Route::get('setting/user_datatable','SettingApiController@user_datatable');
	Route::get('setting/widget_datatable','SettingApiController@widget_datatable');
	Route::get('setting/dashboard_datatable','SettingApiController@dashboard_datatable');
	Route::get('setting/bank_datatable','SettingApiController@bank_datatable');
	Route::get('setting/port_datatable','SettingApiController@port_datatable');
	Route::get('setting/airport_datatable','SettingApiController@airport_datatable');
	Route::get('setting/address_type_datatable','SettingApiController@address_type_datatable');
	Route::get('setting/vendor_type_datatable','SettingApiController@vendor_type_datatable');
	Route::get('setting/customer_stage_datatable','SettingApiController@customer_stage_datatable');
	Route::get('setting/vessel_datatable','SettingApiController@vessel_datatable');
	Route::get('setting/countries_datatable','SettingApiController@countries_datatable');
	Route::get('setting/satuan_datatable','SettingApiController@satuan_datatable');
	Route::get('setting/commodity_datatable','SettingApiController@commodity_datatable');
	Route::get('setting/service_datatable','SettingApiController@service_datatable');
    Route::get('setting/vendor_job_status_datatable','SettingApiController@vendor_job_status_datatable');
	Route::get('setting/service_warehouse_datatable','SettingApiController@service_warehouse_datatable');
	Route::get('setting/container_datatable','SettingApiController@container_datatable');
	Route::get('setting/group_datatable','SettingApiController@group_datatable');
	Route::get('setting/account_datatable','SettingApiController@account_datatable');
	Route::get('setting/tax_datatable','SettingApiController@tax_datatable');
	Route::get('setting/cash_category_datatable','SettingApiController@cash_category_datatable');
	Route::get('setting/city_datatable','SettingApiController@city_datatable');
    Route::get('setting/province_datatable','SettingApiController@province_datatable');
	Route::get('setting/cost_type_datatable','SettingApiController@cost_type_datatable');
	Route::get('setting/saldo_account_datatable','SettingApiController@saldo_account_datatable');
	Route::get('setting/saldo_payable_datatable','SettingApiController@saldo_payable_datatable');
	Route::get('setting/saldo_receivable_datatable','SettingApiController@saldo_receivable_datatable');
	Route::get('setting/saldo_um_supplier_datatable','SettingApiController@saldo_um_supplier_datatable');
	Route::get('setting/saldo_um_customer_datatable','SettingApiController@saldo_um_customer_datatable');
	Route::get('setting/favorite_datatable','SettingApiController@favorite_datatable');
	Route::get('setting/route_datatable','SettingApiController@route_datatable');
    Route::get('setting/additional_field_datatable','SettingApiController@additional_field_datatable');
	Route::get('setting/vehicle_joint_datatable','SettingApiController@vehicle_joint_datatable');
	Route::get('setting/lead_status_datatable','SettingApiController@lead_status_datatable');
	Route::get('setting/lead_source_datatable','SettingApiController@lead_source_datatable');
	Route::get('setting/industry_datatable','SettingApiController@industry_datatable');
	Route::get('setting/detail_cost_datatable/{id}','SettingApiController@detail_cost_datatable');

	Route::get('contact/contact_datatable','ContactApiController@contact_datatable');
	Route::get('contact/customer_datatable','ContactApiController@customer_datatable');
	Route::get('contact/customer_amount','ContactApiController@customer_amount');
	Route::get('contact/contact_address_datatable/{id}','ContactApiController@contact_address_datatable');

	Route::get('setting/vehicle_variant_datatable','SettingApiController@vehicle_variant_datatable');
	Route::get('setting/maintenance_type_datatable','SettingApiController@maintenance_type_datatable');
	Route::get('setting/vehicle_checklist_datatable','SettingApiController@vehicle_checklist_datatable');
	Route::get('setting/vehicle_body_datatable','SettingApiController@vehicle_body_datatable');
	Route::get('setting/vehicle_type_datatable','SettingApiController@vehicle_type_datatable');
	Route::get('setting/vehicle_manufacturer_datatable','SettingApiController@vehicle_manufacturer_datatable');
	Route::get('setting/vehicle_owner_datatable','SettingApiController@vehicle_owner_datatable');

	Route::get('setting/route_cost_datatable','SettingApiController@route_cost_datatable');

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'finance'
	], function(){
		Route::get('company_saldo_datatable','FinanceApiController@company_saldo_datatable');
		Route::get('gross_margin_inPercent','FinanceApiController@gross_margin_inPercent');
		Route::get('balance_inPercent','FinanceApiController@balance_inPercent');
		Route::get('draft_list_piutang_datatable','FinanceApiController@draft_list_piutang_datatable');
		Route::get('draft_list_piutang_datatable2','FinanceApiController@draft_list_piutang_datatable2');
		Route::get('draft_list_hutang_datatable','FinanceApiController@draft_list_hutang_datatable');
		Route::get('journal_datatable','FinanceApiController@journal_datatable');
		Route::get('cash_transaction_datatable','FinanceApiController@cash_transaction_datatable');
		Route::get('cek_giro_datatable','FinanceApiController@cek_giro_datatable');
		Route::get('um_supplier_datatable','FinanceApiController@um_supplier_datatable');
		Route::get('um_customer_datatable','FinanceApiController@um_customer_datatable');
		Route::get('nota_credit_datatable','FinanceApiController@nota_credit_datatable');
		Route::get('nota_debet_datatable','FinanceApiController@nota_debet_datatable');
		Route::get('submission_cost_datatable','FinanceApiController@submission_cost_datatable');
		Route::get('cash_count_datatable','FinanceApiController@cash_count_datatable');
		Route::get('pajak_datatable','FinanceApiController@pajak_datatable');
		Route::get('kas_bon_datatable','FinanceApiController@kas_bon_datatable');
		Route::get('bill_datatable','FinanceApiController@bill_datatable');
		Route::get('debt_datatable','FinanceApiController@debt_datatable');
		Route::get('bill_payment_datatable','FinanceApiController@bill_payment_datatable');
		Route::get('debt_payment_datatable','FinanceApiController@debt_payment_datatable');
		Route::get('cash_migration_datatable','FinanceApiController@cash_migration_datatable');
		Route::get('asset_group_datatable','FinanceApiController@asset_group_datatable');
		Route::get('asset_datatable','FinanceApiController@asset_datatable');
		Route::get('daftarasset_datatable','FinanceApiController@daftarasset_datatable');
		Route::get('saldo_asset_datatable','FinanceApiController@saldo_asset_datatable');
		Route::get('asset_purchase_datatable','FinanceApiController@asset_purchase_datatable');
		Route::get('asset_depreciation_datatable','FinanceApiController@asset_depreciation_datatable');
		Route::get('asset_afkir_datatable','FinanceApiController@asset_afkir_datatable');
		Route::get('asset_sales_datatable','FinanceApiController@asset_sales_datatable');
	});

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'vehicle'
	], function(){
		Route::get('vehicle_datatable','VehicleApiController@vehicle_datatable');
		Route::get('get_vehicle','VehicleApiController@get_vehicle');
		Route::get('vehicle_distance_datatable','VehicleApiController@vehicle_distance_datatable');
		Route::get('vehicle_check_datatable','VehicleApiController@vehicle_check_datatable');
		Route::get('target_rate_datatable','VehicleApiController@target_rate_datatable');
		Route::get('maintenance_datatable','VehicleApiController@maintenance_datatable');
		Route::get('maintenance_detail_datatable/{vm_id}','VehicleApiController@maintenance_detail_datatable');
		Route::get('hitung_perawatan/{vehicle_id}','VehicleApiController@hitung_perawatan');
	});

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'driver'
	], function(){
		Route::get('driver_datatable','DriverApiController@driver_datatable');
	});

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'vendor'
	], function(){
		Route::get('vendor_datatable','VendorApiController@vendor_datatable');
		Route::get('register_vendor_datatable','VendorApiController@register_vendor_datatable');
		Route::get('vendor_price_datatable/{id?}','VendorApiController@vendor_price_datatable');
	});

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'customer'
	], function(){
		Route::get('customer_price_datatable','CustomerController@customer_price_datatable');
	});

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'marketing'
	], function(){
		Route::get('price_list_datatable','MarketingApiController@price_list_datatable');
		Route::get('combined_price_datatable','MarketingApiController@combined_price_datatable');
		Route::get('inquery_datatable','MarketingApiController@inquery_datatable');
		Route::get('inquery_customer_datatable','MarketingApiController@inquery_customer_datatable');
		Route::get('contract_datatable','MarketingApiController@contract_datatable');
		Route::get('inquery_detail_cost_datatable','MarketingApiController@inquery_detail_cost_datatable');
		Route::get('contract_price_datatable','MarketingApiController@contract_price_datatable');
		Route::get('lead_amount','MarketingApiController@lead_amount');
		Route::get('lead_datatable','MarketingApiController@lead_datatable');
		Route::get('lead_activity_datatable','MarketingApiController@lead_activity_datatable');
		Route::get('opportunity_datatable','MarketingApiController@opportunity_datatable');
		Route::get('inquery_qt_datatable','MarketingApiController@inquery_qt_datatable');
		Route::get('quotation_detail_datatable','MarketingApiController@quotation_detail_datatable');
		Route::get('work_order_trend','MarketingApiController@work_order_trend');
		Route::get('work_order_trend_new','MarketingApiController@work_order_trend_new');
		Route::get('work_order_amount/{filter}','MarketingApiController@work_order_amount');
		Route::get('work_order_datatable','MarketingApiController@work_order_datatable');
		Route::get('work_order_draft_datatable','MarketingApiController@work_order_draft_datatable');
		Route::get('work_order_detail_datatable','MarketingApiController@work_order_detail_datatable');
		Route::get('draft_check','MarketingApiController@draft_check');
		Route::get('inquery_offer_datatable','MarketingApiController@inquery_offer_datatable');
		Route::get('activity_work_order','MarketingApiController@activity_work_order');
		Route::get('activity_job_order','MarketingApiController@activity_job_order');
	});

    Route::group([
        'middleware' => 'auth:api',
        'prefix' => 'sales'
    ], function(){
        Route::get('sales_order_datatable','SalesApiController@sales_order_datatable');
        Route::get('sales_order_detail_datatable','SalesApiController@sales_order_detail_datatable');
        Route::get('customer_order_datatable','SalesApiController@customer_order_datatable');
    });

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'inventory'
	], function(){
		Route::get('mutasi_transfer_datatable','InventoryApiController@mutasi_transfer_datatable');
		Route::get('category_datatable','InventoryApiController@category_datatable');
        Route::get('item_condition_datatable','InventoryApiController@item_condition_datatable');
		Route::get('stock_initial_datatable','InventoryApiController@stock_initial_datatable');
		Route::get('purchase_request_datatable','InventoryApiController@purchase_request_datatable');
		Route::get('purchase_order_datatable','InventoryApiController@purchase_order_datatable');
        Route::get('pallet_purchase_order_datatable','InventoryApiController@pallet_purchase_order_datatable');
		Route::get('receipt_datatable','InventoryApiController@receipt_datatable');
		Route::get('adjustment_datatable','InventoryApiController@adjustment_datatable');
		Route::get('warehouse_stock_datatable','InventoryApiController@warehouse_stock_datatable');
        Route::get('stock_by_item_datatable','InventoryApiController@stock_by_item_datatable');
		Route::get('stock_transaction_datatable','InventoryApiController@stock_transaction_datatable');
		Route::get('using_item_datatable','InventoryApiController@using_item_datatable');
		Route::get('gudang_dan_item','InventoryApiController@gudang_dan_item');
		Route::get('retur_datatable','InventoryApiController@retur_datatable');
		Route::get('warehouse_datatable','InventoryApiController@warehouse_datatable');
		Route::get('picking_order_datatable','InventoryApiController@picking_order_datatable');

	});

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'inventory',
		'as' => 'inventory.',
		'namespace' => 'Inventory'
	], function(){
      // Item
		Route::get('item/general_item   ','ItemController@general_item');
		Route::get('item/surat_jalan','ItemController@surat_jalan');
		Route::get('item/rack','ItemController@rack');

		Route::get('item/cek_stok_warehouse','ItemController@cek_stok_warehouse');
		Route::post('picking_order/{id}/import','ItemController@import_picking');
		Route::resource('item','ItemController');
	});


	Route::group([
		'middleware' => ['auth:api', 'compare_date'],
		'prefix' => 'operational'
	], function(){

		Route::get('voyage_schedule_datatable','OperationalApiController@voyage_schedule_datatable');
		Route::get('container_datatable','OperationalApiController@container_datatable');
		Route::get('job_order_datatable','OperationalApiController@job_order_datatable');
		Route::get('job_order_detail_datatable','OperationalApiController@job_order_detail_datatable');
        Route::get('vendor_job_datatable','OperationalApiController@vendor_job_datatable');
		Route::get('job_order_cost_datatable','OperationalApiController@job_order_cost_datatable');
		Route::get('manifest_cost_datatable','OperationalApiController@manifest_cost_datatable');
		Route::get('jo_datatable','OperationalApiController@jo_datatable');
		Route::get('job_order_inProgress','OperationalApiController@job_order_inProgress');
		Route::get('manifest_ftl_datatable','OperationalApiController@manifest_ftl_datatable');
		Route::get('manifest_fcl_datatable','OperationalApiController@manifest_fcl_datatable');
		Route::get('delivery_order_driver_datatable','OperationalApiController@delivery_order_driver_datatable');
		Route::get('shipment_status_datatable','OperationalApiController@shipmentStatusDatatable')->middleware(['compare_date']);
		Route::get('invoice_jual_amount','OperationalApiController@invoice_jual_amount');
		Route::get('invoice_jual_datatable','OperationalApiController@invoice_jual_datatable');
		Route::get('job_order_cost_datatable','OperationalApiController@job_order_cost_datatable');
		Route::get('kpi_log_datatable','OperationalApiController@kpi_log_datatable');
		Route::get('invoice_vendor_datatable','OperationalApiController@invoice_vendor_datatable');
		Route::get('map_driver_job_list','OperationalApiController@map_driver_job_list');
		Route::post('get_last_position_by_vendor_1','OperationalApiController@get_last_position_by_vendor_1');
		Route::post('get_last_position_by_vendor_2','OperationalApiController@get_last_position_by_vendor_2');
		Route::get('jo_cost_vendor_datatable','OperationalApiController@jo_cost_vendor_datatable');
		Route::get('claim_categories_datatable','OperationalApiController@claim_categories_datatable');
		Route::get('claims_datatable','OperationalApiController@claims_datatable');
	});

    Route::group([
        'middleware' => ['auth:api', 'compare_date'],
        'prefix' => 'depo'
    ], function(){
        Route::get('container_inspection_datatable','DepoApiController@container_inspection_datatable');
        Route::get('gate_in_container_datatable','DepoApiController@gate_in_container_datatable');
        Route::get('movement_container_datatable','DepoApiController@movement_container_datatable');
    });

	Route::group([
		'middleware' => ['auth:api', 'compare_date'],
		'prefix' => 'operational_warehouse'
	], function(){
		Route::get('export','OperationalWarehouseApiController@export');
		Route::get('stocklist_datatable','OperationalWarehouseApiController@stocklist_datatable');
        Route::get('warehouse_receipt_detail4_datatable','OperationalWarehouseApiController@warehouse_receipt_detail4_datatable');
		Route::get('mutasi_transfer_datatable','OperationalWarehouseApiController@mutasi_transfer_datatable');
		Route::get('general_item_datatable','OperationalWarehouseApiController@general_item_datatable');
		Route::get('putaway_datatable','OperationalWarehouseApiController@putaway_datatable');
		Route::get('picking_datatable','OperationalWarehouseApiController@picking_datatable');
		Route::get('stok_opname_datatable','OperationalWarehouseApiController@stok_opname_datatable');
		Route::get('warehouse_receipt_datatable','OperationalWarehouseApiController@warehouse_receipt_datatable');
        Route::get('packaging_datatable','OperationalWarehouseApiController@packaging_datatable');

		Route::get('receipt_report_datatable','OperationalWarehouseApiController@receipt_report_datatable');

		Route::get('rack_datatable','OperationalWarehouseApiController@rack_datatable');
		Route::get('warehouse_datatable','OperationalWarehouseApiController@warehouse_datatable');
		Route::get('warehouse_receipt_detail_datatable','OperationalWarehouseApiController@warehouse_receipt_detail_datatable');
		Route::get('pallet_category_datatable','OperationalWarehouseApiController@pallet_category_datatable');
		Route::get('master_item_datatable','OperationalWarehouseApiController@master_item_datatable');
		Route::get('item_warehouse_datatable','OperationalWarehouseApiController@item_warehouse_datatable');
		Route::get('validasi_item_datatable/{id}','OperationalWarehouseApiController@validasi_item_datatable');
		Route::get('master_pallet_datatable','OperationalWarehouseApiController@master_pallet_datatable');
		Route::get('storage_type_datatable','OperationalWarehouseApiController@storage_type_datatable');
		Route::get('pallet_purchase_order_datatable','OperationalWarehouseApiController@pallet_purchase_order_datatable');
		Route::get('pallet_receipt_datatable','OperationalWarehouseApiController@pallet_receipt_datatable');
		Route::get('pallet_using_datatable','OperationalWarehouseApiController@pallet_using_datatable');
		Route::get('pallet_po_return_datatable','OperationalWarehouseApiController@pallet_po_return_datatable');
		Route::get('pallet_sales_order_datatable','OperationalWarehouseApiController@pallet_sales_order_datatable');
		Route::get('pallet_sales_order_return_datatable','OperationalWarehouseApiController@pallet_sales_order_return_datatable');
		Route::get('pallet_migration_datatable','OperationalWarehouseApiController@pallet_migration_datatable');
		Route::get('pallet_stock_datatable','OperationalWarehouseApiController@pallet_stock_datatable');
		Route::get('pallet_deletion_datatable','OperationalWarehouseApiController@pallet_deletion_datatable');
	});

	Route::group([
		'middleware' => 'auth:api',
		'prefix' => 'operational',
		'as' => 'operational.',
		'namespace' => 'Operational'
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
		Route::post('job_order/add_item_warehouse/{id}','JobOrderController@add_item_warehouse');
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
		Route::resource('job_order','JobOrderController');

	});

	Route::post('mobile/login','MobileController@login');
	Route::group([
		'prefix' => 'mobile',
		'middleware' => 'auth:api'
	], function(){
		Route::get('city_list','MobileController@city_list');
		Route::get('company_list','MobileController@company_list');

		Route::get('warehouse_list','MobileController@warehouse_list');
		Route::post('detail_warehouse','MobileController@detail_warehouse');
		Route::post('tambah_warehouse','MobileController@tambah_warehouse');
		Route::post('delete_warehouse','MobileController@delete_warehouse');
		Route::post('update_warehouse','MobileController@update_warehouse');

		Route::get('bin_list','MobileController@bin_list');
		Route::post('detail_bin','MobileController@detail_bin');
		Route::post('create_bin','MobileController@create_bin');
		Route::post('update_bin','MobileController@update_bin');
		Route::post('delete_bin','MobileController@delete_bin');

		Route::get('employee_list','MobileController@employee_list');
		Route::get('contact_list','MobileController@contact_list');
		Route::get('customer_list','MobileController@customer_list');
		Route::get('vendor_list','MobileController@vendor_list');
		Route::get('supplier_list','MobileController@supplier_list');
		Route::get('category_list','MobileController@category_list');
		Route::get('item_list','MobileController@item_list');
		Route::get('stripping_list','MobileController@stripping_list');
	});

});

Route::group([
	'prefix' => 'v4',
	'namespace' => 'Api\v4',
], function(){

	Route::post('login','CustomerController@login_user');
	Route::post('logout','CustomerController@logout');
	Route::post('cek_token','CustomerController@cek_token');
	Route::put('change_password','CustomerController@change_password');
	Route::get('send_password','CustomerController@sendPassword');
	Route::group([
		'middleware' => 'auth:api',
	], function(){
		require(base_path('routes/api/apiv4.php'));
	});
});

Route::group([
    'prefix' => 'v5',
    'namespace' => 'Api\v5',
], function(){

    Route::post('driver/login','AuthController@login');
    Route::post('driver/logout','AuthController@logout');
    Route::group([
        'middleware' => 'auth:api',
    ], function(){
        require(base_path('routes/api/apiv5.php'));
    });
});
