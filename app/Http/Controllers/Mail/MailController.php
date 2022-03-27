<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class MailController extends Controller
{
  public function test_mail()
  {
    Mail::to('superhafizh@gmail.com')->send(new TestMail("Fajar"));
    
    return "Email Sent!";
  }
}
