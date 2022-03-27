<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\Receivable;
use App\Model\Invoice;
use DB;

class InvoiceReceivable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receivable:invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger Pelunasan Invoice';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::beginTransaction();
        //LUNAS
        $data=DB::table('receivables')->whereRaw('debet > 0 and credit >= debet')->where('type_transaction_id',26)->get();
        foreach ($data as $key => $value) {
          Invoice::where('id', $value->relation_id)->update([
            'status' => 5
          ]);
        }

        //DIBAYAR SEBAGIAN
        $data=DB::table('receivables')->whereRaw('credit > 0 and credit < debet')->where('type_transaction_id',26)->get();
        foreach ($data as $key => $value) {
          Invoice::where('id', $value->relation_id)->update([
            'status' => 4
          ]);
        }

        DB::commit();
        $this->info('Invoice Berhasil diupdate');
    }
}
