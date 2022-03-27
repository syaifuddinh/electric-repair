<?php

namespace App\Http\Library;

use Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Email implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $details;

    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Date : 28-05-2019
     * Description : Send email.
     * Developer : Halili
     * Status : Create
     */
    public function handle()
    {
        $data = array("messages" => $this->details['message']);
        $to_name = $this->details['name'];
        $to_email = $this->details['email'];
        $subject = $this->details['subject'];
        Mail::send('emails.mail', $data, function ($email) use ($to_name, $to_email, $subject) {
            $email->to($to_email, $to_name)
                ->subject($subject);
        });
    }
}
