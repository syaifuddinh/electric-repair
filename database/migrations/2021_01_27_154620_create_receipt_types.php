<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReceiptTypes extends Migration
{
    protected $table = 'receipt_types';
    protected $params = [
        [
            'code' => 'ro1',
            'name' => 'Pembelian (Purchasing Order)'
        ],
        [
            'code' => 'ro2',
            'name' => 'Retur Barang atas Pembelian (Reject Order/Retur Barang)'
        ],
        [
            'code' => 'ro3',
            'name' => 'Mutasi / Stock Transfer'
        ],
        [
            'code' => 'ro4',
            'name' => 'Inbound Delivery (barang dari customer yg akan di kelola)'
        ],
        [
            'code' => 'ro5',
            'name' => 'Outbound Delivery (Barang yg tidak jadi dikirim)'
        ],
        [
            'code' => 'ro6',
            'name' => 'Retur Barang atas Perbaikan/Perawatan (Maintenance Service Order)'
        ],
        [
            'code' => 'ro7',
            'name' => 'Retur Barang atas Penjualan/Sales Order'
        ],
        [
            'code' => 'ro8',
            'name' => 'Retur Barang Sisa Produksi'
        ]
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipt_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 10)->nullable(false)->index();
            $table->string('name', 50)->nullable(false);
        });

        DB::table($this->table)
        ->insert($this->params);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('receipt_types');
    }
}
