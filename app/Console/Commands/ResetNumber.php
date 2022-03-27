<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\CompanyNumbering;
use DB;

class ResetNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:numbering';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Format Penomoran ke 1';

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
      CompanyNumbering::where('id','!=',0)->update([
        'last_value' => 1
      ]);
      DB::commit();
    }
}
