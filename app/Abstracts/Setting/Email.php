<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Setting\EmailLog;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_TransportException;

class Email 
{
    protected static $shipmentChips = ["shipment_code", "job_order_code", "customer_name", "etd", "eta"];
    protected static $table = 'emails';

    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }
    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show() {
        $dt = self::query()
        ->first();

        return $dt;
    }

    public static function fetch($args = []) {
        $params = [];
        $params['shipment_subject'] = $args['shipment_subject'] ?? null;
        $params['shipment_body'] = $args['shipment_body'] ?? null;
        $params['receipt_subject'] = $args['receipt_subject'] ?? null;
        $params['receipt_body'] = $args['receipt_body'] ?? null;
        $params['name'] = $args['name'] ?? null;
        $params['mail_driver'] = $args['mail_driver'] ?? null;
        $params['host'] = $args['host'] ?? null;
        $params['port'] = $args['port'] ?? 0;
        $params['username'] = $args['username'] ?? null;
        $params['password'] = $args['password'] ?? null;
        $params['encryption'] = $args['encryption'] ?? null;
        if($params['encryption']) {
            self::validateEncryption($params['encryption']);
            $params['encryption'] = strtolower($params['encryption']);
        }

        return $params;
    }

    public static function store($params = []) {
        $params = self::fetch($params);
        $dt = self::show();
        if($dt) {
            $params['updated_at'] = Carbon::now();
            self::query()->update($params);
        } else {
            $params['created_at'] = Carbon::now();
            self::query()->insert($params);
        }
    }

    public static function validateEncryption($v) {
        $v = strtolower($v);
        if($v != 'tls' && $v != 'ssl') {
            throw new Exception('Email encryption not found');
        }
    }

    public static function validateTransport() {
        $dt = self::show();
        if(!$dt) {
            throw new Exception('Email setting is not set');
        }
        if(!$dt->username) {
            throw new Exception('Username / email is not set');
        }
        if(!$dt->password) {
            throw new Exception('Password is not set');
        }
        if(!$dt->mail_driver) {
            throw new Exception('Mail driver is not set');
        }
        if(!$dt->host) {
            throw new Exception('Host is not set');
        }
        if(!$dt->port) {
            throw new Exception('Port is not set');
        }
        if(!$dt->encryption) {
            throw new Exception('Encryption is not set');
        }
    }

    public static function getTransport() {
        self::validateTransport();
        $dt = self::show();

        $transport = (new Swift_SmtpTransport($dt->host, $dt->port, $dt->encryption))
        ->setUsername($dt->username)
        ->setPassword($dt->password);

        $mailer = new Swift_Mailer($transport);

        return $mailer;
    }

    public static function send($subject, $destinations, $destination_name, $body) {
        $mailer = self::getTransport();
        $dt = self::show();
        $destinations = explode(';', $destinations);
        foreach($destinations as $destination) {
            $destination = trim($destination);
            $message = (new Swift_Message($subject))
            ->setFrom([$dt->username => $dt->name])
            ->setTo([$destination => $destination_name])
            ->setBody($body, 'text/html');
            $params = [];
            $params['status'] = 'OK';
            $params['destination'] = $destination;
            $params['subject'] = $subject;
            $params['body'] = $body;
            try {
                $mailer->send($message);
                EmailLog::store($params);
            } catch (\ErrorException $e) {
                $params['description'] = $e->getMessage();
                $params['status'] = 'ERROR';
                EmailLog::store($params);
                throw new Exception($e->getMessage());
            }
        }
    }

    /*
      Date : 16-03-2021
      Description : Menampilkan chips untuk konten template email shipment
      Developer : Didin
      Status : Create
    */
    public static function indexShipmentChip() {
        return self::$shipmentChips;
    }
}
