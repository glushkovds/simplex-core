<?php

namespace Simplex\Core;

use Simplex\Core\Helpers\Phone;
use Simplex\Core\Sms\DriverBase;


class Sms
{

    /** @var DriverBase */
    protected static $driver;

    /**
     *
     * @param string $phone
     * @param string $text
     * @param string $from [optional = ''] Если '', берем значение из настроек сайта: sms_sender
     * @param string $extra [optional = ''] - например номер заказа, чтобы потом можно было отследить
     * @param int $lastSendTimeout [optional = 0] Лимит времени в секундах после последней отправки смс на этот номер
     * @return boolean|string отправлено или нет, если сервис вернул ошибку, функция вернет эту ошибку в виде строки
     */
    public static function send($phone, $text, $from = '', $extra = '', $lastSendTimeout = 0)
    {

        if (!$from) {
            $from = Core::siteParam('sms_sender');
        }

        $phone = Phone::extract($phone);
        if (!$phone) {
            return 'Not correct phone';
        }

        $lastSendTimeout = (int)$lastSendTimeout;
        if ($lastSendTimeout) {
            $q = "
                SELECT * FROM sms_log WHERE phone = $phone 
                AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(time_create)) < $lastSendTimeout LIMIT 1
            ";
            $exist = DB::result($q);
            if ($exist) {
                return 'Last send too recently';
            }
        }

        $q = "
            INSERT INTO sms_log
            SET phone = $phone, text = '" . DB::escape($text) . "', sender = '" . DB::escape($from) . "', 
            extra = '" . DB::escape($extra) . "', time_create = now()
        ";
        $success = DB::query($q);
        if (!$success) {
            return 'Server error'; //false;
        }
        $smsId = DB::insertId();

        $result = self::sendById($smsId, $phone, $text, $from);
        return $result;
    }

    /**
     *
     * @param int $smsId
     * @return boolean|string отправлено или нет, если сервис вернул ошибку, функция вернет эту ошибку в виде строки
     */
    private static function sendById($smsId, $phone = false, $text = false, $from = false)
    {
        $smsId = (int)$smsId;
        if (!$smsId) {
            return 'no sms id';
        }
        if ($phone === false) {
            $q = "SELECT * FROM sms_log WHERE sms_id = $smsId";
            $row = DB::result($q);
            $phone = $row['phone'];
            $text = $row['text'];
            $from = $row['sender'];
        }

        if (empty(Core::siteParam('send_deny_sms'))) {
            $driverInstance = self::getDriver();
            if (empty($driverInstance)) {
                return false;
            }
            $sendResult = $driverInstance->send($phone, $text, $from, $smsId);
        } else {
            $sendResult = ['success' => true, 'driver_sms_id' => '999', 'price' => 1, 'error_text' => '', 'error_code' => 0];
        }

        $set = ["time_send = now(), state = '" . ($sendResult['success'] ? 'send' : 'error') . "'"];
        $set[] = "driver_id = {$driverInstance->getConfig()['driver_id']}";
        if (!empty($sendResult['error_text'])) {
            $set[] = "error_text = '" . DB::escape($sendResult['error_text']) . "'";
            $set[] = "error_code = " . (int)$sendResult['error_code'];
        } else {
            $set[] = "driver_sms_id = '{$sendResult['driver_sms_id']}', price = {$sendResult['price']}";
        }
        $q = "UPDATE sms_log set " . implode(', ', $set) . " WHERE sms_id = $smsId";
        DB::query($q);
        return !empty($sendResult['error_text']) ? $sendResult['error_text'] : true;
    }

    /**
     *
     * @return DriverBase
     */
    public static function getDriver()
    {
        if (empty(static::$driver)) {
            $q = "SELECT * FROM sms_driver WHERE active = 1 LIMIT 1";
            $driverInfo = DB::result($q);
            static::setDriverByInfo($driverInfo);
        }
        return static::$driver;
    }

    /**
     * @param array $driverInfo data from sms_driver
     * @return void
     */
    protected static function setDriverByInfo($driverInfo)
    {
        static::$driver = static::createDriverByInfo($driverInfo);
    }

    /**
     * @param array $driverInfo data from sms_driver
     * @return DriverBase
     */
    protected static function createDriverByInfo($driverInfo)
    {
        if (!isset($driverInfo['driver_id'])) {
            return null;
        }
        include_once dirname(__FILE__) . "/drivers/{$driverInfo['alias']}/driver.class.php";
        $driverClass = 'Driver' . ucfirst($driverInfo['alias']);
        return new $driverClass($driverInfo);
    }

    /**
     * @param string $driverAlias
     */
    public static function setDriver($driverAlias)
    {
        $driverAlias = DB::escape($driverAlias);
        $q = "SELECT * FROM sms_driver WHERE alias = '$driverAlias' LIMIT 1";
        $driverInfo = DB::result($q);
        static::setDriverByInfo($driverInfo);
    }

    public static function resend($smsId)
    {
//        return rand(0, 1) == 1 ? true : 'Нет денег';
        $result = self::sendById($smsId);
        return $result;
    }

    public static function renewStates()
    {
        $q = "SELECT * FROM sms_log WHERE state = 'send' AND driver_id IS NOT NULL AND driver_sms_id IS NOT NULL LIMIT 100";
        $rows = [];//DB::assoc($q);
        $q = "SELECT * FROM sms_driver WHERE active = 1";
        $drivers = DB::assoc($q, 'driver_id');
        foreach ($rows as $row) {
            if ($driver = $drivers[$row['driver_id']] ?? null) {
                self::renewState($row, $drivers[$row['driver_id']]);
            } else {
                $set = ["state = '" . DriverBase::STATE_ERROR . "'"];
                $set[] = "time_delivery = NOW()";
                $set[] = "error_text = 'Driver disabled'";
                $set[] = "error_code = 403";
                $q = "UPDATE sms_log SET " . implode(', ', $set) . " WHERE sms_id = {$row['sms_id']}";
                DB::query($q);
            }
        }
        foreach ($drivers as $driver) {
            $driverInstance = self::createDriverByInfo($driver);
            try {
                $driverInstance->readDeliveries();
            } catch (\BadMethodCallException $e) {

            }
        }
    }

    private static function renewState($sms, $driverInfo)
    {
        $driverInstance = self::getDriver($driverInfo);
        try {
            $result = $driverInstance->getState($sms['driver_sms_id']);
        } catch (\BadMethodCallException $e) {
            return;
        }
        $set = ["state = '{$result['state']}'"];
        if (DriverBase::STATE_DELIVERY == $result['state']) {
            $set[] = "time_delivery = '{$result['time']}'";
        }
        if (DriverBase::STATE_ERROR == $result['state']) {
            $set[] = "error_text = '{$result['error_text']}'";
        }
        $q = "UPDATE sms_log SET " . implode(', ', $set) . " WHERE sms_id = {$sms['sms_id']}";
        DB::query($q);
    }

    /**
     *
     * @return bool true - деньги кончились, false - все ок
     */
    public static function checkBalance()
    {
        $q = "SELECT error_code FROM sms_log ORDER BY sms_id DESC LIMIT 1";
        $lastErrorCode = (int)DB::result($q, 0);
        return DriverBase::ERROR_NO_MONEY === $lastErrorCode;
    }

}
