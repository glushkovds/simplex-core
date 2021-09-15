<?php
namespace Simplex\Core\DB\Schema;

abstract class ElementBase implements Element
{
    protected $name = '';

    public function getName(): string
    {
        return $this->name;
    }
}