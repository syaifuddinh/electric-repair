<?php

use Illuminate\Database\Seeder;

class ClearManifestSeeder extends Seeder 
{

	
    public function run()
    {
        DB::table('vehicles')->update([
            'delivery_id' => null
        ]);
        DB::table('delivery_order_drivers')->delete();
        DB::table('manifest_details')->delete();
        DB::table('manifests')->delete();
    }

}