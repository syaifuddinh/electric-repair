<?php

Route::group([
    'middleware' => ['web','auth'],
    'prefix' => 'finance',
    'as' => 'finance.',
    'namespace' => 'Finance',
], function(){
    Route::post('journal/approve_post','JournalController@approvePost');
    Route::post('journal/approve','JournalController@approve');
    Route::put('journal/undo_approve/{id}','JournalController@undo_approve');
    Route::post('journal/store_favorite','JournalController@store_favorite');
    Route::post('journal/posting_rev/{id}','JournalController@posting_rev');
    Route::post('journal/store_posting','JournalController@store_posting');
    Route::put('journal/unposting','JournalController@unposting');
    Route::post('asset/depreciate/{id}', 'AssetController@depreciate');
    Route::get('journal/create_favorite','JournalController@create_favorite');
    Route::post('cash_transaction/approve/{id}','CashTransactionController@approve')->middleware('closing:cashIn');
    Route::post('cash_transaction/reject/{id}','CashTransactionController@reject');
    Route::put('cash_transaction/detail/manifest/{cash_transaction_detail_id}','CashTransactionController@update_manifest')->middleware('closing:cashIn');
    Route::post('cash_transaction/upload_bukti','CashTransactionController@uploadBukti')->middleware('closing:cashIn');
    Route::delete('cash_transaction/delete_bukti','CashTransactionController@deleteBukti');
    Route::delete('cash_transaction/delete_detail/{id}','CashTransactionController@delete_detail');
    Route::get('asset/find/{id}','AssetController@find');
    Route::post('asset/depreciate/{id}', 'AssetController@depreciate');
    Route::post('asset_afkir/approve/{id}','AssetAfkirController@approve');
    Route::post('asset_purchase/approve/{id}', 'AssetPurchaseController@approve');
    Route::delete('asset_sales/delete_detail/{id}', 'AssetSalesController@deleteDetail');
    Route::post('asset_sales/approve/{id}', 'AssetSalesController@approve');
    Route::delete('asset_sales/delete_detail/{id}', 'AssetSalesController@deleteDetail');
    Route::post('um_supplier/{id}/return_sisa','UmSupplierController@returnSisa');
    Route::post('um_customer/{id}/return_sisa','UmCustomerController@returnSisa');
    Route::resource('journal','JournalController');
    Route::resource('cash_transaction','CashTransactionController')->middleware('closing:cashIn');
    Route::resource('cek_giro','CekGiroController')->middleware('closing:giro');
    Route::resource('um_supplier','UmSupplierController');
    Route::resource('um_customer','UmCustomerController');
    Route::resource('cash_migration','CashMigrationController');
    Route::resource('asset_group','AssetGroupController');
    Route::resource('asset','AssetController');
    Route::resource('asset_purchase','AssetPurchaseController');
    Route::resource('asset_depreciation','AssetDepreciationController');
    Route::resource('asset_afkir','AssetAfkirController');
    Route::resource('asset_sales','AssetSalesController');
    Route::get('nota_credit/cari_piutang/{id}','NotaCreditController@cari_piutang');
    Route::resource('nota_credit','NotaCreditController')->middleware('closing:notaCredit');
    Route::get('nota_debet/cari_hutang/{id}','NotaDebetController@cari_hutang');
    Route::get('bill_receivable/payment/{id}','BillReceivableController@payment');
    Route::post('bill_receivable/store_payment/{id}','BillReceivableController@store_payment')->middleware('closing:billReceivablePayment');
    Route::post('bill_receivable/validasiBP/{id}','BillReceivableController@validasiBP');

    Route::post('bill_receivable/uploadBP/{id}','BillReceivableController@uploadBP');

    Route::post('bill_receivable/store_pengantar_invoice','BillReceivableController@store_pi_data');
    Route::get('bill_receivable/print_pengantar_invoice/{id}','BillReceivableController@print_pengantar_invoice');
    Route::post('bill_receivable/{id}/file','BillReceivableController@storeFile');
    Route::get('bill_receivable/{id}/file','BillReceivableController@indexFile');
    Route::delete('bill_receivable/{id}/file/{bill_file_id}','BillReceivableController@destroyFile');

    Route::post('cash_migration/reject/{id}','CashMigrationController@reject');
    Route::post('cash_migration/approve/{id}','CashMigrationController@approve');
    Route::post('cash_migration/approve_direction/{id}','CashMigrationController@approve_direction');
    Route::post('cash_migration/realisation/{id}','CashMigrationController@realisation');

    Route::get('debt_payable/cari_supplier_list/{id}','DebtPayableController@cari_supplier_list');
    Route::get('debt_payable/payment/{id}','DebtPayableController@payment');
    Route::post('debt_payable/uploadBP/{id}','DebtPayableController@uploadBP');
    Route::post('debt_payable/validasiBP/{id}','DebtPayableController@validasiBP');
    Route::post('debt_payable/store_payment/{id}','DebtPayableController@store_payment')->middleware('closing:debtPayablePayment');
    Route::delete('debt_payable/{id}/detail','DebtPayableController@destroyDebtDetailById')->middleware('closing:debtPayablePayment');
    Route::get('debt_payable/draft_list/{id}','DebtPayableController@draftListHutangDetail');

    Route::resource('nota_debet','NotaDebetController')->middleware('closing:notaDebet');
    Route::resource('bill_receivable','BillReceivableController')->middleware('closing:billReceivablePayment');
    Route::resource('debt_payable','DebtPayableController')->middleware('closing:debtPayablePayment');

    Route::post('submission_cost/approve/{id}','SubmissionCostController@approve');
    Route::post('submission_cost/revisi/{id}','SubmissionCostController@revisi');
    Route::post('submission_cost/reject/{id}','SubmissionCostController@reject');
    Route::post('submission_cost/posting/{id}','SubmissionCostController@posting');
    Route::post('submission_cost/cancel_approve/{id}','SubmissionCostController@cancel_approve');
    Route::post('submission_cost/cancel_posting/{id}','SubmissionCostController@cancel_posting');
    Route::resource('submission_cost','SubmissionCostController');

    Route::get('cash_count/cari_saldo/{cid}','CashCountController@cari_saldo');
    Route::post('cash_count/toggle_freeze/{cid}','CashCountController@toggle_freeze');
    Route::post('cash_count/approve/{id}','CashCountController@approve');

    Route::post('kas_bon/approve/{id}','KasBonController@approve')->middleware('closing:kasbon');
    Route::post('kas_bon/cancel/{id}','KasBonController@cancel')->middleware('closing:kasbon');
    Route::post('kas_bon/cash_out/{id}','KasBonController@cash_out')->middleware('closing:kasbon');
    Route::post('kas_bon/activate/{id}','KasBonController@activate')->middleware('closing:kasbon');
    Route::post('kas_bon/close/{id}','KasBonController@close')->middleware('closing:kasbon');
    Route::post('kas_bon/reapproval/{id}','KasBonController@reapproval')->middleware('closing:kasbon');
    Route::post('kas_bon/reapprove/{id}','KasBonController@reapprove')->middleware('closing:kasbon');
    Route::resource('cash_count','CashCountController');
    Route::resource('kas_bon','KasBonController')->middleware('closing:kasbon');

    Route::post('closing/{id}/posting','ClosingController@posting');
    Route::put('closing/{id}/rollback','ClosingController@rollback');
    Route::delete('closing/{id}/posting','ClosingController@cancelPosting');
    Route::resource('closing','ClosingController');

    Route::resource('pajak','PajakController');

    Route::get('report/journal','ReportController@journal');
    Route::get('report/ledger','ReportController@ledger');
    Route::get('report/ledger_receivable','ReportController@ledger_receivable');
    Route::get('report/ledger_payable','ReportController@ledger_payable');
    Route::get('report/ledger_um_supplier','ReportController@ledger_um_supplier');
    Route::get('report/ledger_um_customer','ReportController@ledger_um_customer');
    Route::get('report/neraca_saldo','ReportController@neraca_saldo');
    Route::get('report/laba_rugi','ReportController@laba_rugi');
    Route::get('report/ekuitas','ReportController@ekuitas');
    Route::get('report/posisi_keuangan','ReportController@posisi_keuangan');
    Route::get('report/outstanding_debt','ReportController@outstanding_debt');
    Route::get('report/outstanding_credit','ReportController@outstanding_credit');
    Route::get('report/laba_rugi_perbandingan','ReportController@laba_rugi_perbandingan');
    Route::get('report/arus_kas','ReportController@arus_kas');

    Route::get('report/export/account','ReportController@account');
    Route::get('report/export/journal','ReportController@export_journal');
    Route::get('report/export/ledger','ReportController@export_ledger');
    Route::get('report/export/ledger_receivable','ReportController@export_ledger_receivable');
    Route::get('report/export/ledger_payable','ReportController@export_ledger_payable');
    Route::get('report/export/ledger_um_supplier','ReportController@export_ledger_um_supplier');
    Route::get('report/export/ledger_um_customer','ReportController@export_ledger_um_customer');
    Route::get('report/export/neraca_saldo','ReportController@export_neraca_saldo');
    Route::get('report/export/neraca_saldo_banding','ReportController@export_neraca_saldo_banding');
    Route::get('report/export/laba_rugi','ReportController@export_laba_rugi');
    Route::get('report/export/ekuitas','ReportController@export_ekuitas');
    Route::get('report/export/ekuitas_banding','ReportController@export_ekuitas_banding');
    Route::get('report/export/posisi_keuangan','ReportController@export_posisi_keuangan');
    Route::get('report/export/posisi_keuangan_perbandingan','ReportController@export_posisi_keuangan_perbandingan');
    Route::get('report/export/outstanding_debt','ReportController@export_outstanding_debt');
    Route::get('report/export/outstanding_credit','ReportController@export_outstanding_credit');
    Route::get('report/export/laba_rugi_perbandingan','ReportController@export_laba_rugi_perbandingan');
    Route::get('report/export/arus_kas','ReportController@export_arus_kas');
    Route::get('report/export/arus_kas_perbandingan','ReportController@export_arus_kas_perbandingan');
    Route::get('report/export/neraca_lajur','ReportController@export_neraca_lajur');
    ;
});