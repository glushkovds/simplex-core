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

    protected static function getTableFrom(string $param, $from): array
    {
        return json_decode($from['params'][$param] ?? '{"v":[]}', true)['v'];
    }

    protected static function getChildrenById(int $id, int $except = 0): array
    {
        $query = ModelContent::findAdv()->where([
            'pid' => $id,
            'active' => 1
        ]);

        if ($except) {
            $query = $query->andWhere('content_id != ' . $except);
        }

        $children = $query->orderBy('title ASC')->all();
        foreach ($children as $child) {
            $child['params'] = unserialize($child['params']);
        }

        return $children;
    }

    protected static function getChildrenByPath(string $path): array
    {
        $children = ModelContent::findAdv()->where([
            'path' => $path,
            'active' => 1
        ])->orderBy('title ASC')->all();

        foreach ($children as $child) {
            $child['params'] = unserialize($child['params']);
        }

        return $children;
    }

    protected static function getChildrenConditional(string $path, array $cond): array
    {
        $parent = ModelContent::findOne(['path' => $path, 'active' => 1])['content_id'];
        $res = ModelContent::findAdv()->where([
            'active' => 1,
            'pid' => $parent
        ])->orderBy('date DESC')->all();

        $children = [];
        foreach ($res as $c) {
            $c['params'] = unserialize($c['params']);
            foreach ($cond as $k) {
                if (!($c['params'][$k] ?? false)) {
                    continue 2;
                }
            }

            $children[] = $c;
        }

        return $children;
    }

    protected function getChildren(string $path, int $max = 10, array $s = [], int $ignore = 0): array
    {
        $searchStr = [];
        foreach ($s as $v) {
            if (!trim($v)) {
                continue;
            }
            $searchStr[] = 'params LIKE \'%' . DB::escape($v) . '%\'';
        }

        if ($searchStr && $ignore) {
            $searchStr[] = 'content_id != ' . $ignore;
        }

        $parent = ModelContent::findOne(['path' => $path, 'active' => 1])['content_id'];
        $children = ModelContent::findAdv()->where([
            'active' => 1,
            'pid' => $parent
        ])->andWhere(implode(' AND ', $searchStr))->orderBy('date DESC LIMIT ' . $max)->all();

        foreach ($children as $c) {
            $c['params'] = unserialize($c['params']);
            $children[] = $c;
        }

        return $children;
    }

    protected function content()
    {
        $content = $this->get();

        $link = Container::getRequest()->getPath();
        if ($content) {
            if (!Core::ajax()) {
                $this->breadcrumbs($content);
            }

            Page::seo($content['title']);

            $children = array();
            $page = 0;
            $pages = 0;
            $hasPrev = false;
            $hasNext = false;
            if (empty($content['params']['hide_children'])) {
                $searchStr = [];
                if (isset($_GET['search'])) {
                    foreach (Container::getRequest()->get('search') as $v) {
                        if (!trim($v)) {
                            continue;
                        }
                        $searchStr[] = 'params LIKE \'%' . DB::escape($v) . '%\'';
                    }
                }

                // count all values
                $cnt = DB::result(
                    "SELECT COUNT(*) cnt FROM content WHERE active=1 AND pid=" . (int)$content['content_id'] . ($searchStr ? (' AND ' . implode(
                            ' AND ',
                            $searchStr
                        )) : ''),
                    'cnt'
                );

                // hack: fix this.
                $pageCount = 15;
                $pages = $cnt / $pageCount;
                $page = (int)Container::getRequest()->get('page');

                $hasPrev = $page > 0;
                $hasNext = $page < $pages - 1;

                $q = "SELECT content_id, title, short, text, path, photo, date, params FROM content WHERE active=1 AND pid=" . (int)$content['content_id'] . ($searchStr ? (' AND ' . implode(
                            ' AND ',
                            $searchStr
                        )) : '');
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
