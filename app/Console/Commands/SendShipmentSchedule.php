<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Abstracts\Setting\Email;
use App\Abstracts\Sales\SalesOrderDetail;
use App\Abstracts\Operational\Manifest;
use DB;
use Exception;

class SendShipmentSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shipment_schedule:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim jadwal lewat email';

    

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $shipments = Manifest::indexRequestedMail('sales_order');

            $shipments->each(function($v){
                $destinations = Manifest::getEmails($v->id);

                if($destinations) {
                    $template = Email::show();
                    $subject = $template->shipment_subject;
                    $body = $template->shipment_body;
                    $destination_name = $template->name;
                    Email::send($subject, $destinations, $destination_name, $body);
                    $this->info('Email berhasil dikirim');
                } else {
                    $this->info('Email kosong, tidak ada yang dikirim');
                }
            });

        } catch (Exception $e) {
            $this->info($e->getMessage());
        }

    }
}
