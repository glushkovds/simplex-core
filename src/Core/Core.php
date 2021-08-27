<?php

namespace Simplex\Core;

use Simplex\Core\Html\HtmlRequest;
use Simplex\Core\Html\HtmlResponse;

class Core
{
    private static $ajax = false;
    private static $uri = [];
    private static $path = '';
    private static $site_params = false;
    private static $menu_tree = [];
    private static $menu_by_id = [];
    private static $menu_by_link = [];
    private static $menu_by_ext = [];
    private static $menu_cur = false;
    private static $component_level = 0;
    private static $component_menu_id = 0;
    private static $content_only = false;

    private function __construct()
    {

    }

    public static function init()
    {
        // if request or response wasn't set, we still have to construct them
        // useful for backwards compatibility
        if (!Container::isSet('request')) {
            Container::set('request', new HtmlRequest());
        }

        if (!Container::isSet('response')) {
            Container::set('response', new HtmlResponse());
        }

        $request = Container::getRequest();

        // TODO: get rid of deprecated code
        DB::bind(['SITE_PATH' => $request->getPath(), 'SITE_LINK' => $request->getPath()]);

        if ($request->isAjax()) {
            self::$ajax = true;
            self::$content_only = true;
            $sf_module_id = isset($_REQUEST['sf_module_id']) ? (int)$_REQUEST['sf_module_id'] : 0;
            if ($sf_module_id > 0) {
                Page::moduleById($sf_module_id);
                exit();
            }
        } else {
            $q = "SELECT title, description, keywords, metatags FROM seo WHERE link=@SITE_LINK";
            if ($row = DB::result($q)) {
                Page::seo($row['title'], $row['description'], $row['keywords'], true, $row['metatags']);
            }
        }

        $q = "SELECT alias, value FROM settings";
        $rows = DB::assoc($q);
        foreach ($rows as $row) {
            self::$site_params[$row['alias']] = $row['value'];
        }

        $q = "SELECT t1.*, t2.class
        FROM menu t1
        LEFT JOIN component t2 ON t2.component_id=t1.component_id
        WHERE t1.active=1
        ORDER BY t1.npp";

        $rows = DB::assoc($q);
        foreach ($rows as $row) {
            self::$menu_tree[(int)$row['menu_pid']][(int)$row['menu_id']] = $row;
            self::$menu_by_id[(int)$row['menu_id']] = $row;
            self::$menu_by_link[md5($row['link'])] = $row;
            self::$menu_by_ext[strtolower(substr($row['class'], 3))][] = $row;
            if ($row['link'] == self::$path && !self::$menu_cur) {
                self::$menu_cur = $row;
            }
        }
    }

    /**
     * @deprecated use Request::isAjax() instead
     * @return bool
     */
    public static function ajax()
    {
        return Container::getRequest()->isAjax();
    }

    /**
     * @deprecated use Request::getUrlParts() instead
     * @param false $i
     * @return array|mixed|string
     */
    public static function uri($i = false)
    {
        $uri = Container::getRequest()->getUrlParts();

        if ($i === false) {
            return $uri;
        }

        $i += self::$component_level;
        return $uri[$i] ?? '';
    }

    /**
     * @deprecated use Request::getUrlParts() instead
     * @param $i
     * @return mixed|string
     */
    public static function uri_r($i)
    {
        $uri = Container::getRequest()->getUrlParts();

        $i = count($uri) - 2 - $i;;
        return $uri[$i] ?? '';
    }

    /**
     * @deprecated use Request::getPath() instead
     * @param int $beg
     * @param int $len
     * @return array|string|string[]
     */
    public static function path($beg = 0, $len = 0)
    {
        $path = Container::getRequest()->getPath();
        $uri = self::uri();

        $beg = $beg < 0 ? count($uri) + $beg : $beg;
        if ($beg + $len > 0) {
            $path = $len ? join('/', array_slice($uri, $beg, $len)) : join('/', array_slice($uri, $beg));
            $path = str_replace('//', '/', '/' . $path . '/');
        }
        return $path ? $path : '/';
    }

    public static function siteParam($key = false, $defultValue = null)
    {

        if (!self::$site_params) {
            $q = "SELECT alias, value FROM settings";
            $rows = DB::assoc($q);
            foreach ($rows as $row) {
                self::$site_params[$row['alias']] = $row['value'];
            }
        }

        if ($key === false) {
            return self::$site_params;
        }
        if (!isset(self::$site_params[$key])) {
            self::$site_params[$key] = $defultValue;
            $q = "INSERT INTO settings(name, alias, value) VALUES('New parameter')";
        }
        return self::$site_params[$key] ?? $defultValue;
    }

    public static function getComponent()
    {
        $class = $classDefault = Container::getConfig()::$component_default;
        $path = '/';
        $i = 0;
        if (!empty(self::$menu_by_link[md5($path)]['class'])) {
            $class = self::$menu_by_link[md5($path)]['class'];
            self::$component_level = $i;
            self::$component_menu_id = self::$menu_by_link[md5($path)]['menu_id'];
        }
        $i++;
        foreach (self::uri() as $u) {
            if ($u) {
                $path .= $u . '/';
                if (!empty(self::$menu_by_link[md5($path)])) {
                    $class = self::$menu_by_link[md5($path)]['class'] ?: $classDefault;
                    self::$component_level = $i;
                    self::$component_menu_id = self::$menu_by_link[md5($path)]['menu_id'];
                }
            }
            $i++;
        }
        return new $class();
    }

    public static function getComponentAPI()
    {

        $class = '';
        $path = '/';
        $i = 0;
        if (!empty(self::$menu_by_link[md5($path)]['class'])) {
            $class = self::$menu_by_link[md5($path)]['class'];
            self::$component_level = $i;
            self::$component_menu_id = self::$menu_by_link[md5($path)]['menu_id'];
        }
        $i++;
        foreach (self::uri() as $u) {
            if ($u) {
                $path .= $u . '/';
                if (!empty(self::$menu_by_link[md5($path)]['class'])) {
                    $class = self::$menu_by_link[md5($path)]['class'];
                    self::$component_level = $i;
                    self::$component_menu_id = self::$menu_by_link[md5($path)]['menu_id'];
                }
            }
            $i++;
        }

        if ($class) {
            $class = 'Api' . substr($class, 3);
            return new $class();
        } else {
            return false;
        }
    }

    /**
     * @return string[]
     */
    public static function getExtensions()
    {
        $exts = array_slice(scandir(SF_ROOT_PATH . '/Extensions'), 2);
        return $exts;
    }

    public static function execute()
    {
        if (self::$content_only) {
            Page::content();
        } else {
            $config = Container::getConfig();
            if (isset($_REQUEST['print']) && is_file('theme/' . $config::$theme . '/print.tpl')) {
                include 'theme/' . $config::$theme . '/print.tpl';
                return;
            }
            if (is_file('theme/' . $config::$theme . '/index.tpl')) {
                include 'theme/' . $config::$theme . '/index.tpl';
            }
        }
    }

    public static function menu($type = 'tree')
    {
        switch ($type) {
            case 'by_id' :
                return self::$menu_by_id;
            case 'by_link' :
                return self::$menu_by_link;
            case 'by_ext' :
                return self::$menu_by_ext;
        }
        return self::$menu_tree;
    }

    public static function menuCurItem()
    {
        return self::$menu_cur;
    }

    public static function componentPath()
    {
        return self::path(self::$component_level);
    }

    public static function componentLevel()
    {
        return self::$component_level;
    }

    public static function componentMenuID()
    {
        return self::$component_menu_id;
    }

    public static function componentTitle()
    {
        return self::$menu_by_id[self::$component_menu_id]['name'];
    }

    public static function error404()
    {
        $config = Container::getConfig();
        header("HTTP/1.0 404 Not Found");
        Page::seo('Error 404');
        if (is_file('theme/' . $config::$theme . '/404.tpl')) {
            self::$content_only = true;
            include 'theme/' . $config::$theme . '/404.tpl';
        } else {
            echo self::siteParam('error404');
        }
    }

    /**
     *
     * @return bool
     */
    public static function isHttps()
    {
        return !empty($_SERVER['HTTPS']);
    }

    /**
     *
     * @param bool $withSlashDots [optional = false] ://
     * @return string https / http
     */
    public static function httpProtocol($withSlashDots = false)
    {
        return (self::isHttps() ? 'https' : 'http') . ($withSlashDots ? '://' : '');
    }

    /**
     * @return string like /vendor/glushkovds/simplex-core/src
     */
    public static function webVendorPath()
    {
        return str_replace(SF_ROOT_PATH, '', static::vendorPath());
    }

    public static function vendorPath()
    {
        return realpath(__DIR__ . '/..');
    }

}
