<?php

use Illuminate\Database\Seeder;

class ClearDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('manifests')
        ->update([
            'container_id' => null
        ]);

        DB::table('vehicles')
        ->update([
            'delivery_id' => null
        ]);
        DB::table('delivery_order_drivers')->delete();

        DB::table('cash_transactions')->delete();
        DB::table('cash_transaction_details')->delete();

        DB::table('nota_debets')->delete();
        DB::table('nota_credits')->delete();

        DB::table('sales_order_return_details')->delete();
        DB::table('sales_order_returns')->delete();

        DB::table('invoice_details')->delete();
        DB::table('invoices')->delete();

        DB::table('invoice_vendor_details')->delete();
        DB::table('invoice_vendors')->delete();

        DB::table('job_order_costs')->delete();
        DB::table('job_order_details')->delete();
        DB::table('job_orders')->delete();

        DB::table('work_order_details')->delete();
        DB::table('work_orders')->delete();

        DB::table('quotation_details')
        ->update([
            'price_list_id' => null
        ]);
        DB::table('price_list_costs')->delete();
        DB::table('price_lists')->delete();

        DB::table('leads')->delete();


        DB::table('quotations')
        ->update([
            'parent_id' => null
        ]);
        DB::table('quotation_items')->delete();
        DB::table('quotation_costs')->delete();
        DB::table('quotation_details')->delete();
        DB::table('quotations')->delete();

        DB::table('item_migration_details')->delete();
        DB::table('item_migrations')->delete();

        DB::table('picking_details')
        ->update([
            'requested_stock_transaction_id' => null
        ]);
        DB::table('picking_details')->delete();
        DB::table('pickings')->delete();

        DB::table('packaging_old_items')->delete();
        DB::table('packaging_new_items')->delete();
        DB::table('packagings')->delete();

        DB::table('stock_initials')->delete();
        DB::table('stock_transactions_report')->delete();
        DB::table('warehouse_stock_details')->delete();
        DB::table('warehouse_stocks')->delete();
        DB::table('stock_transactions')->delete();


        DB::table('retur_details')->delete();
        DB::table('returs')->delete();

        DB::table('item_deletion_details')->delete();
        DB::table('item_deletions')->delete();

        DB::table('vehicle_maintenance_details')->delete();
        DB::table('vehicle_maintenances')->delete();

        DB::table('stok_opname_warehouse_details')->delete();
        DB::table('stok_opname_warehouses')->delete();

        DB::table('gate_in_containers')->delete();
        
        DB::table('warehouse_receipt_details')->delete();
        DB::table('warehouse_receipts')->delete();



        
        DB::table('receipt_details')->delete();
        DB::table('receipts')->delete();
        DB::table('inqueries')->delete();
        DB::table('movement_containers')->delete();
        DB::table('inquery_customers')->delete();
        DB::table('containers')->delete();
        DB::table('voyage_schedules')->delete();
        DB::table('vessels')->delete();
        DB::table('sales_order_details')->delete();
        DB::table('sales_orders')->delete();
        DB::table('container_inspection_details')->delete();
        DB::table('container_inspections')->delete();
        DB::table('pallet_usage_details')->delete();
        DB::table('pallet_usages')->delete();
        DB::table('stock_adjustment_details')->delete();
        DB::table('stock_adjustments')->delete();
        DB::table('using_item_details')->delete();
        DB::table('using_items')->delete();
        DB::table('purchase_order_details')->delete();
        DB::table('purchase_orders')->delete();
        DB::table('purchase_request_details')->delete();
        DB::table('purchase_requests')->delete();
        DB::table('items')->delete();
        DB::table('categories')
        ->update([
            'parent_id' => null
        ]);
        DB::table('categories')->delete();
        DB::table('vendor_prices')->delete();
        DB::table('cost_types')
        ->update([
            'parent_id' => null
        ]);
        DB::table('cost_types')->delete();
        DB::table('bill_payments')->delete();
        DB::table('bill_details')->delete();
        DB::table('bills')->delete();
        DB::table('invoice_taxes')->delete();
        DB::table('tax_invoices')->delete();
        DB::table('cash_migrations')->delete();
        DB::table('payable_details')->delete();
        DB::table('payables')->delete();
        DB::table('receivable_details')->delete();
        DB::table('receivables')->delete();
        DB::table('cash_advance_reports')->delete();
        DB::table('cash_advance_statuses')->delete();
        DB::table('cash_advances')->delete();
        DB::table('kpi_statuses')->delete();

        DB::table('manifest_costs')->delete();
        DB::table('manifest_details')->delete();
        DB::table('manifests')->delete();

        DB::table('vehicles')
        ->update([
            'delivery_id' => null
        ]);
        DB::table('delivery_order_drivers')->delete();
        DB::table('handling_vehicles')->delete();
        DB::table('stuffing_vehicles')->delete();
        DB::table('vehicle_checklist_detail_bodies')->delete();
        DB::table('vehicle_checklist_detail_items')->delete();
        DB::table('vehicle_checklist_items')->delete();
        DB::table('vehicle_checklists')->delete();
        DB::table('vehicle_contacts')->delete();
        DB::table('vehicle_distances')->delete();
        DB::table('vehicle_documents')->delete();
        DB::table('vehicle_insurances')->delete();
        DB::table('vehicle_maintenance_details')->delete();
        DB::table('vehicle_maintenance_types')->delete();
        DB::table('vehicle_maintenances')->delete();
        DB::table('vehicles')->delete();
        DB::table('vehicle_variants')->delete();
        DB::table('vehicle_joints')->delete();
        DB::table('vehicle_manufacturers')->delete();
        DB::table('vehicle_owners')->delete();
        DB::table('vehicle_bodies')->delete();
        DB::table('vehicle_types')->delete();
        DB::table('cash_transaction_details')->delete();
        DB::table('cash_transactions')->delete();
        
        DB::table('route_costs')->delete();
        DB::table('routes')->delete();
        DB::table('customer_prices')->delete();
        DB::table('combined_prices')->delete();
        DB::table('debt_details')->delete();
        DB::table('debts')->delete();
        DB::table('asset_purchases')->delete();
        DB::table('asset_purchase_details')->delete();
        DB::table('asset_sales_details')->delete();
        DB::table('asset_sales')->delete();
        DB::table('asset_afkirs')->delete();
        DB::table('asset_depreciations')->delete();
        DB::table('assets')->delete();
        DB::table('asset_groups')->delete();
        DB::table('cek_giros')->delete();
        DB::table('nota_credits')->delete();

        DB::table('journal_details')->delete();
        DB::table('journals')->delete();

        

        DB::table('cash_count_details')->delete();
        DB::table('cash_counts')->delete();
        $user = DB::table('users')
        ->where('username', '!=', 'admin')
        ->first();
        if($user) {
            DB::table('user_roles')
            ->where('user_id', '!=', $user->id)
            ->delete();
            DB::table('users')
            ->where('username', '!=', 'admin')
            ->delete();
        }

        DB::table('contact_documents')->delete();
        DB::table('contact_addresses')->delete();
        DB::table('contact_files')->delete();

        $contact = DB::table('contacts')
        ->first();
        if($contact) {
            DB::table('contacts')
            ->update([
                'parent_id' => null 
            ]);

            DB::table('contacts')
            ->where('id', '!=', $contact->id)
            ->delete();
        }
    }
}
