<?php


namespace Simplex\Core\Helpers;


class Str
{

    /**
     * @param $string
     * @return string
     */
    public static function translite($string)
    {
        $converter = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => "'", 'ы' => 'y', 'ъ' => "'",
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => "'", 'Ы' => 'Y', 'Ъ' => "'",
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );
        $string = strtr($string, $converter);
        $string = str_replace(' ', '-', $string);
        $string = preg_replace('@\-+@', '-', $string);
        $string = preg_replace('@[^a-z0-9\-]@i', '', $string);
        $string = strtolower($string);
        return trim($string);
    }

    /**
     * @param string $str
     * @param bool $firstIsLower
     * @return string|string[]
     */
    public static function toCamel($str, $firstIsLower = true)
    {
        if ($firstIsLower) {
            $parts = explode('_', $str);
            $result = $parts[0] . implode('', array_map('ucfirst', array_slice($parts, 1)));
        } else {
            $result = str_replace('_', '', ucwords($str, '_'));
        }
        return $result;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function toUnderscore($str)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $str, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * @return string
     */
    public static function uuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

}