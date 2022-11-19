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

        if ($content) {
            if (!Core::ajax()) {
                $this->breadcrumbs($content);
            }

            Page::seo($content['title']);

            $children = array();
            if (empty($content['params']['hide_children'])) {
                $q = "SELECT content_id, title, short, text, path, photo FROM content WHERE active=1 AND pid=" . (int)$content['content_id'];
                $children = DB::assoc($q);
            }
            include static::findTemplateFile($content->template_path ?? 'default.tpl');
        } else {
            Core::error404();
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
