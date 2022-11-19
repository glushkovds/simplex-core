<?php

namespace Simplex\Extensions\Content;

use Simplex\Core\DB;
use Simplex\Core\ModuleBase;

/**
 * ContentModule class
 *
 * Output last contents
 */
class ModuleContent extends ModuleBase {

    protected function content() {
        $content_id = empty($this->params['content_id']) ? 0 : (int) $this->params['content_id'];
        $cnt_limit = empty($this->params['cnt_limit']) ? 0 : abs($this->params['cnt_limit']);

        $q = "SELECT content_id, date, title, path, short, text, photo
        FROM content
        WHERE active=1
          " . ($content_id ? "AND pid=" . $content_id : "") . "
          ORDER BY date DESC, content_id" . ($cnt_limit ? ' LIMIT ' . $cnt_limit : '');
        $rows = DB::assoc($q);

        if (count($rows)) {
            if (!empty($this->params['tpl']) && is_file(__DIR__ . '/tpl/' . $this->params['tpl'] . '.tpl')) {
                include __DIR__ . '/tpl/' . $this->params['tpl'] . '.tpl';
            } else {
                include __DIR__ . '/tpl/mod_list.tpl';
            }
        }
    }

}
