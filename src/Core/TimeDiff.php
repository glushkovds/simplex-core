<?php


namespace Simplex\Core;


use Simplex\Core\Helpers\Declension;

class TimeDiff
{

    private $value;

    /**
     *
     * @param \DateInterval $value
     */
    public function __construct(\DateInterval $value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return (string)$this->value->days;
    }

    /**
     * @see http://php.net/manual/ru/dateinterval.format.php
     * @param string $format %d - дни, %Y - года, %H - часы и т.п.
     * @return string
     */
    public function format(string $format): string
    {
        return $this->value->format($format);
    }

    /**
     * Возвращает строковое представление
     * @param int $ss Количество значимых значений
     * @return string 2 дня 3 часа 40 минут
     */
    public function significant(int $ss = 3): string
    {
        $ret = [];
        $this->value->y && $ret[] = $this->value->y . ' ' . Declension::byCount($this->value->y, 'год', 'года', 'лет');
        $this->value->m && $ret[] = $this->value->m . ' ' . Declension::byCountMonths($this->value->m);
        $this->value->d && $ret[] = $this->value->d . ' ' . Declension::byCountDays($this->value->d);
        $this->value->h && $ret[] = $this->value->h . ' ' . Declension::byCount($this->value->h, 'час', 'часа', 'часов');
        $this->value->i && $ret[] = $this->value->i . ' ' . Declension::byCount($this->value->i, 'минута', 'минуты', 'минут');
        $this->value->s && $ret[] = $this->value->s . ' ' . Declension::byCount($this->value->s, 'секунда', 'секунды', 'секунд');
        $ret = array_slice($ret, 0, $ss);
        return implode(' ', $ret);
    }

    /**
     * @return \DateInterval
     */
    public function getRawDateInterval(): \DateInterval
    {
        return $this->value;
    }

}
