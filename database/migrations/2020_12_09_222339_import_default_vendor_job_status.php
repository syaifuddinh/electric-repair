<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImportDefaultVendorJobStatus extends Migration
{
    protected $params = [
        [
            'editable' => 0,
            'priority' => 1,
            'name' => 'Draft',
            'slug' => 'draft',
        ],
        [
            'editable' => 0,
            'priority' => 9,
            'name' => 'Invoice',
            'slug' => 'invoice',
        ],
        [
            'editable' => 0,
            'priority' => 10,
            'name' => 'Paid',
            'slug' => 'paid',
        ]
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('vendor_job_statuses')
        ->insert($this->params);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->params as $p) {
            DB::table('vendor_job_statuses')
            ->whereSlug($p['slug'])
            ->delete();
        }
    }
}
