<?php

namespace Simplex\Core\Sms;

use PhpSmpp\Service\Sender as SmppSender;
use PhpSmpp\SMPP\SMPP;
use PhpSmpp\SMPP\Unit\Sm;

/**
 * Class DriverSmpp
 * @require https://github.com/glushkovds/php-smpp
 */
class DriverSmpp extends DriverBase
{

    const STATES = [
        SMPP::STATE_ENROUTE => self::STATE_SEND,
        SMPP::STATE_DELIVERED => self::STATE_DELIVERY,
        SMPP::STATE_EXPIRED => self::STATE_ERROR,
        SMPP::STATE_DELETED => self::STATE_ERROR,
        SMPP::STATE_UNDELIVERABLE => self::STATE_ERROR,
        SMPP::STATE_ACCEPTED => self::STATE_SEND,
        SMPP::STATE_UNKNOWN => self::STATE_SEND,
        SMPP::STATE_REJECTED => self::STATE_ERROR,
    ];

    /**
     *
     * @param string $phone
     * @param string $text
     * @param string $from
     * @return boolean
     */
    public function send($phone, $text, $from, $smsId = 0)
    {
        try {
            $error = '';
            $errorCode = 0;
            $phone = PlugPhone::extract7($phone);
            $service = new SmppSender([$this->config['url']], $this->config['login'], $this->config['pass'], false);
            $service->client->csmsMethod = \PhpSmpp\SMPP\SmppClient::CSMS_8BIT_UDH;
            $driverSmsId = $service->send($phone, $text, 'CigMall');
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $errorCode = $e->getCode() ?: 500;
            $driverSmsId = 0;
        }

        if (!empty($smpp)) {
            try {
                $smpp->close();
            } catch (\Throwable $e) {

            }
        }

        return [
            'success' => !empty($driverSmsId),
            'driver_sms_id' => $driverSmsId,
            'price' => 1,
            'error_code' => $errorCode,
            'error_text' => $error
        ];
    }

    public function readDeliveries()
    {
        $service = new \PhpSmpp\Service\Listener([$this->config['url']], $this->config['login'], $this->config['pass'], true);
        $service->listenOnce(function (Sm $sm) {
//            file_put_contents('/tmp/' . microtime(true) . '.hex', $sm->getHex());
            if (!($sm instanceof \PhpSmpp\SMPP\Unit\DeliverReceiptSm)) {
                echo "not receipt\n";
                return;
            }

            $state = static::STATES[$sm->state] ?? static::STATE_ERROR;
            $this->updateState($sm->msgId, [
                'state' => $state,
                'time' => $sm->doneDate,
                'error_code' => $sm->receiptErrorCode,
                'error_text' => $sm->receiptErrorText,
            ]);
        });
    }

}
