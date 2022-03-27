<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\JobOrder;
use DB;

class MakeQrOnJo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qrcode:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate QR Code on Job Order';

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
      $jo=DB::table('job_orders')->where('uniqid', null)->selectRaw('id,code')->get();
      foreach ($jo as $key => $value) {
        JobOrder::find($value->id)->update([
          'uniqid' => str_random(30)
        ]);
        $this->info("JO $value->code telah diupdate");
      }
      DB::commit();
    }
}
