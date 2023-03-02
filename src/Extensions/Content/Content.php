<?php

namespace Simplex\Extensions\Content;

use Simplex\Core\ComponentBase;
use Simplex\Core\Container;
use Simplex\Extensions\Breadcrumbs\Breadcrumbs;
use Simplex\Core\Core;
use Simplex\Core\DB;
use Simplex\Core\Page;
use Simplex\Extensions\Content\Model\ModelContent;

/**
 * ComContent class
 *
 * Output content on site page
 *
 */
class Content extends ComponentBase
{

    public function &get(): ?ModelContent
    {
        if ($content = ModelContent::findOne(['path' => Container::getRequest()->getPath(), 'active' => 1])) {
            $content['params'] = unserialize($content['params']);
        }
        return $content;
    }

    public static function getStatic($alias)
    {
        $q = "SELECT * FROM content WHERE active=1 AND alias = '$alias'";
        if ($content = DB::result($q)) {
            $content['params'] = unserialize($content['params']);
        }
        return $content;
    }

    protected function content()
    {
        $content = $this->get();

        $loadAdditional = function (string $path) use (&$content) {
            // don't load additional content if it's already loaded
            if (isset($content[$path])) return;

            if ($nc = ModelContent::findOne(['path' => $path, 'active' => 1])) {
                $nc['params'] = unserialize($nc['params']);
            }
            $content[$path] = $nc;
        };

        $loadParent = function () use (&$content) {
            if (isset($content['p'])) return;
            if ($nc = ModelContent::findOne(['content_id' => $content['pid'], 'active' => 1])) {
                $nc['params'] = unserialize($nc['params']);
            }
            $content['p'] = $nc;
        };

        $loadTable = function (string $param, string $path = '') use ($content) {
            $params = $path ? $content[$path]['params'] : $content['params'];
            return json_decode($params[$param] ?? '{"v":[]}', true)['v'];
        };

        $loadTableChld = function (string $param, $prm) {
            return json_decode($prm['params'][$param] ?? '{"v":[]}', true)['v'];
        };

        $loadChildrenAll = function ($id, $but=0) {
            $q = "SELECT content_id, title, short, text, path, photo, date, params FROM content WHERE active=1 AND pid=" . (int)$id;
            $q .= " ORDER BY title ASC";
            $q = DB::query($q);
            $children = [];
            while ($c = DB::fetch($q)) {
                if ($c['content_id'] == $but) continue;
                $child = $c;
                $child['params'] = unserialize($c['params']);
                $children[] = $child;
            }
            return $children;
        };

        $loadChildrenCond = function (string $path, array $cond) {
            $content = ModelContent::findOne(['path' => $path, 'active' => 1]);
            $q = "SELECT content_id, title, short, text, path, photo, date, params FROM content WHERE active=1 AND pid=" . (int)$content['content_id'];
            $q .= " ORDER BY date DESC";
            $q = DB::query($q);
            $children = [];
            while ($c = DB::fetch($q)) {
                $child = $c;
                $child['params'] = unserialize($c['params']);
                foreach ($cond as $k) {
                    if (!($child['params'][$k] ?? false)) continue 2;
                }
                $children[] = $child;
            }
            return $children;
        };

        $loadChildrenAsc = function (string $path) {
            $content = ModelContent::findOne(['path' => $path, 'active' => 1]);
            $q = "SELECT content_id, title, short, text, path, photo, date, params FROM content WHERE active=1 AND pid=" . (int)$content['content_id'];
            $q .= " ORDER BY title ASC";
            $q = DB::query($q);
            $children = [];
            while ($c = DB::fetch($q)) {
                $child = $c;
                $child['params'] = unserialize($c['params']);
                $children[] = $child;
            }
            return $children;
        };

        $loadChildren = function (string $path, int $max = 10, array $s = [], int $ignore = 0) {
            $searchStr = [];
            foreach ($s as $v) {
                if (!trim($v)) continue;
                $searchStr[] = 'params LIKE \'%' . DB::escape($v) . '%\'';
            }

            if ($searchStr && $ignore) {
                $searchStr[] = 'content_id != ' . $ignore;
            }

            $content = ModelContent::findOne(['path' => $path, 'active' => 1]);
            $q = "SELECT content_id, title, short, text, path, photo, date, params FROM content WHERE active=1 AND pid=" . (int)$content['content_id'].($searchStr?(' AND '.implode(' AND ',$searchStr)):'');
            $q .= " ORDER BY date DESC LIMIT " . (int)$max;
            $q = DB::query($q);
            $children = [];
            while ($c = DB::fetch($q)) {
                $child = $c;
                $child['params'] = unserialize($c['params']);
                $children[] = $child;
            }
            return $children;
        };

        $link = Container::getRequest()->getPath();
        if ($content) {
            if (!Core::ajax()) {
                $this->breadcrumbs($content);
            }

            Page::seo($content['title']);

            $children = array(); $page = 0; $pages = 0; $hasPrev = false; $hasNext = false;
            if (empty($content['params']['hide_children'])) {
                $searchStr = [];
                if (isset($_GET['search'])) {
                    foreach (Container::getRequest()->get('search') as $v) {
                        if (!trim($v)) continue;
                        $searchStr[] = 'params LIKE \'%' . DB::escape($v) . '%\'';
                    }
                }

                // count all values
                $cnt = DB::result("SELECT COUNT(*) cnt FROM content WHERE active=1 AND pid=" . (int)$content['content_id'].($searchStr?(' AND '.implode(' AND ',$searchStr)):''), 'cnt');

                // hack: fix this.
                $pageCount = 15;
                $pages = $cnt / $pageCount;
                $page = (int)Container::getRequest()->get('page');

                $hasPrev = $page > 0;
                $hasNext = $page < $pages - 1;

                $q = "SELECT content_id, title, short, text, path, photo, date, params FROM content WHERE active=1 AND pid=" . (int)$content['content_id'].($searchStr?(' AND '.implode(' AND ',$searchStr)):'');
                if (isset($_GET['mob'])) {
                    $q .= " ORDER BY date DESC LIMIT " . (($page + 1) * $pageCount);
                } else {
                    $q .= " ORDER BY date DESC LIMIT " . ($page * $pageCount) . ", " . $pageCount;
                }
                $q = DB::query($q);
                $children = [];
                while ($c = DB::fetch($q)) {
                    $child = $c;
                    $child['params'] = unserialize($c['params']);
                    $children[] = $child;
                }
            }
            include static::findTemplateFile($content->template_path ?? 'default.tpl');
        } else {
            if ($link == '/404') {
                http_response_code(404);
            } else {
                header('Location: /404');
                die;
            }
            include self::findTemplateFile('base/404.tpl');
        }
    }

    protected function breadcrumbs($content)
    {
        Breadcrumbs::add($content['title'], $content['path']);
        $id = (int)$content['pid'];
        while ($id) {
            $q = "SELECT pid, title, path FROM content WHERE content_id=$id";
            $id = 0;
            if ($content = DB::result($q)) {
                Breadcrumbs::add($content['title'], $content['path']);
                $id = (int)$content['pid'];
            }
        }
    }

    protected static function findTemplateFile(string $name): string
    {
        if (is_file($path = SF_ROOT_PATH . '/Extensions/Content/tpl/' . $name)) {
            return $path;
        }
        if (is_file($path = __DIR__ . '/tpl/' . $name)) {
            return $path;
        }
        return __DIR__ . '/tpl/default.tpl';
    }

}
