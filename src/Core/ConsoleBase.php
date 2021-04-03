<?php

namespace Simplex\Core;


class ConsoleBase
{

    /**
     * @param string $job "ext/job" for example
     * @param array $params
     * @param int|null $maxSame Max parallel processes allowed
     * @param string|null $sameKey Used only with $maxSame parameter.
     *                             Optional, if empty it will be calculated as md5 of $job and $params
     * @param int|false $timeout max execution time in seconds. false = limitless
     */
    public static function toBackground($job, $params = [], $maxSame = null, $sameKey = null, $timeout = false)
    {
        $applyTimeout = is_int($timeout) && $timeout > 0;
        $tmp = [];
        if ($maxSame) {
            $sameKey || $sameKey = md5($job . serialize($params));
            if (static::findJobsCount($sameKey, $applyTimeout) >= $maxSame) {
                return;
            }
            $params['sameKey'] = $sameKey;
        }
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $tmp[] = '--' . $key . '="' . $value . '"';
        }
        $paramsStr = implode(' ', $tmp);
        $command = "php {$_SERVER['DOCUMENT_ROOT']}/console.php $job $paramsStr > /dev/null 2>&1 &";
        if ($applyTimeout) {
            $command = "timeout -s 9 {$timeout}s $command";
        }
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

    /**
     * @param string $key
     * @param bool $excludeTimeoutProcess
     * @return int
     */
    protected static function findJobsCount($key, $excludeTimeoutProcess = false)
    {
        $command = 'ps aux | grep "' . $key . '" | grep -v grep';
        if ($excludeTimeoutProcess) {
            $command .= ' | grep -v "timeout -s"';
        }
        $command .= ' | wc -l';
        exec($command, $output);
        return (int)trim(shell_exec($command));
    }

}
