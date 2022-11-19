<?php

namespace Simplex\Extensions\Content\Admin;

use Simplex\Admin\Base;
use Simplex\Core\DB;

class AdminContent extends Base
{
    protected function tableParamsLoad()
    {
        $contentId = (int)($_REQUEST[$this->pk->name] ?? 0);
        $q = "
            SELECT param_id, param_pid, pos, t1.name, t1.label, t1.params, t2.class, '$this->table' `table`
            FROM struct_param t1
            LEFT JOIN struct_field t2 USING(field_id)
            WHERE table_id = $this->tableId
            UNION ALL
            SELECT ctp_id + 1000000 as param_id, param_pid, position as pos, 
                   t1.name, t1.label, t1.params, t2.class, '$this->table' `table`
            FROM content_template_param t1
            JOIN content c USING(template_id)
            LEFT JOIN struct_field t2 USING(field_id)
            WHERE c.content_id = $contentId
        ";
        $params = DB::assoc($q, 'param_pid', 'param_id');
        return $params;
    }
}