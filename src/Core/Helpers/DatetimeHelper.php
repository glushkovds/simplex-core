<?php

namespace Simplex\Core\Helpers;


use DateTime;

class DatetimeHelper
{
    /**
     * @param string $time 01:30:00
     * @return string PT1H30M
     */
    public static function timeToIso8601Duration(string $time): string
    {
        $ret = "PT";
        $arr = array_reverse(explode(':', $time));
        $ct = count($arr);
        if ($h = (int)$arr[2] ?? 0) {
            $ret .= $h . 'H';
        }
        if ($h = (int)$arr[1] ?? 0) {
            $ret .= $h . 'M';
        }
        if ($h = (int)$arr[0] ?? 0) {
            $ret .= $h . 'S';
        }
        return $ret;
    }

    /**
     * @param string $time 01:30:00
     * @return int 5400
     */
    public static function timeToSeconds(string $time)
    {
        $ret = 0;
        $arr = array_reverse(explode(':', $time));
        if ($h = (int)$arr[2] ?? 0) {
            $ret += $h * 3600;
        }
        if ($h = (int)$arr[1] ?? 0) {
            $ret += $h * 60;
        }
        if ($h = (int)$arr[0] ?? 0) {
            $ret += $h;
        }
        return $ret;
    }

    /**
     * @param string $time PT1H30M
     * @return string 01:30:00
     * @throws \Exception
     */
    public static function iso8601DurationToSqlTime(string $time): string
    {
        return (new \DateInterval($time))->format('%H:%i:%s');
    }

    /**
     * @param string $time PT1H30M
     * @return \DateInterval
     * @throws \Exception
     */
    public static function iso8601DurationToDateInterval(string $time): \DateInterval
    {
        return (new \DateInterval($time));
    }

    /**
     * @param int $seconds
     * @return \DateInterval
     */
    public static function secondsToDateInterval(int $seconds): \DateInterval
    {
        return static::iso8601DurationToDateInterval(static::secondsToIso8601Duration($seconds));
    }

    /**
     * @param string $time PT1H30M
     * @return int seconds
     * @throws \Exception
     */
    public static function iso8601DurationToSeconds(string $time): int
    {
        return self::dateIntervalToSeconds(self::iso8601DurationToDateInterval($time));
    }

    /**
     * @param string $time PT1H30M
     * @param int $default
     * @return int seconds
     */
    public static function iso8601DurationToSecondsSilent(string $time, int $default): int
    {
        try {
            return self::dateIntervalToSeconds(self::iso8601DurationToDateInterval($time));
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * @param int $second 3630
     * @return string PT1H30S
     */
    public static function secondsToIso8601Duration(int $second): string
    {
        $remain = $second;
        $d = intval($remain / 3600 / 24);
        $remain -= $d * 3600 * 24;
        $h = intval($remain / 3600);
        $remain -= $h * 3600;
        $m = intval($remain / 60);
        $remain -= $m * 60;
        $s = $remain;
        $ret = 'P';
        if ($d) {
            $ret .= $d . 'D';
        }
        $time = '';
        if ($h) {
            $time .= $h . 'H';
        }
        if ($m) {
            $time .= $m . 'M';
        }
        if ((!$h && !$m) || $s) {
            $time .= $s . 'S';
        }
        if ($time) {
            $ret .= 'T' . $time;
        }
        return $ret;
    }

    /**
     * @param \DateInterval $dateInterval
     * @return int seconds
     * @throws \Exception
     */
    public static function dateIntervalToSeconds(\DateInterval $dateInterval): int
    {
        $reference = new \DateTimeImmutable;
        $endTime = $reference->add($dateInterval);
        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

    /**
     * @param $time 05:00:00
     * @return string 05:00Z
     * @throws \Exception
     */
    public static function sqlTimeToIso8601Time($time): string
    {
        return (new DateTime($time))->format('H:i') . 'Z';
    }

    /**
     * @param $time 05:00:00
     * @return string 05:00
     * @throws \Exception
     */
    public static function sqlTimeToShortSqlTime($time): string
    {
        return (new DateTime($time))->format('H:i');
    }

    /**
     * @param string $time 10:00+05
     * @return string 05:00:00
     * @throws \Exception
     */
    public static function iso8601TimeToSqlTime(string $time): string
    {
        return (new DateTime($time))->setTimezone(new \DateTimeZone('UTC'))->format('H:i:s');
    }

    /**
     * Also changes timezone to UTC
     * @param string $time
     * @return string 2007-03-02T19:00:00Z -> 2007-03-02 19:00:00,
     *                2007-03-02T19:00:00+01:00 -> 2007-03-02 18:00:00
     * @throws \Exception
     */
    public static function iso8601DateTimeToSql(string $time): string
    {
        return (new DateTime($time))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * @param string $time
     * @return string 2007-03-02T19:00:00Z -> 2007-03-02 19:00:00,
     *                2007-03-02T19:00:00+01:00 -> 2007-03-02 19:00:00
     * @throws \Exception
     */
    public static function iso8601DateTimeToSqlInLocalTz(string $time): string
    {
        return (new DateTime($time))->format('Y-m-d H:i:s');
    }

    /**
     * Convert any datetime format to DateTime instance
     * @param int|string|DateTime $mixed
     * @return DateTime
     * @throws \Exception
     */
    public static function toDateTime($mixed): DateTime
    {
        if (is_int($mixed)) {
            return new DateTime($mixed);
        }
        if ($mixed instanceof DateTime) {
            return $mixed;
        }
        try {
            if (is_string($mixed)) {
                return DateTime::createFromFormat('Y-m-d H:i:s', $mixed);
            }
        } catch (\Throwable $e) {
            // Will replace exception by next
        }
        throw new \Exception('Unknown datetime format: ' . var_export($mixed, true));
    }

    /**
     * Convert any datetime format to string Y-m-d H:i:s
     * Also changes timezone to UTC
     * @param int|string|DateTime $mixed
     * @return string
     * @throws \Exception
     */
    public static function toSqlFormat($mixed): string
    {
        $format = 'Y-m-d H:i:s';
        if (is_int($mixed)) {
            return (new DateTime($mixed))->setTimezone(new \DateTimeZone('UTC'))->format($format);
        }
        if ($mixed instanceof DateTime) {
            return $mixed->setTimezone(new \DateTimeZone('UTC'))->format($format);
        }
        try {
            if (is_string($mixed)) {
                return (new DateTime($mixed))->setTimezone(new \DateTimeZone('UTC'))->format($format);
            }
        } catch (\Throwable $e) {
            // Will replace exception by next
        }
        throw new \Exception('Unknown datetime format: ' . var_export($mixed, true));
    }

    /**
     * @param string $startDate 2000-01-01
     * @param string $endDate 2000-01-03
     * @return array [2000-01-01, 2000-01-02, 2000-01-03]
     * @throws \Exception
     */
//    public static function dateRange($startDate, $endDate)
//    {
//        $start = static::toDateTime($startDate);
//        $end = static::toDateTime($endDate);
//        if ($start->gt($end)) {
//            return [$startDate];
//        }
//        $dates = [];
//        $date = $start;
//        while ($date->lte($end)) {
//            $dates[] = $date->format('Y-m-d');
//            $date->addDay();
//        }
//        return $dates;
//    }

}
