<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;
use Carbon\Carbon;

class HitungJoCostManifestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $manifest_id;
    public function __construct($manifest_id)
    {
      $this->manifest_id=$manifest_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $totalTerangkut = 0;
      $manifestGroup = array();

      $mCost = DB::table('manifest_costs')->where('header_id', $this->manifest_id)->get();
      $m = DB::table('manifests')->whereId($this->manifest_id)->first();

      $dJo = DB::table('manifest_details as md');
      $dJo = $dJo->leftJoin('job_order_details as jod','jod.id','md.job_order_detail_id');
      $dJo = $dJo->groupBy('jod.header_id');
      $dJo = $dJo->where('md.header_id', $this->manifest_id);
      $dJo = $dJo->selectRaw('sum(md.transported) as terangkut, jod.header_id as jo_id, jod.weight as jo_tonase, jod.volume as jo_volume, (md.transported/jod.qty*jod.weight) as tonase_terangkut, (md.transported/jod.qty*jod.volume) as volume_terangkut');
      $dJo = $dJo->orderBy('jo_id','asc');
      $dJo = $dJo->chunk(50, function($chunk) use (&$totalTerangkut,&$manifestGroup){
        foreach ($chunk as $key => $value) {
          array_push($manifestGroup, $value);
          if ($value->volume_terangkut) {
            $totalTerangkut+=$value->volume_terangkut;
          } else {
            $totalTerangkut+=$value->tonase_terangkut;
          }
        }
      });
      DB::beginTransaction();
      foreach ($mCost as $value) {
        foreach ($manifestGroup as $mg) {
          if ($mg->volume_terangkut>0) {
            $biaya = $mg->volume_terangkut/$totalTerangkut*$value->total_price;
          } else {
            $biaya = $mg->tonase_terangkut/$totalTerangkut*$value->total_price;
          }
          $biaya = round($biaya, -2);
          if ($biaya<=0) {
            continue;
          }
          $cekJoCost = DB::table('job_order_costs')->where('header_id', $mg->jo_id)->where('manifest_cost_id', $value->id)->first();
          if ($cekJoCost) {
            DB::table('job_order_costs')->whereId($cekJoCost->id)->update([
              'qty' => 1,
              'price' => $biaya,
              'total_price' => $biaya,
              'status' => $value->status,
              'updated_at' => Carbon::now()
            ]);
          } else {
            DB::table('job_order_costs')->insert([
              'header_id' => $mg->jo_id,
              'cost_type_id' => $value->cost_type_id,
              'transaction_type_id' => 21,
              'vendor_id' => $value->vendor_id,
              'qty' => 1,
              'price' => $biaya,
              'total_price' => $biaya,
              'description' => "Biaya PL - No. {$m->code}",
              'type' => 1,
              'is_edit' => 1,
              'status' => $value->status,
              'quotation_costs' => $biaya,
              'create_by' => $value->create_by,
              'manifest_cost_id' => $value->id,
              'created_at' => Carbon::now(),
              'updated_at' => Carbon::now()
            ]);
          }
        }
      }
      DB::commit();
    }
}
