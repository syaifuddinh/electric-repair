<?php

use Illuminate\Support\Facades\Artisan;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');
Route::get('/home', function(){
  return redirect(url('/'));
});
Route::get('/logout', 'Auth\LoginController@logout');
Route::get('/pdf/print_quotation/{slug}/slug','Export\PdfController@print_quotation_by_slug')->name('print_quotation_by_slug');
Route::group(
    [
        'middleware' => ['web','auth'],
        'prefix' => 'mail',
        'as' => 'mail.',
        'namespace' => 'Mail',
    ], function(){
        Route::get('/test_mail','MailController@test_mail');
});
Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'setting',
  'as' => 'setting.',
  'namespace' => 'Setting',
], function(){
    Route::get('/migrate', function () {
        $res = Artisan::call('migrate');
    });
    Route::get('/migrate/rollback', function () {
        $res = Artisan::call('migrate:rollback');
    });
  Route::get('files/{filename}', function ($filename)
  {
      // Routing file
      $path = storage_path('app/public/files/' . $filename);

      if (!File::exists($path)) {
          abort(404);
      }

      $file = File::get($path);
      $type = File::mimeType($path);
      return Storage::download($path);

      $response = Response::make($file, 200);
      $response->header("Content-Type", $type);

      return $response;
  });
  Route::get('query','QueryController@index');
  Route::get('query/table','QueryController@showTable');
  Route::get('query/table/detail','QueryController@detailTable');
  Route::get('query/run','QueryController@run');
  Route::get('query/first','QueryController@first');
  Route::post('query','QueryController@store');
  Route::put('query/{id}','QueryController@update');
  Route::resource('widget','WidgetController');

  Route::get('dashboard/{id}/detail','DashboardController@showDetail');
  Route::resource('dashboard','DashboardController');

  Route::get('field_type','FieldTypeController@index');
  Route::get('additional_field/group','AdditionalFieldController@indexGroup');
  Route::get('additional_field/field/in_manifest','AdditionalFieldController@indexInManifest');
  Route::get('additional_field/field/in_index/{type_transaction}','AdditionalFieldController@indexInIndex');
  Route::get('additional_field/field/job_order_summary','AdditionalFieldController@indexInJobOrderSummary');
  Route::get('additional_field/field/operational_progress','AdditionalFieldController@indexInOperationalProgress');
  Route::get('additional_field/field/{type_transaction}','AdditionalFieldController@indexByTransaction');
  Route::resource('additional_field','AdditionalFieldController');

  Route::get('area/area_datatable','AreaController@datatable');
  Route::resource('area','AreaController');

  Route::post('company/warehouse_delete/{id}','CompanyController@warehouse_delete');
  Route::get('company/warehouse_detail/{id}','CompanyController@warehouse_detail');
  Route::get('company/warehouse/{id}','CompanyController@warehouse');
  Route::post('company/store_gudang','CompanyController@store_gudang');
  Route::get('company/numbering_index','CompanyController@numbering_index');
  Route::get('company/company_numbering/{cid}/{fid}','CompanyController@company_numbering');
  Route::get('company/edit_format/{id}','CompanyController@edit_format');
  Route::post('company/format_store','CompanyController@format_store');
  Route::delete('company/delete_format/{id}','CompanyController@delete_format');
  Route::resource('company','CompanyController');

  Route::get('user/roles','UserController@roles');
  Route::post('user/role_array','UserController@role_array');
  Route::post('user/save_role/{id}','UserController@save_role');
  Route::get('user/group_previlage/{id}','UserController@group_previlage');
  Route::post('user/store_group_previlage/{id}','UserController@store_group_previlage');
  Route::post('user/store_group/{id?}','UserController@store_group');
  Route::delete('user/user_group/{id}','UserController@user_group_delete');
  Route::get('user/role/{id}','UserController@role');
  Route::get('user/notification/{id}','UserController@notification');
  Route::post('user/store_notification/{id}','UserController@store_notification');
  Route::post('user/change_password/{id}','UserController@change_password');
  Route::resource('user','UserController');

  Route::get('setting/{slug?}','SettingController@index');
  Route::get('setting/{slug}/{key}','SettingController@show');
  Route::put('setting','SettingController@update');

  Route::get('email','EmailController@index');
  Route::post('email','EmailController@store');
  Route::get('email/shipment_chip','EmailController@indexShipmentChip');

  Route::resource('unit','UnitController');

  Route::resource('container_type','ContainerTypeController');

  Route::get('general/customer_stage/{id?}','GeneralController@customer_stage');
  Route::get('general/customer_stage/{id?}','GeneralController@customer_stage');
  Route::get('general/bank/{id?}','GeneralController@bank');
  Route::get('general/vessel/{id?}','GeneralController@vessel');
  Route::post('general/store_bank/{id?}','GeneralController@store_bank');
  Route::post('general/store_customer_stage/{id?}','GeneralController@store_customer_stage');
  Route::post('general/store_vessel/{id?}','GeneralController@store_vessel');
  Route::post('general/store_countries/{id?}','GeneralController@store_countries');
  Route::get('general/satuan/{id?}','GeneralController@satuan');
  Route::get('general/commodity/{id?}','GeneralController@commodity');
  Route::post('general/store_commodity/{id?}','GeneralController@store_commodity');
  Route::get('general/index_commodity','GeneralController@indexCommodity');
  Route::get('general/service/{id?}','GeneralController@service');
  Route::get('general/service_type','GeneralController@serviceType');
  Route::get('general/moda','GeneralController@moda');
  Route::get('general/service_group','GeneralController@serviceGroup');
  Route::get('general/account','GeneralController@account');
  Route::post('general/store_service/{id?}','GeneralController@store_service');
  Route::post('general/store_service_warehouse/{id?}','GeneralController@store_service_warehouse');
  Route::get('general/container/{id?}','GeneralController@container');
  Route::get('general/port/{id?}','GeneralController@port');
  Route::post('general/store_port/{id?}','GeneralController@store_port');
  Route::get('general/airport/{id?}','GeneralController@airport');
  Route::post('general/store_airport/{id?}','GeneralController@store_airport');
  Route::get('general/vendor','GeneralController@vendor');
  Route::get('general/vendor_type/{id?}','GeneralController@vendor_type');
  Route::post('general/store_vendor_type/{id?}','GeneralController@store_vendor_type');
  Route::get('general/address_type/{id?}','GeneralController@address_type');
  Route::post('general/store_address_type/{id?}','GeneralController@store_address_type');
  Route::get('general/lead_status/{id?}','GeneralController@lead_status');
  Route::post('general/store_lead_status/{id?}','GeneralController@store_lead_status');
  Route::get('general/lead_source/{id?}','GeneralController@lead_source');
  Route::post('general/store_lead_source/{id?}','GeneralController@store_lead_source');
  Route::get('general/industry/{id?}','GeneralController@industry');
  Route::post('general/store_industry/{id?}','GeneralController@store_industry');
  Route::get('general/print_remark','GeneralController@print_remark');
  Route::post('general/print_remark/logo','GeneralController@storeRemarkLogo');
  Route::post('general/store_remark','GeneralController@store_remark');

  Route::delete('general/countries/{id}','GeneralController@delete_countries');
  Route::delete('general/vessel/{id}','GeneralController@delete_vessel');
  Route::delete('general/service/{id}','GeneralController@delete_service');
  Route::delete('general/commodity/{id}','GeneralController@delete_commodity');
  
  Route::delete('general/bank/{id}','GeneralController@delete_bank');
  Route::delete('general/port/{id}','GeneralController@delete_port');
  Route::delete('general/airport/{id}','GeneralController@delete_airport');
  Route::delete('general/vendor_type/{id}','GeneralController@delete_vendor_type');
  Route::delete('general/address_type/{id}','GeneralController@delete_address_type');
  Route::delete('general/customer_stage/{id}','GeneralController@delete_customer_stage');

  Route::get('get_account/{id?}','SettingAccountController@get_account');
  Route::resource('account','SettingAccountController');

  Route::resource('account_default','AccountDefaultController');
  Route::get('tax/default','TaxController@default');
  Route::get('tax/ppn','TaxController@ppn');
  Route::resource('tax','TaxController');
  Route::post('cash_category/store_detail/{id}','CashCategoryController@store_detail');
  Route::post('cash_category/delete_detail/{id}','CashCategoryController@delete_detail');
  Route::resource('cash_category','CashCategoryController');
  Route::resource('city','CityController');
  Route::resource('country','CountryController');
  Route::resource('province','ProvinceController');
  Route::resource('cost_type','CostTypeController');

  Route::get('cost_route_type','CostRouteTypeController@index');
  Route::get('cost_route_type/{id}','CostRouteTypeController@show');

  Route::resource('saldo_account','SaldoAccountController')->middleware('closing:saldoAwal');
  Route::resource('saldo_payable','SaldoPayableController')->middleware('closing:saldoAwal');
  Route::resource('saldo_receivable','SaldoReceivableController')->middleware('closing:saldoAwal');
  Route::resource('saldo_um_supplier','SaldoUmSupplierController')->middleware('closing:saldoAwal');
  Route::resource('saldo_um_customer','SaldoUmCustomerController')->middleware('closing:saldoAwal');
  Route::resource('favorite','FavoriteController');
  Route::resource('transaction_lock','TransactionLockController');
  Route::resource('vendor_job_status','VendorJobStatusController');
  Route::post('route_cost/save_as','RouteCostController@save_as');

  Route::resource('vehicle_manufacturer','VehicleManufacturerController');
  Route::resource('vehicle_owner','VehicleOwnerController');
  Route::resource('vehicle_joint','VehicleJointController');
  Route::resource('vehicle_variant','VehicleVariantController');

  Route::resource('tire_type','TireTypeController');
  Route::resource('tire_size','TireSizeController');

  Route::resource('reminder_type','ReminderTypeController');
  Route::resource('maintenance_type','MaintenanceTypeController');
  Route::resource('vehicle_checklist','VehicleChecklistController');
  Route::resource('vehicle_body','VehicleBodyController');

  Route::resource('route_cost','RouteCostController');
  Route::resource('status_proses','StatusProsesController');

  Route::get('service/{id}/statuses','ServiceController@showStatuses');
});

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'contact',
  'as' => 'contact.',
  'namespace' => 'Contact',
], function(){
  Route::get('contact/pegawai','ContactController@pegawai');
  Route::get('contact/customer','ContactController@customer');
  Route::get('contact/driver','ContactController@driver');
  Route::get('contact/sales','ContactController@sales');
  Route::get('contact/supplier','ContactController@supplier');
  Route::get('contact/vendor','ContactController@vendor');
  Route::get('contact/penerima','ContactController@penerima');
  Route::put('contact/{id}/activate','ContactController@activate');
  Route::put('contact/{id}/approve_customer','ContactController@approveCustomer');
  Route::get('contact/{id}/receivable_value','ContactController@receivable_value');
  Route::get('contact/{id}/receivable_count','ContactController@receivable_count');
  Route::get('contact/{id}/payable_value','ContactController@payable_value');
  Route::get('contact/{id}/payable_count','ContactController@payable_count');
  Route::get('contact/{id}/pic','ContactController@showPic');
  Route::get('contact/{id}/field/{column}','ContactController@showField');
  Route::get('contact/create_address','ContactController@create_address');
  Route::get('contact/create_address_f','ContactController@create_address_f');
  Route::get('contact/edit_address/{id}','ContactController@edit_address');
  Route::get('contact/show_address/{id}','ContactController@show_address');
  Route::get('contact/user_application/{id}','ContactController@user_application');
  Route::get('contact/show_file/{id}','ContactController@show_file');
  Route::post('contact/store_address','ContactController@store_address');
  Route::post('contact/store_address_f','ContactController@store_address_f');
  Route::post('contact/update_address/{id}','ContactController@update_address');
  Route::post('contact/store_user_application/{id}','ContactController@store_user_application');
  Route::post('contact/contact_store_user/{id}','ContactController@contact_store_user');
  Route::post('contact/save_as/{id}','ContactController@save_as');
  Route::post('contact/upload_document/{id}','ContactController@upload_document');
  Route::delete('contact/delete_file/{id}','ContactController@delete_file');
  Route::delete('contact/delete_address/{id}','ContactController@delete_address');
  Route::resource('contact','ContactController');
});

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'vehicle',
  'as' => 'vehicle.',
  'namespace' => 'Vehicle',
], function(){
  Route::get('maintenance/create/{vehicle_id}','MaintenanceController@create');
  Route::get('maintenance/{vm_id}','MaintenanceController@show');
  Route::get('maintenance/edit_rencana/{vm_id}','MaintenanceController@edit_rencana');
  Route::post('maintenance/store_pengajuan/{vehicle_id}','MaintenanceController@store_pengajuan')->middleware('closing:maintenance');
  Route::post('maintenance/store_rencana/{vm_id}','MaintenanceController@store_rencana')->middleware('closing:maintenance');
  Route::post('maintenance/store_selesai/{vm_id}','MaintenanceController@store_selesai')->middleware('closing:maintenance');
  Route::post('maintenance/store_detail/{vm_id}','MaintenanceController@store_detail')->middleware('closing:maintenance');
  Route::post('maintenance/go_perawatan/{vm_id}','MaintenanceController@go_perawatan')->middleware('closing:maintenance');
  Route::post('maintenance/store_item_detail/{vmd_id}','MaintenanceController@store_item_detail')->middleware('closing:maintenance');
  Route::delete('maintenance/delete_detail/{detail_id}','MaintenanceController@delete_detail')->middleware('closing:maintenance');
  Route::delete('maintenance/delete_maintenance','MaintenanceController@delete_maintenance')->middleware('closing:maintenance');
  Route::get('vehicle/driver/{vid}','VehicleController@driver');
  Route::get('vehicle/print/{id}/{date}','VehicleController@print');
  Route::get('vehicle/body/{vid}','VehicleController@body');
  Route::get('vehicle/insurance/{vid}','VehicleController@insurance');
  Route::get('vehicle/insurance_detail/{id}','VehicleController@insurance_detail');
  Route::put('vehicle/insurance_detail/{id}','VehicleController@edit_insurance');
  Route::delete('vehicle/insurance_detail/{id}','VehicleController@delete_insurance');
  Route::get('vehicle/document/{vid}','VehicleController@document');
  Route::get('vehicle/rate/{vid}','VehicleController@rate');
  Route::get('vehicle/card/{id}','VehicleController@card');
  Route::post('vehicle/store_driver/{vid}','VehicleController@store_driver');
  Route::post('vehicle/store_insurance/{vid}','VehicleController@store_insurance');
  Route::post('vehicle/store_document/{vid}','VehicleController@store_document');
  Route::post('vehicle/store_rate/{vid}','VehicleController@store_rate');
  Route::post('vehicle/store_detail_rate','VehicleController@store_detail_rate');
  Route::post('vehicle/delete_document','VehicleController@delete_document');
  Route::resource('vehicle','VehicleController');
  Route::resource('vehicle_distance','VehicleDistanceController');
  Route::resource('vehicle_check','VehicleCheckController');
});

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'driver',
  'as' => 'driver.',
  'namespace' => 'Driver',
], function(){
  Route::post('driver/store_application/{id}','DriverController@store_application');
  Route::post('driver/store_vehicle/{id}','DriverController@store_vehicle');
  Route::post('driver/upload_file/{id}','DriverController@upload_file');
  Route::delete('driver/delete_vehicle/{id}','DriverController@delete_vehicle');
  Route::get('driver/vehicle_list/{id}','DriverController@vehicle_list');
  Route::get('driver/{id}/history','DriverController@history');
  Route::resource('driver','DriverController');
});

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'vendor',
  'as' => 'vendor.',
  'namespace' => 'Vendor',
], function(){
  Route::get('register_vendor/create_price/{id}','VendorRegisterController@create_price');
  Route::get('register_vendor/edit_price/{id}','VendorRegisterController@edit_price');
  Route::post('register_vendor/store_price/{id}','VendorRegisterController@store_price');
  Route::post('register_vendor/update_price/{id}','VendorRegisterController@update_price');
  Route::get('register_vendor/document/{id}','VendorRegisterController@document');
  Route::post('register_vendor/upload_file/{id}','VendorRegisterController@upload_file');
  Route::post('register_vendor/approve/{id}','VendorRegisterController@approve');
  Route::post('register_vendor/delete_file/{id}','VendorRegisterController@delete_file');
  Route::resource('register_vendor','VendorRegisterController');
});

Route::group([
    'middleware' => ['web','auth'],
    'prefix' => 'sales',
    'as' => 'sales.',
    'namespace' => 'Sales',
], function(){
    Route::get('sales_order/detail/datatable','SalesOrderController@detailDatatable');
    Route::get('sales_order/{id}/approve','SalesOrderController@approve');
    Route::get('sales_order/{id}/reject','SalesOrderController@reject');
    Route::get('sales_order/{id}/detail','SalesOrderController@showDetail');
    Route::get('sales_order/{id}/detail/{sales_order_detail_id}','SalesOrderController@showDetailInfo');
    Route::resource('sales_order','SalesOrderController');
    Route::get('customer_order/detail/datatable','CustomerOrderController@detailDatatable');
    Route::get('customer_order/{id}/detail','CustomerOrderController@showDetail');
    Route::get('customer_order/{id}/file','CustomerOrderController@showFile');
    Route::delete('customer_order/{id}/file','CustomerOrderController@deleteFile');
    Route::get('customer_order/{id}/detail/{customer_order_detail_id}','CustomerOrderController@showDetailInfo');
    Route::get('customer_order/{id}/approve','CustomerOrderController@approve');
    Route::get('customer_order/{id}/reject','CustomerOrderController@reject');
    Route::post('customer_order/{id}/upload_file','CustomerOrderController@uploadFile');
    Route::resource('customer_order','CustomerOrderController');
});

Route::group([
    'middleware' => ['web','auth'],
    'prefix' => 'depo',
    'as' => 'depo.',
    'namespace' => 'Depo',
], function(){
    Route::get('container_inspection/{id}/detail','ContainerInspectionController@showDetail');
    Route::resource('container_inspection','ContainerInspectionController');

    Route::put('gate_in_container/{id}/approve','GateInContainerController@approve');
    Route::resource('gate_in_container','GateInContainerController');

    Route::put('movement_container/{id}/approve','MovementContainerController@approve');
    Route::get('movement_container/{id}/detail','MovementContainerController@showDetail');
    Route::resource('movement_container','MovementContainerController');
});

/* Marketing Routes */
require dirname(__FILE__).'/section/marketing.php';

/* Finance Routes */
require dirname(__FILE__).'/section/finance.php';

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'pdf',
  'as' => 'pdf.',
  'namespace' => 'Export',
], function(){
  Route::get('print_quotation/{id}','PdfController@print_quotation');
  Route::get('print_sales_order/{id}','PdfController@print_sales_order');
  Route::get('unpaid_cost','PdfController@unpaid_cost');
  Route::get('cost_balance','PdfController@cost_balance');
});

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'excel',
  'as' => 'excel.',
  'namespace' => 'Export',
], function(){
  Route::get('stock_transaction_export','ExportExcelController@stock_transaction_export');
  Route::get('area_export','ExportExcelController@area_export');
  Route::get('work_order_export','ExportExcelController@work_order_export');
  Route::get('company_export','ExportExcelController@company_export');
  Route::get('lead_export','ExportExcelController@lead_export');
  Route::get('price_list_export','ExportExcelController@price_list_export');
  Route::get('price_list_vendor','ExportExcelController@price_list_vendor');
  Route::get('customer_list_export','ExportExcelController@customer_list_export');
  Route::get('contract_export','ExportExcelController@contract_export');
  Route::get('quotation_export','ExportExcelController@quotation_export');
  Route::get('work_order_export','ExportExcelController@work_order_export');
  Route::get('inquery_export','ExportExcelController@inquery_export');
  Route::get('opportunity_export','ExportExcelController@opportunity_export');
  Route::get('contract_price_export','ExportExcelController@contract_price_export');
  Route::get('user_management_export','ExportExcelController@user_management_export');
  Route::get('account_export','ExportExcelController@account_export');
  Route::get('region_export','ExportExcelController@region_export');
  Route::get('route_cost_export','ExportExcelController@route_cost_export');
  Route::get('container_cost_export','ExportExcelController@container_cost_export');
  Route::get('route_export','ExportExcelController@route_export');
  // Kontak
  Route::get('kontak_export','ExportExcelController@kontak_export');
  Route::get('vendor_export','ExportExcelController@vendor_export');
  Route::get('driver_export','ExportExcelController@driver_export');
  Route::get('customer_export','ExportExcelController@customer_export');
  // Operational
  Route::get('jadwalkapal_export','ExportExcelController@jadwalkapal_export');
  Route::get('container_export','ExportExcelController@container_export');
  Route::get('joborder_export','ExportExcelController@joborder_export');
  Route::get('invoicejual_export','ExportExcelController@invoicejual_export');
  Route::get('PL_FTL_export','ExportExcelController@PL_FTL_export');
  Route::get('PL_FCL_export','ExportExcelController@PL_FCL_export');
  Route::get('SJ_Drivers_export','ExportExcelController@SJ_Drivers_export');
  Route::get('Invoice_export','ExportExcelController@Invoice_export');


  Route::get('bank_export','ExportExcelController@bank_export');
  Route::get('kapal_export','ExportExcelController@kapal_export');
  Route::get('satuan_export','ExportExcelController@satuan_export');
  Route::get('komoditas_export','ExportExcelController@komoditas_export');
  Route::get('tipe_kontainer_export','ExportExcelController@tipe_kontainer_export');
  Route::get('tipe_alamat_export','ExportExcelController@tipe_alamat_export');
  Route::get('kategori_vendor_export','ExportExcelController@kategori_vendor_export');
  Route::get('layanan_export','ExportExcelController@layanan_export');
  Route::get('layanan_warehouse_export','ExportExcelController@layanan_warehouse_export');
  Route::get('customer_stage_export','ExportExcelController@customer_stage_export');
  Route::get('dermaga_export','ExportExcelController@dermaga_export');
  Route::get('saldo_akun_export','ExportExcelController@saldo_akun_export');
  Route::get('jenis_biaya_export','ExportExcelController@jenis_biaya_export');
  Route::get('jenis_perawatan_export','ExportExcelController@jenis_perawatan_export');
  Route::get('tipe_kendaraan_export','ExportExcelController@tipe_kendaraan_export');
  Route::get('parikan_kendaraan_export','ExportExcelController@parikan_kendaraan_export');
  Route::get('kepemilikan_kendaraan_export','ExportExcelController@kepemilikan_kendaraan_export');
  Route::get('sumbu_posisi_ban_export','ExportExcelController@sumbu_posisi_ban_export');
  Route::get('variant_kendaraan_export','ExportExcelController@variant_kendaraan_export');
  Route::get('pengecekan_kendaraan_export','ExportExcelController@pengecekan_kendaraan_export');
  Route::get('body_kendaraan_export','ExportExcelController@body_kendaraan_export');
  Route::get('tipe_ban_export','ExportExcelController@tipe_ban_export');
  Route::get('ukuran_ban_export','ExportExcelController@ukuran_ban_export');
  Route::get('invoice_jual_export','ExportExcelController@invoice_jual_export');
  Route::get('invoice_vendor_export','ExportExcelController@invoice_vendor_export');
  Route::get('progress_operasional_export','ExportExcelController@progress_operasional_export');
  Route::get('daftar_gudang_export','ExportExcelController@daftar_gudang_export');
  Route::get('daftar_rak_export','ExportExcelController@daftar_rak_export');
  Route::get('penerimaan_barang_export','ExportExcelController@penerimaan_barang_export');
  Route::get('semua_kendaraan_export','ExportExcelController@semua_kendaraan_export');
  Route::get('kilometer_kendaraan_export','ExportExcelController@kilometer_kendaraan_export');
  Route::get('kendaraan_pengecekan_export','ExportExcelController@kendaraan_pengecekan_export');
  Route::get('register_vendor_export','ExportExcelController@register_vendor_export');
  // Route::get('job_order_vendor_export','ExportExcelController@job_order_vendor_export');
  Route::get('inventory_warehouse_export','ExportExcelController@inventory_warehouse_export');
  Route::get('inventory_kategori_export','ExportExcelController@inventory_kategori_export');
  Route::get('inventory_item_export','ExportExcelController@inventory_item_export');
  Route::get('persediaan_awal_export','ExportExcelController@persediaan_awal_export');
  Route::get('permintaan_pembelian_export','ExportExcelController@permintaan_pembelian_export');
  Route::get('pembelian_export','ExportExcelController@pembelian_export');
  Route::get('inventory_penerimaan_barang_export','ExportExcelController@inventory_penerimaan_barang_export');
  Route::get('penggunaan_barang_export','ExportExcelController@penggunaan_barang_export');
  Route::get('laporan_penerimaan_barang_export','ExportExcelController@laporan_penerimaan_barang_export');
  Route::get('penyesuaian_barang_export','ExportExcelController@penyesuaian_barang_export');
  Route::get('stok_gudang_export','ExportExcelController@stok_gudang_export');
  Route::get('retur_barang_export','ExportExcelController@retur_barang_export');
  Route::get('asset_group_export','ExportExcelController@asset_group_export');
  Route::get('saldo_awal_asset_export','ExportExcelController@saldo_awal_asset_export');
  Route::get('um_supplier_export','ExportExcelController@um_supplier_export');
  Route::get('um_customer_export','ExportExcelController@um_customer_export');
  Route::get('draft_list_piutang_export','ExportExcelController@draft_list_piutang_export');
  Route::get('draf_pelunasan_hutang_export','ExportExcelController@draf_pelunasan_hutang_export');
  Route::get('pelunasan_hutang_export','ExportExcelController@pelunasan_hutang_export');
  Route::get('draf_penagihan_hutang_export','ExportExcelController@draf_penagihan_hutang_export');
  Route::get('penagihan_hutang_export','ExportExcelController@penagihan_hutang_export');
  Route::get('nota_potong_penjualan_export','ExportExcelController@nota_potong_penjualan_export');
  Route::get('nota_potong_pembelian_export','ExportExcelController@nota_potong_pembelian_export');
  Route::get('cek_giro_export','ExportExcelController@cek_giro_export');
  Route::get('realisasi_mutasi_export','ExportExcelController@realisasi_mutasi_export');
  Route::get('permintaan_mutasi_export','ExportExcelController@permintaan_mutasi_export');
  Route::get('transaksi_kas_bank_export','ExportExcelController@transaksi_kas_bank_export');
  Route::get('pengajuan_biaya_export','ExportExcelController@pengajuan_biaya_export');
  Route::get('cash_count_export','ExportExcelController@cash_count_export');
  Route::get('kas_bon_export','ExportExcelController@kas_bon_export');
  Route::get('setting_keuangan_saldo_hutang_export','ExportExcelController@setting_keuangan_saldo_hutang_export');
  Route::get('setting_keuangan_saldo_piutang_export','ExportExcelController@setting_keuangan_saldo_piutang_export');
  Route::get('setting_keuangan_um_supplier_export','ExportExcelController@setting_keuangan_um_supplier_export');
  Route::get('warehouse_putaway_export','ExportExcelController@warehouse_putaway_export');


});

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'operational_warehouse',
  'as' => 'operational_warehouse.',
  'namespace' => 'OperationalWarehouse',
], function(){
  Route::get('setting/rack','SettingController@rack');
  Route::get('setting/rack/{id}','SettingController@detail_rack');
  Route::get('setting/rack/{id}/qrcode','SettingController@showRackQRCode');
  Route::get('setting/warehouse','SettingController@warehouse');
  Route::get('setting/category_pallet_list','SettingController@category_pallet_list');

  Route::post('setting/store_warehouse','SettingController@store_warehouse');
  Route::post('setting/store_pallet_category','SettingController@store_pallet_category');
  Route::post('setting/store_storage_type','SettingController@store_storage_type');
  Route::post('pallet_purchase_order_return/approve/{id}','PurchaseOrderReturnController@approve');
  Route::post('pallet_sales_order/approve/{id}','PalletSalesOrderController@approve');
  Route::post('pallet_deletion/approve/{id}','PalletDeletionController@approve');
  Route::post('pallet_sales_order/store_detail','PalletSalesOrderController@store_detail');
  Route::post('pallet_migration/store_detail','PalletMigrationController@store_detail');

  Route::post('mutasi_transfer/{id}/receipt','MutasiTransferController@storeReceipt');
  Route::post('mutasi_transfer/store_detail','MutasiTransferController@store_detail');
  Route::post('pallet_deletion/store_detail','PalletDeletionController@store_detail');
  Route::post('pallet_migration/delete_detail/{id}','PalletMigrationController@delete_detail');
  Route::post('mutasi_transfer/delete_detail/{id}','MutasiTransferController@delete_detail');
  Route::post('pallet_deletion/delete_detail/{id}','PalletDeletionController@delete_detail');
  Route::post('pallet_migration/item_out/{id}','PalletMigrationController@item_out');
  Route::post('pallet_migration/item_in/{id}','PalletMigrationController@item_in');
  Route::post('mutasi_transfer/item_out/{id}','MutasiTransferController@item_out');
  Route::post('mutasi_transfer/item_in/{id}','MutasiTransferController@item_in');
  Route::post('putaway/item_out/{id}','PutawayController@item_out');
  Route::post('putaway/item_in/{id}','PutawayController@item_in');
  Route::put('putaway/store_detail','PutawayController@store_detail');

  Route::delete('setting/delete_rack/{id}','SettingController@delete_rack');
  Route::delete('setting/delete_warehouse/{id}','SettingController@delete_warehouse');

  Route::resource('putaway','PutawayController');

  Route::put('receipt_detail/{id}/approve_quality','ReceiptDetailController@approveQuality');
  Route::put('receipt_detail/{id}/reject_quality','ReceiptDetailController@rejectQuality');

  Route::get('receipt/warehouse','ReceiptController@warehouse');
  Route::get('receipt/rack','ReceiptController@rack');
  Route::put('receipt/{id}/cancel','ReceiptController@cancel');
  Route::get('receipt/{id}/job_order','ReceiptController@showJobOrder');
  Route::get('receipt/{customer_id}/job_order_pengiriman','ReceiptController@showJobOrderPengiriman');
  Route::get('receipt/{id}/manifest','ReceiptController@showManifest');
  Route::get('receipt/{id}/detail','ReceiptController@showDetail');
  Route::get('receipt/detail/{id}/barcode','ReceiptController@showBarcode');
  Route::delete('receipt/delete_detail/{id}','ReceiptController@delete_detail');
  Route::post('receipt/store_detail/{id}','ReceiptController@store_detail');
  Route::put('receipt/update_detail/{id}','ReceiptController@update_detail');
  Route::get('receipt/print/{id}','ReceiptController@print');
  Route::post('receipt/approve/{id}','ReceiptController@approve');
  Route::post('receipt/update/{id}','ReceiptController@update');
  Route::get('receipt/{receipt_id}/attachment','ReceiptController@show_attachment');
  Route::post('receipt/{receipt_id}/attachment','ReceiptController@store_attachment');
  Route::post('receipt/{id}/send_email','ReceiptController@sendEmail');
  Route::get('receipt/{id}/preview_email','ReceiptController@previewEmail');
  Route::delete('receipt/{receipt_id}/attachment/{delivery_order_photo_id}','ReceiptController@destroy_attachment');
  Route::get('receipt/download_import_item','ReceiptController@downloadImportItem');
  Route::post('receipt/import_item','ReceiptController@importItem');

  Route::get('stocklist/excel','StocklistController@excel');
  Route::get('stocklist/{id}','StocklistController@show');

  Route::resource('receipt','ReceiptController');

  Route::resource('pallet_purchase_order_return','PurchaseOrderReturnController');
  Route::resource('pallet_sales_order','PalletSalesOrderController');

  Route::post('pallet_sales_order_return/{id}/receipt','SalesOrderReturnController@storeReceipt');
  Route::resource('pallet_sales_order_return','SalesOrderReturnController');

  Route::resource('pallet_migration','PalletMigrationController');
  Route::resource('mutasi_transfer','MutasiTransferController');
  Route::resource('pallet_deletion','PalletDeletionController');
});

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'inventory',
  'as' => 'inventory.',
  'namespace' => 'Inventory',
], function(){
    Route::resource('item_condition','ItemConditionController');
    Route::get('purchase_request/cari_gudang','PurchaseRequestController@cari_gudang');
    Route::get('purchase_request/cari_kendaraan','PurchaseRequestController@cari_kendaraan');
  Route::post('purchase_request/approve/{id}','PurchaseRequestController@approve');
  Route::post('purchase_request/reject/{id}','PurchaseRequestController@reject');
  Route::post('purchase_request/create_po/{id}','PurchaseRequestController@create_po');
  Route::post('purchase_request/store_detail/{id}','PurchaseRequestController@store_detail');
  Route::delete('purchase_request/delete_detail/{id}','PurchaseRequestController@delete_detail');

  Route::get('adjustment/cari_item','AdjustmentController@cari_item');
  Route::get('retur/cari_penerimaan/{id}','ReturController@cari_penerimaan');
  Route::get('retur/cari_list/{id}','ReturController@cari_list');
  Route::get('retur/receive/{id}','ReturController@receive');
  Route::post('retur/store_receive/{id}','ReturController@store_receive')->middleware('closing:retur');
  Route::put('retur/{id}/approve','ReturController@approve')->middleware('closing:retur');


  Route::resource('category','CategoryController');
  Route::post('item/upload-picture/{id}','ItemController@uploadPicture');
  Route::get('item/get-pictures/{id}','ItemController@get_pictures');
  Route::get('item/cekStok','ItemController@cekStok');
  Route::get('item/general_item   ','ItemController@general_item');
  Route::get('item/surat_jalan','ItemController@surat_jalan');
  Route::get('item/rack','ItemController@rack');

  Route::get('item/cek_stok_warehouse','ItemController@cek_stok_warehouse');
  Route::get('item/datatable','ItemController@datatable');

  Route::post('report/export','ReportController@export');
  Route::get('report/preview','ReportController@preview');

  // Rack / bin location controller
  Route::get('rack/suggestion/descending','RackController@getSuggestionDescending');
  Route::get('rack/suggestion/ascending','RackController@getSuggestionAscending');
  Route::put('rack/{id}/map/{warehouse_map_id}','RackController@setMap');
  Route::resource('rack','RackController');
  // End of rack / bin location controller

  // Warehouse controller
  Route::patch('warehouse/{id}/generate_map','WarehouseController@generateMap');
  Route::get('warehouse/{id}/map','WarehouseController@indexMap');
  Route::get('warehouse/{id}/map_list','WarehouseController@mapList');
  Route::resource('warehouse','WarehouseController');
  // End of warehouse controller

  Route::resource('item','ItemController');
  Route::resource('stock_initial','StockInitialController')->middleware('closing:stockInitial');
  Route::resource('purchase_request','PurchaseRequestController');

  Route::put('purchase_order/{id}/approve','PurchaseOrderController@approve');
  Route::get('purchase_order/status','PurchaseOrderController@indexStatus');
  Route::resource('purchase_order','PurchaseOrderController');

  Route::resource('adjustment','AdjustmentController');
  Route::put('using_item/{id}/approve','UsingItemController@approve');
  Route::resource('using_item','UsingItemController');
  Route::resource('retur','ReturController')->middleware('closing:retur');
  Route::resource('report','ReportController');
});

Route::get('shipment','Operational\JobOrderController@print_out');

Route::group([
  'middleware' => ['web','auth'],
  'prefix' => 'operational',
  'as' => 'operational.',
  'namespace' => 'Operational',
], function(){
  // Route::resource('work_order','WorkOrderController');

  // Job order route
  Route::get('job_order/{id}/detail','JobOrderController@showDetail');
  Route::get('job_order/{id}/transits','JobOrderController@showTransits');
  Route::get('job_order/{id}/kpi_status','JobOrderController@showKpiStatus');
  Route::get('job_order/{id}/kpi_status/data','JobOrderController@showKpiStatusData');
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
  Route::get('job_order/jo_margin_detail/{id}','JobOrderController@jo_margin_detail');
  Route::post('job_order/add_armada/{id}','JobOrderController@add_armada')->middleware('closing:jobOrder');
  Route::post('job_order/add_item/{id}','JobOrderController@add_item')->middleware('closing:jobOrder');
  Route::post('job_order/add_item_warehouse/{id}','JobOrderController@add_item_warehouse');
  Route::post('job_order/add_cost/{id}','JobOrderController@add_cost')->middleware('closing:jobOrder');
  Route::get('job_order/{id}/cost','JobOrderController@showCost')->middleware('closing:jobOrder');
  Route::post('job_order/add_receipt/{id}','JobOrderController@add_receipt')->middleware('closing:jobOrder');
  Route::post('job_order/add_status/{id}','JobOrderController@add_status')->middleware('closing:jobOrder');
  Route::put('job_order/add_status/{id}/auto','JobOrderController@autoAddStatus')->middleware('closing:jobOrder');
  Route::post('job_order/upload_file/{id}','JobOrderController@upload_file')->middleware('closing:jobOrder');
  Route::post('job_order/store_submission/{id}','JobOrderController@store_submission')->middleware('closing:jobOrder');
  Route::post('job_order/store_voyage_vessel/{man}','JobOrderController@store_voyage_vessel')->middleware('closing:jobOrder');
  Route::post('job_order/store_revision/{id}','JobOrderController@store_revision')->middleware('closing:jobOrder');
  Route::post('job_order/send_notification','JobOrderController@send_notification')->middleware('closing:jobOrder');
  Route::post('job_order/store_archive','JobOrderController@store_archive')->middleware('closing:jobOrder');
  Route::put('job_order/update_status','JobOrderController@update_status')->middleware('closing:jobOrder');
  Route::post('job_order/change_service/{id}','JobOrderController@change_service')->middleware('closing:jobOrder');
  Route::post('job_order/ajukan_atasan','JobOrderController@ajukan_atasan')->middleware('closing:jobOrder');
  Route::post('job_order/approve_atasan','JobOrderController@approve_atasan')->middleware('closing:jobOrder');
  Route::post('job_order/reject_atasan','JobOrderController@reject_atasan')->middleware('closing:jobOrder');

  Route::post('job_order/cost_journal','JobOrderController@cost_journal')->middleware('closing:jobOrder');
  Route::post('job_order/cancel_cost_journal/{cost_id}','JobOrderController@cancel_cost_journal')->middleware('closing:jobOrder');
  Route::post('job_order/submit_armada_lcl/{id}','JobOrderController@submit_armada_lcl')->middleware('closing:jobOrder');
  Route::delete('job_order/delete_item/{id}','JobOrderController@delete_item')->middleware('closing:jobOrder');
  Route::delete('job_order/delete_cost/{id}','JobOrderController@delete_cost')->middleware('closing:jobOrder');
  Route::delete('job_order/delete_status/{id}','JobOrderController@delete_status')->middleware('closing:jobOrder');
  Route::delete('job_order/delete_file/{id}','JobOrderController@delete_file')->middleware('closing:jobOrder');
  Route::delete('job_order/delete_armada/{id}','JobOrderController@delete_armada')->middleware('closing:jobOrder');
  Route::get('job_order/download_import_item','JobOrderController@downloadImportItem');
  Route::post('job_order/import_item_warehouse','JobOrderController@importItemWarehouse');
  // End of job order route

  Route::get('manifest_ftl/source','ManifestFTLController@indexSource');
  
  Route::post('manifest_ftl/ajukan_atasan','ManifestFTLController@ajukan_atasan')->middleware('closing:manifest');
  Route::get('manifest_ftl/{id}/cost','ManifestFTLController@show_cost');
  Route::put('manifest_ftl/{id}/additional','ManifestFTLController@storeAdditional');
  Route::get('manifest_ftl/edit_cost/{id}','ManifestFTLController@edit_cost');
  Route::delete('manifest_ftl/delete_cost/{id}','ManifestFTLController@delete_cost');
  Route::post('manifest_ftl/approve_atasan','ManifestFTLController@approve_atasan')->middleware('closing:manifest');
  Route::post('manifest_ftl/reject_atasan','ManifestFTLController@reject_atasan')->middleware('closing:manifest');
  Route::post('manifest_ftl/cost_journal','ManifestFTLController@cost_journal')->middleware('closing:manifest');
  Route::post('manifest_ftl/cancel_cost_journal/{cost_id}','ManifestFTLController@cancel_cost_journal')->middleware('closing:manifest');
  Route::post('manifest_ftl/add_cost/{id}','ManifestFTLController@add_cost')->middleware('closing:manifest');
  Route::post('manifest_ftl/add_item/{id}','ManifestFTLController@add_item')->middleware('closing:manifest');
  Route::post('manifest_ftl/store_delivery/{id}','ManifestFTLController@store_delivery')->middleware('closing:manifest');
  Route::post('manifest_ftl/store_submission/{id}','ManifestFTLController@store_submission')->middleware('closing:manifest');
  Route::post('manifest_ftl/change_depart_arrive/{id}','ManifestFTLController@change_depart_arrive')->middleware('closing:manifest');
  Route::post('manifest_ftl/update_delivery/{id}','ManifestFTLController@update_delivery')->middleware('closing:manifest');
  Route::delete('manifest_ftl/delete_detail/{id}','ManifestFTLController@delete_detail')->middleware('closing:manifest');
  Route::get('manifest_ftl/list_job_order/{id}','ManifestFTLController@list_job_order');
  Route::get('manifest_ftl/list_customer_manifest','ManifestFTLController@list_customer_manifest');
  Route::get('manifest_ftl/create_delivery/{id}','ManifestFTLController@create_delivery');
  Route::get('manifest_ftl/cari_kendaraan/{id}','ManifestFTLController@cari_kendaraan');
  Route::get('manifest_ftl/edit_delivery/{id}','ManifestFTLController@edit_delivery');
  Route::get('manifest_ftl/print_sj/{id}','ManifestFTLController@print_sj');
  Route::post('manifest_ftl/cancel_delivery/{id}','ManifestFTLController@cancel_delivery');

  Route::get('invoice_jual/get_job_order_costs','InvoiceJualController@get_job_order_costs');
  Route::get('invoice_jual/cari_customer_list/{company_id}','InvoiceJualController@cari_customer_list');
  Route::get('invoice_jual/{id}/detail','InvoiceJualController@showDetail');
  Route::get('invoice_jual/cari_jo/{id}','InvoiceJualController@cari_jo');
  Route::get('invoice_jual/cari_wo/{id}','InvoiceJualController@cari_wo');
  Route::get('invoice_jual/cari_default_akun','InvoiceJualController@cari_default_akun');
  Route::get('invoice_jual/cari_wo_collectible/{id}','InvoiceJualController@cari_wo_collectible');
  Route::post('invoice_jual/posting/{id}','InvoiceJualController@posting')->middleware('closing:invoice');
  Route::post('invoice_jual/approve/{id}','InvoiceJualController@approve')->middleware('closing:invoice');
  Route::get('invoice_jual/print/{id}','InvoiceJualController@print');
  Route::get('invoice_jual/cari_invoice','InvoiceJualController@cari_invoice');
  Route::post('invoice_jual/cari_jo_cost','InvoiceJualController@cari_jo_cost');
  Route::post('invoice_jual/cancel_posting/{id}','InvoiceJualController@cancel_posting');
  Route::get('invoice_jual/print_wo_gabungan','InvoiceJualController@print_wo_gabungan');
  Route::get('invoice_jual/jo_list','InvoiceJualController@jo_list');

  Route::get('manifest_fcl/change_vessel/{id}','ManifestFCLController@change_vessel');
  Route::post('manifest_fcl/store_vessel/{id}','ManifestFCLController@store_vessel')->middleware('closing:manifest');
  Route::post('manifest_fcl/store_revision/{id}','ManifestFCLController@store_revision')->middleware('closing:manifest');
  Route::post('manifest_fcl/store_edit/{id}','ManifestFCLController@store_edit')->middleware('closing:manifest');
  Route::delete('manifest_fcl/delete_price/{id}','ManifestFCLController@delete_price')->middleware('closing:manifest');
  Route::post('manifest_fcl/submit_price/{id}','ManifestFCLController@submit_price')->middleware('closing:manifest');
  Route::post('manifest_fcl/change_stuff_strip/{container_id}','ManifestFCLController@change_stuff_strip')->middleware('closing:manifest');
  Route::post('manifest_fcl/store_vehicle/{id}','ManifestFCLController@store_vehicle');

  Route::get('progress_operasional/cari_status_by_jo/{id}','ProgressController@cari_status_by_jo');
  Route::get('report/index_shipment_instruction','ReportController@index_shipment_instruction');
  Route::post('report/shipment_instruction','ReportController@shipment_instruction');

  Route::get('report/export_bdv/{id}','ReportController@export_bdv');
  Route::post('report/export','ReportController@export')->middleware(['compare_date']);
  Route::post('report/export_pdf','ReportController@export_pdf')->middleware(['compare_date']);
  Route::get('report/preview','ReportController@preview')->middleware(['compare_date']);
  Route::get('report/show_cost','ReportController@show_cost');

  Route::get('voyage_schedule/index_voyage_schedule','VoyageScheduleController@indexVoyageSchedule');
  Route::post('voyage_schedule/{id}/receipt','VoyageScheduleController@storeReceipt');
  Route::put('vendor_job/{source}/{id}','VendorJobController@storeStatus');
  Route::get('container/{id}/check_invoice','ContainerController@checkInvoice');
  Route::resource('voyage_schedule','VoyageScheduleController');
  Route::resource('container','ContainerController');
  Route::resource('job_order','JobOrderController');
  Route::resource('job_order/{job_order_id}/transit','JobOrderTransitController')->middleware('closing:jobOrder');
  Route::resource('manifest_ftl','ManifestFTLController')->middleware('closing:manifest');
  Route::resource('manifest_fcl','ManifestFCLController')->middleware('closing:manifest');
  Route::resource('invoice_jual','InvoiceJualController')->middleware('closing:invoice');
  Route::resource('progress_operasional','ProgressController');

  Route::get('invoice_vendor/get_jo_cost/{id}','InvoiceVendorController@get_jo_cost');
  Route::put('invoice_vendor/approve/{id}/{flag?}','InvoiceVendorController@approve')->middleware('closing:invoice');
  Route::put('invoice_vendor/abort_journal/{id}/{flag?}','InvoiceVendorController@abort_journal')->middleware('closing:invoice');
  Route::resource('invoice_vendor','InvoiceVendorController')->middleware('closing:invoice');
  Route::resource('delivery_order_driver','DeliveryOrderDriverController');
  Route::resource('report','ReportController');


  Route::resource('claim_categories','ClaimCategoryController');
  Route::resource('claims','ClaimController');
  Route::get('claims/{id}/detail','ClaimController@showDetail');
  Route::put('claims/{id}/approve','ClaimController@approve');

  Route::get('tracking_map', function(){
    return view('operational.map.map');
  });
});
