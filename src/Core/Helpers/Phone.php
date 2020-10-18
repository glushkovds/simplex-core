<?php

namespace Simplex\Core\Helpers;

class Phone
{

    /**
     * Извлекает номер телефона из строки, выдает в формате 89001002030
     * @param string $phone
     * @return double
     */
    public static function extract($phone)
    {
        $phone = preg_replace("@[^0-9]+@", "", $phone);
        $len = strlen($phone);
        if ($len === 10) {
            $phone = '8' . $phone;
        } elseif ($phone && $phone[0] == 7) {
            $phone[0] = 8;
        }
        if (substr($phone, 0, 3) == '889') {
            return 0;
        }
        return $len === 10 || $len === 11 ? $phone : 0;
    }

    /**
     * Извлекает номер телефона из строки, выдает в формате 79001002030
     * @param string $phone
     * @return double
     */
    public static function extract7($phone)
    {
        $phone = preg_replace("@[^0-9]+@", "", $phone);
        $len = strlen($phone);
        if ($len === 10) {
            $phone = '7' . $phone;
        } elseif ($phone && $phone[0] == 8) {
            $phone[0] = 7;
        }
        return $len === 10 || $len === 11 ? $phone : 0;
    }

    /**
     * Форматирует телефон в человекопонятный вид
     * @param bigint $phone
     * @return string
     */
    public static function format($phone)
    {
        $phone = self::extract($phone);
        if (!$phone) {
            return '';
        }
        if ('3412' === substr($phone, 1, 4)) {
            $phone = preg_replace("/(\d)(\d{4})(\d{2})(\d{2})(\d{2})/", "$1 ($2) $3-$4-$5", $phone);
        } else {
            $phone = preg_replace("/(\d)(\d{3})(\d{3})(\d{2})(\d{2})/", "$1 ($2) $3-$4-$5", $phone);
        }
        return $phone;
    }

    /**
     * Извлекает из текста телефонные номера
     * @param string $text
     * @param bool $format (optional = false) - формитировать в человеческий вид
     * @return array
     */
    public static function parse($text, $format = false)
    {
        $phones = array();
        $contacts = $text;
        $matches = array();
        preg_match_all("@[78][\-\s\(\)]{0,3}(\d{2,3}[\-\s\(\)]{0,3}){4}@", $contacts, $matches);
        if (count($matches[0])) {
            foreach ($matches[0] as $match) {
                $phone = preg_replace("@\D@", '', $match);
                if (strlen($phone) == 11) {
                    if ((string)$phone[0] === '7') {
                        $phone = '8' . substr($phone, 1);
                    }
                    $contacts = str_replace($match, '', $contacts);
                    $phones[] = $phone;
                }
            }
        }
        $matches = array();
        preg_match_all("@2(\d{2,3}[\-\s\(\)]{0,3}){3}@", $contacts, $matches);
        if (count($matches[0])) {
            foreach ($matches[0] as $match) {
                $phone = preg_replace("@\D@", '', $match);
                if (strlen($phone) == 7) {
                    $phones[] = '8342' . $phone;
                }
            }
        }

        if ($format) {
            foreach ($phones as $index => $phone) {
                $phones[$index] = self::format($phone);
            }
        }

        return $phones;
    }

}
