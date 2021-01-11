<?php

namespace Simplex\Core;


class ConsoleBase
{

    public static function toBackground($job, $params = [])
    {
        $tmp = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $tmp[] = '--' . $key . '="' . $value . '"';
        }
        $paramsStr = implode(' ', $tmp);
        $command = "php {$_SERVER['DOCUMENT_ROOT']}/console.php $job $paramsStr > /dev/null 2>&1 &";
        Log::debug($command);
        exec($command);
    }

    public static function execInTime(callable $closure, float $seconds)
    {
        $timeStart = microtime(true);
        $closure();
        $diff = microtime(true) - $timeStart;
        $wait = $seconds - $diff;
        if ($wait > 0) {
            usleep($wait * 1e6);
        }
    }

}
