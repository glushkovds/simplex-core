<?php

namespace Simplex\Core;

/**
 * Class Service
 * @package Simplex\Core
 * @deprecated
 */
class Service
{

    private static $list = array();

    private function __construct()
    {

    }

    public static function &tree2list(&$tree, $id_start = 0, $p_only = false)
    {
        self::$list = array();
        if (isset($tree[$id_start])) {
            self::tree2listBuild($tree, $id_start, $p_only);
        }
        return self::$list;
    }

    private static function tree2listBuild(&$tree, $pid, $p_only, $level = 0)
    {
        if ($p_only) {
            foreach ($tree[$pid] as $id => $item) {
                if (isset($tree[$id])) {
                    $item['tree_level'] = $level;
                    $item['tree_nchild'] = isset($tree[$id]) ? count($tree[$id]) : 0;
                    self::$list[$id] = $item;
                    self::tree2listBuild($tree, $id, $p_only, $level + 1);
                }
            }
        } else {
            foreach ($tree[$pid] as $id => $item) {
                $item['tree_level'] = $level;
                $item['tree_nchild'] = isset($tree[$id]) ? count($tree[$id]) : 0;
                self::$list[$id] = $item;
                if (isset($tree[$id])) {
                    self::tree2listBuild($tree, $id, $p_only, $level + 1);
                }
            }
        }
    }

    public static function translite($string) {
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

}
