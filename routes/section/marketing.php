<?php

Route::group([
    'middleware' => ['web','auth'],
    'prefix' => 'marketing',
    'as' => 'marketing.',
    'namespace' => 'Marketing',
], function(){
    Route::get('price_list/cari_tarif','PriceListController@cari_tarif');
    Route::get('price_list/{id}/cost','PriceListController@showCost');
    Route::post('price_list/{id}/cost','PriceListController@addCost');
    Route::put('price_list/{id}/cost','PriceListController@updateCost');
    Route::delete('price_list/{id}/cost/{price_list_cost_id}','PriceListController@deleteCost');
    Route::get('price_list/edit_cost/{price_list_cost_id}','PriceListController@editCost');
    Route::get('price_list/{id}/minimum_detail','PriceListController@showMinimumDetail');
    Route::resource('price_list','PriceListController');
    Route::post('price_list/minimum_detail','PriceListController@store_minimal_detail');
    Route::put('price_list/minimum_detail/{id}','PriceListController@update_minimal_detail');
    Route::delete('price_list/minimum_detail/{id}','PriceListController@destroy_minimal_detail');
    Route::get('inquery/cari_route_cost','InqueryController@cari_route_cost');
    Route::get('inquery/show_detail/{id}','InqueryController@show_detail');
    Route::post('inquery/store_detail/{id}/{iddetail?}','InqueryController@store_detail');
    Route::delete('inquery/delete_detail/{id}','InqueryController@delete_detail');
    Route::post('inquery/minimum_detail','InqueryController@store_minimal_detail');
    Route::put('inquery/minimum_detail/{id}','InqueryController@update_minimal_detail');
    Route::delete('inquery/minimum_detail/{id}','InqueryController@destroy_minimal_detail');
    Route::post('inquery/reject/{id}','InqueryController@reject');
    Route::post('inquery/ajukan/{id}','InqueryController@ajukan');
    Route::post('inquery/approve/{id}','InqueryController@approve');
    Route::post('inquery/approve_direction/{id}','InqueryController@approve_direction');
    Route::post('inquery/approve_manager/{id}','InqueryController@approve_manager');
    Route::post('inquery/approve_detail/{id}','InqueryController@approve_detail');
    Route::get('inquery/add_contract/{id}','InqueryController@add_contract');
    Route::post('inquery/store_contract/{id}','InqueryController@store_contract');
    Route::post('inquery/store_description/{id}','InqueryController@store_description');
    Route::post('inquery/store_cost/{id}','InqueryController@store_cost');
    Route::post('inquery/store_detail_cost/{id}','InqueryController@store_detail_cost');
    Route::post('inquery/store_detail_item/{id}','InqueryController@store_detail_item');
    Route::delete('inquery/delete_detail_cost/{id}','InqueryController@delete_detail_cost');
    Route::post('inquery/upload_file/{id}','InqueryController@upload_file');
    Route::get('inquery/document/{id}','InqueryController@document');
    Route::get('inquery/detail_cost/{id}','InqueryController@detail_cost');
    Route::get('inquery/offering/{id}','InqueryController@offering');
    Route::post('inquery/store_offer/{id}','InqueryController@store_offer');
    Route::post('inquery/reject_offer/{id}','InqueryController@reject_offer');
    Route::post('inquery/approve_offer/{id}','InqueryController@approve_offer');
    Route::post('inquery/cancel_quotation/{id}','InqueryController@cancel_quotation');
    Route::post('inquery/cancel_cancel_quotation/{id}','InqueryController@cancel_cancel_quotation');
    Route::post('inquery/{id}/item/{item_id}','InqueryController@storeQuotationItem');
    Route::resource('inquery','InqueryController');
    Route::get('contract/{id}/item','ContractController@item');
    Route::get('contract/{id}/item_quotation','ContractController@itemQuotation');
    Route::get('contract/{id}/cost','ContractController@cost');
    Route::get('contract/{id}/jo_history','ContractController@jo_history');
    Route::get('contract/amandemen/{id}','ContractController@amandemen');
    Route::post('contract/save_typing/{id}','ContractController@save_typing');
    Route::post('contract/clone_amandemen/{id}','ContractController@clone_amandemen');
    Route::post('contract/{id}/cancel','ContractController@cancel');
    Route::post('contract/store_amandemen/{id}','ContractController@store_amandemen');
    Route::post('contract/stop_contract/{id}','ContractController@stop_contract');
    Route::post('lead/store_activity/{id}','LeadController@store_activity');
    Route::post('lead/store_document/{id}','LeadController@store_document');
    Route::post('lead/delete_document/{id}','LeadController@delete_document');
    Route::post('lead/change_status/{id}','LeadController@change_status');
    Route::post('lead/cancel_lead/{id}','LeadController@cancel_lead');
    Route::post('lead/cancel_cancel_lead/{id}','LeadController@cancel_cancel_lead');
    Route::get('lead/document/{id}','LeadController@document');
    Route::get('lead/cari_lead/{id}','LeadController@cari_lead');
    Route::post('lead/store_opportunity/{id}','LeadController@store_opportunity');
    Route::post('lead/store_inquery/{id}','LeadController@store_inquery');
    Route::post('lead/store_quotation/{id}','LeadController@store_quotation');
    Route::post('lead/done_activity/{id}','LeadController@done_activity');
    Route::delete('lead/delete_activity/{id}','LeadController@delete_activity');
    Route::get('opportunity/data_activity/{id}','OpportunityController@data_activity');
    Route::post('opportunity/store_activity/{id}','OpportunityController@store_activity');
    Route::post('opportunity/done_activity/{id}','OpportunityController@done_activity');
    Route::post('opportunity/cancel_opportunity/{id}','OpportunityController@cancel_opportunity');
    Route::post('opportunity/cancel_cancel_opportunity/{id}','OpportunityController@cancel_cancel_opportunity');
    Route::post('opportunity/cancel_cancel_inquery/{id}','OpportunityController@cancel_cancel_inquery');
    Route::post('opportunity/cancel_inquery/{id}','OpportunityController@cancel_inquery');
    Route::delete('opportunity/delete_activity/{id}','OpportunityController@delete_activity');
    Route::get('inquery_qt/cari_oppo/{id}','InqueryQtController@cari_oppo');
    Route::post('inquery_qt/store_activity/{id}','InqueryQtController@store_activity');
    Route::get('work_order/cari_detail_kontrak/{id}','WorkOrderController@cari_detail_kontrak');
    Route::get('work_order/detail/{work_order_detail_id}','WorkOrderController@showDetail');
    Route::get('work_order/{id}/price_detail','WorkOrderController@showPriceDetail');
    Route::get('work_order/add_detail/{id}','WorkOrderController@add_detail');
    Route::get('work_order/print/{id}','WorkOrderController@print');
    Route::get('work_order/show_request/{id}','WorkOrderController@show_request');
    Route::get('work_order/get_wo_detail_parameter/{id}','WorkOrderController@get_wo_detail_parameter');
    Route::delete('work_order/request/{id}','WorkOrderController@reject_request');

    Route::get('work_order/{id}/packet/job_order','WorkOrderController@getJobOrderIdPacket')->middleware('closing:workOrder');
    Route::post('work_order/store_detail/{id}','WorkOrderController@store_detail')->middleware('closing:workOrder');
    Route::post('work_order/store_detail_customer_price/{id}','WorkOrderController@store_detail_customer_price')->middleware('closing:workOrder');

    Route::post('work_order/store_edit_detail','WorkOrderController@store_edit_detail')->middleware('closing:workOrder');
    Route::post('work_order/approve_detail/{id}','WorkOrderController@approve_detail')->middleware('closing:workOrder');
    Route::post('work_order/store_qty/{id}','WorkOrderController@store_qty')->middleware('closing:workOrder');
    Route::post('work_order/store_draft','WorkOrderController@store_draft')->middleware('closing:workOrder');
    Route::post('work_order/cancel_done/{id}','WorkOrderController@cancel_done');
    Route::delete('work_order/delete_detail/{id}','WorkOrderController@delete_detail')->middleware('closing:workOrder');

    Route::get('report/activity_wo_index','ReportController@activity_wo_index');
    Route::post('report/export','ReportController@export');
    Route::post('report/report_html','ReportController@report_html');

    Route::resource('contract','ContractController');
    Route::get('vendor_price/{flag}/search','VendorPriceController@search');
    Route::resource('vendor_price','VendorPriceController');
    Route::resource('customer_price','CustomerPriceController');
    Route::resource('lead','LeadController');
    Route::resource('opportunity','OpportunityController');
    Route::resource('inquery_qt','InqueryQtController');
    Route::resource('work_order','WorkOrderController')->middleware('closing:workOrder');
    Route::resource('report','ReportController');

    Route::post('sales_contract','SalesContractController@store');

});