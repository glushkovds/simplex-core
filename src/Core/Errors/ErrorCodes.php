<?php

namespace Simplex\Core\Errors;


class ErrorCodes
{
    /** @var int Дефолтная ошибка приложения */
    const APP_INTERNAL_ERROR = 1000;

    const ERROR_MESSAGES = [
        self::APP_INTERNAL_ERROR => 'An internal server error has occurred. Try again later',
    ];

    /**
     * @param int $code
     * @param bool $withCode [optional = false]
     * @param array $params Дополнительные необязательные параметры, например, для кастомизации текста ошибки.
     * Например текст ошибки "Required parameter {param}", тогда можно подставить через параметры значение:
     * ['param' => 'My awesome param']
     * @return string
     */
    public static function getText($code, $withCode = false, $params = [])
    {
        if ($text = static::ERROR_MESSAGES[$code] ?? null) {
//            $text = Yii::t('app', $text);
        } elseif (method_exists(static::class, $method = "text$code")) {
            $text = static::$method();
        }
        if ($params) {
            $paramsPrepared = [];
            foreach ($params as $key => $value) {
                $paramsPrepared['{' . $key . '}'] = $value;
            }
            $text = strtr($text, $paramsPrepared);
        }
        return $text ? (new Error($text, $code))->toString($withCode) : '';
    }
}
