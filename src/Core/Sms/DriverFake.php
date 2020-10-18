<?php


namespace Simplex\Core\Sms;


class DriverFake extends DriverBase
{

    public function send($phone, $text, $from, $smsId = 0)
    {
        return true;
    }

}