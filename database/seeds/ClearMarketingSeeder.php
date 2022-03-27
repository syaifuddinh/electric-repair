<?php

use Illuminate\Database\Seeder;

class ClearMarketingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('quotations')
        ->update([
            'parent_id' => null
        ]);
        DB::table('leads')->delete();
        DB::table('work_order_details')->delete();
        DB::table('work_orders')->delete();
        DB::table('quotation_items')->delete();
        DB::table('quotation_costs')->delete();
        DB::table('quotation_details')->delete();
        DB::table('quotations')->delete();
        DB::table('inqueries')->delete();
        DB::table('price_lists')->delete();
    }
}
