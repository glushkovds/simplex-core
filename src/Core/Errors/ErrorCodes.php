<?php

namespace Simplex\Core\Errors;


class ErrorCodes
{
    /** @var int Дефолтная ошибка приложения */
    const APP_INTERNAL_ERROR = 1000;

    /** @var int Метод не найден */
    const APP_METHOD_NOT_FOUND = 1001;

    /** @var int Неподдерживаемый тип ответа */
    const APP_UNSUPPORTED_RESPONSE_TYPE = 1002;

    /** @var int Неподдерживаемый ответ */
    const APP_UNSUPPORTED_RESPONSE = 1003;

    /** @var int Не авторизован */
    const APP_UNAUTHORIZED = 1004;

    const BASIC_ERROR_MESSAGES = [
        self::APP_INTERNAL_ERROR => 'An internal server error has occurred. Try again later',
        self::APP_METHOD_NOT_FOUND => 'Requested method was not found',
        self::APP_UNSUPPORTED_RESPONSE => 'Unsupported response: {content}',
        self::APP_UNSUPPORTED_RESPONSE_TYPE => 'Unsupported response type: {class}',
        self::APP_UNAUTHORIZED => 'Unauthorized'
    ];

    const ERROR_MESSAGES = [];

    /**
     * @param int $code
     * @param bool $withCode [optional = false]
     * @param array $params Дополнительные необязательные параметры, например, для кастомизации текста ошибки.
     * Например текст ошибки "Required parameter {param}", тогда можно подставить через параметры значение:
     * ['param' => 'My awesome param']
     * @return string
     */
    public static function getText(int $code, bool $withCode = false, array $params = []): string
    {
        $text = (static::ERROR_MESSAGES[$code] ?? static::BASIC_ERROR_MESSAGES[$code]) ?? '';

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
