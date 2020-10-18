<?php

namespace Simplex\Core\DB\Fields;


class FieldPasswordVisible extends Field
{

    public function __construct($row)
    {
        parent::__construct($row);
        if (empty($this->help)) {
            $this->help = 'При изменении записи оставьте поле пустым, если не требуется изменять';
        }
    }

    public function show($row)
    {
        echo '<div style="text-align: center">***</div>';
    }

}
