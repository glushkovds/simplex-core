<?php


namespace Simplex\Core;


class TimeDiff
{

    private $value;

    /**
     *
     * @param \DateInterval $value
     */
    public function __construct($value)
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
    public function format($format)
    {
        return $this->value->format($format);
    }

    /**
     * Возвращает строковое представление
     * @param int $ss Количество значимых значений
     * @return string 2 дня 3 часа 40 минут
     */
    public function significant($ss = 3)
    {
        if (!class_exists('PlugDeclension')) {
            throw new \Exception('TimeDiff::significant need PlugDeclension module');
        }
        $ret = [];
        $this->value->y && $ret[] = $this->value->y . ' ' . PlugDeclension::byCount($this->value->y, 'год', 'года', 'лет');
        $this->value->m && $ret[] = $this->value->m . ' ' . PlugDeclension::byCountMonths($this->value->m);
        $this->value->d && $ret[] = $this->value->d . ' ' . PlugDeclension::byCountDays($this->value->d);
        $this->value->h && $ret[] = $this->value->h . ' ' . PlugDeclension::byCount($this->value->h, 'час', 'часа', 'часов');
        $this->value->i && $ret[] = $this->value->i . ' ' . PlugDeclension::byCount($this->value->i, 'минута', 'минуты', 'минут');
        $this->value->s && $ret[] = $this->value->s . ' ' . PlugDeclension::byCount($this->value->s, 'секунда', 'секунды', 'секунд');
        $ret = array_slice($ret, 0, $ss);
        return implode(' ', $ret);
    }

}
