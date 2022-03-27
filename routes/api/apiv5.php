<?php

Route::group([
	'prefix' => 'driver',
], function(){
	Route::get('get_auth','AuthController@getAuth');
    Route::post('change_password','AuthController@changePassword');

    Route::get('get_vehicles','VehicleController@index');
    Route::post('post_vehicles','VehicleController@store');
    Route::get('score','DriverController@showShipmentSummary');

    Route::group([
        'prefix' => 'delivery_orders',
    ], function(){
        Route::post('post_locations','DriverController@storeLocation');
        Route::get('','DeliveryOrderController@index');
        Route::get('/summary','DeliveryOrderController@showSummary');
        Route::get('/{id}','DeliveryOrderController@show');
        Route::get('/{id}/manifests','DeliveryOrderController@indexManifest');
        Route::get('/{id}/receiver','DeliveryOrderController@indexReceiver');
        Route::put('/{id}/load/{manifest_detail_id}','DeliveryOrderController@loadItem');
        Route::put('/{id}/discharge/{manifest_detail_id}','DeliveryOrderController@dischargeItem');
        Route::post('submit_status/{id}','DeliveryOrderController@submitStatus');
        Route::post('/{id}/file','DeliveryOrderController@storeFile');

    });
});
