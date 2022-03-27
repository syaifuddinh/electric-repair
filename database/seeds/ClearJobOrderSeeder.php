<?php

use Illuminate\Database\Seeder;

class ClearJobOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('packaging_old_items')->delete();
        DB::table('packaging_new_items')->delete();
        DB::table('packagings')->delete();
        DB::table('item_migrations')->delete();
        DB::table('item_migration_details')->delete();
        DB::table('invoices')->delete();
        DB::table('invoice_details')->delete();
        DB::table('job_order_details')->delete();
        DB::table('job_orders')->delete();
        DB::table('picking_details')->delete();
        DB::table('pickings')->delete();
        DB::table('stok_opname_warehouse_details')->delete();
        DB::table('stok_opname_warehouses')->delete();

        DB::table('stock_transactions_report')
        ->delete();
        DB::table('warehouse_stock_details')
        ->delete();
        DB::table('warehouse_stocks')
        ->delete();
        DB::table('stock_transactions')
        ->delete();
        DB::table('warehouse_receipt_details')
        ->delete();
        DB::table('warehouse_receipts')
        ->delete();
    }
}
