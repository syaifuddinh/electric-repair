<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\Quotation;
use App\Model\Notification;
use App\Model\NotificationUser;
use App\Model\Invoice;
use App\Model\InvoiceVendor;
use Carbon\Carbon;
use DB;

class PushNotif extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek dan Trigger Notifikasi';

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
      //invoices
      $invoices=DB::table('invoices')
      ->leftJoin('contacts','contacts.id','=','invoices.customer_id')
      ->where('invoices.due_date','>',Carbon::now()->format('Y-m-d'))
      ->where('invoices.status','<',5)
      ->select([
        'invoices.id',
        'contacts.name as customer',
        'invoices.code',
        'invoices.due_date',
        'invoices.slug',
        'invoices.company_id'
      ])->get();
      $reminder_invoice=DB::table('reminder_types')->where('id', 1)->first();
      foreach ($invoices as $value) {
        $due=Carbon::parse($value->due_date);
        $limit=Carbon::now()->addDays($reminder_invoice->interval);
        $now=Carbon::now();
        $diff=Carbon::parse($value->due_date)->diffInDays(Carbon::now());
        if ($now<$due && $due<$limit) {
          DB::beginTransaction();

          $slug=str_random(6);
          if (!$value->slug) {
            Invoice::find($value->id)->update([
              'slug' => $slug
            ]);
          } else {
            $slug=$value->slug;
          }
          $userList=DB::table('notification_type_users')
          ->leftJoin('users','users.id','=','notification_type_users.user_id')
          ->whereRaw("notification_type_users.notification_type_id = 10")
          ->select('users.id','users.is_admin','users.company_id')->get();
          $n=Notification::create([
            'notification_type_id' => 10,
            'name' => 'Ada Invoice yang akan mendekati jatuh tempo!',
            'description' => 'No. Invoice '.$value->code.', '.$value->customer.' akan jatuh tempo dalam '.$diff.' hari',
            'slug' => $slug,
            'route' => 'operational.invoice_jual.show',
            'parameter' => json_encode(['id' => $value->id])
          ]);
          foreach ($userList as $un) {
            if ($un->is_admin) {
              NotificationUser::create([
                'notification_id' => $n->id,
                'user_id' => $un->id
              ]);
            } else {
              if ($un->company_id==$value->company_id) {
                NotificationUser::create([
                  'notification_id' => $n->id,
                  'user_id' => $un->id
                ]);
              }
              //abaikan
            }
          }
          $this->info("Invoice $value->code telah dikirim notifikasi");
          DB::commit();
        }
      }

      // invoice vendor --------------------------------------------
      $invoice_vendors=DB::table('invoice_vendors')
      ->leftJoin('contacts','contacts.id','=','invoice_vendors.vendor_id')
      ->where('invoice_vendors.date_invoice','>',Carbon::now()->format('Y-m-d'))
      ->where('invoice_vendors.status',1)
      ->select([
        'invoice_vendors.id',
        'contacts.name as customer',
        'invoice_vendors.code',
        'invoice_vendors.date_invoice as due_date',
        'invoice_vendors.slug',
        'invoice_vendors.company_id'
      ])->get();
      $reminder_invoice=DB::table('reminder_types')->where('id', 2)->first();
      foreach ($invoice_vendors as $value) {
        $due=Carbon::parse($value->due_date);
        $limit=Carbon::now()->addDays($reminder_invoice->interval);
        $now=Carbon::now();
        $diff=Carbon::parse($value->due_date)->diffInDays(Carbon::now());
        if ($now<$due && $due<$limit) {
          DB::beginTransaction();

          $slug=str_random(6);
          if (!$value->slug) {
            InvoiceVendor::find($value->id)->update([
              'slug' => $slug
            ]);
          } else {
            $slug=$value->slug;
          }
          $userList=DB::table('notification_type_users')
          ->leftJoin('users','users.id','=','notification_type_users.user_id')
          ->whereRaw("notification_type_users.notification_type_id = 9")
          ->select('users.id','users.is_admin','users.company_id')->get();
          $n=Notification::create([
            'notification_type_id' => 9,
            'name' => 'Ada Invoice Vendor yang akan mendekati jatuh tempo!',
            'description' => 'No. Invoice '.$value->code.', '.$value->customer.' akan jatuh tempo dalam '.$diff.' hari',
            'slug' => $slug,
            'route' => 'operational.invoice_vendor.show',
            'parameter' => json_encode(['id' => $value->id])
          ]);
          foreach ($userList as $un) {
            if ($un->is_admin) {
              NotificationUser::create([
                'notification_id' => $n->id,
                'user_id' => $un->id
              ]);
            } else {
              if ($un->company_id==$value->company_id) {
                NotificationUser::create([
                  'notification_id' => $n->id,
                  'user_id' => $un->id
                ]);
              }
              //abaikan
            }
          }
          $this->info("Invoice Vendor $value->code telah dikirim notifikasi");
          DB::commit();
        }
      }
    }
}
