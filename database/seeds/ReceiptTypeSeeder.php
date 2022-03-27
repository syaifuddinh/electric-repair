<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ReceiptTypeSeeder extends Seeder
{
    protected $params = [
        [
            'code' => 'r01',
            'name' => 'Pembelian (Purchasing Order)'
        ],
        [
            'code' => 'r02',
            'name' => 'Retur Barang atas Pembelian (Reject Order/Retur Barang)'
        ],
        [
            'code' => 'r03',
            'name' => 'Mutasi / Stock Transfer'
        ],
        [
            'code' => 'r04',
            'name' => 'Inbound Delivery (barang dari customer yg akan di kelola)'
        ],
        [
            'code' => 'r05',
            'name' => 'Outbound Delivery (Barang yg tidak jadi dikirim)'
        ],
        [
            'code' => 'r06',
            'name' => 'Retur Barang atas Perbaikan/Perawatan (Maintenance Service Order)'
        ],
        [
            'code' => 'r07',
            'name' => 'Retur Barang atas Penjualan/Sales Order'
        ],
        [
            'code' => 'r08',
            'name' => 'Retur Barang Sisa Produksi'
        ],
        [
            'code' => 'r09',
            'name' => 'Persediaan Awal'
        ],
        [
            'code' => 'r10',
            'name' => 'Voyage scheduled / jadwal kapal'
        ]
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $params = $this->params;
        Schema::disableForeignKeyConstraints();

        DB::table('receipt_types')->delete();

        foreach ($params as $param) {

            DB::table('receipt_types')->insert([
                'code' => $param['code'],
                'name' => $param['name']
            ]);
        }
    }
}
