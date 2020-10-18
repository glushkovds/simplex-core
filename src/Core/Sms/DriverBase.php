<?php

namespace Simplex\Core\Sms;

use Simplex\Core\DB;
use Simplex\Core\Time;

abstract class DriverBase
{

    const ERROR_NO_MONEY = 1;

    const STATE_NEW = 'new';
    const STATE_SEND = 'send';
    const STATE_DELIVERY = 'delivery';
    const STATE_ERROR = 'error';

    /** @var array */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Отправка СМС
     * @param string $phone
     * @param string $text
     * @param string $from
     * @return array  bool success, mixed driver_sms_id, float price, string error_text, int error_code
     */
    abstract public function send($phone, $text, $from, $smsId = 0);

    /**
     * Получение статуса СМС
     * @param mixed $driverSmsId Идентификатор СМС в системе сервиса СМС-отправки
     * @return array string state, string error_text, time
     */
    public function getState($driverSmsId)
    {
        throw new \BadMethodCallException('Not implemented!');
    }

    /**
     * Чтение входящих сообщений о доставке
     */
    public function readDeliveries()
    {
        throw new \BadMethodCallException('Not implemented!');
    }

    /**
     * @param string $driverSmsId
     * @param array $result [string state, mixed time, error_code, error_text]
     */
    protected function updateState($driverSmsId, $result)
    {
        $set = ["state = '{$result['state']}'"];
        if (self::STATE_DELIVERY == $result['state']) {
            $time = Time::mysql($result['time']);
            $set[] = "time_delivery = '$time'";
        }
        if (self::STATE_ERROR == $result['state']) {
            $set[] = "error_code = " . (int)$result['error_code'];
            $set[] = "error_text = '{$result['error_text']}'";
        }
        $q = "
            UPDATE sms_log SET " . implode(', ', $set) . " 
            WHERE driver_sms_id = '$driverSmsId'
            AND driver_id = {$this->config['driver_id']}
        ";
        DB::query($q);
    }

}