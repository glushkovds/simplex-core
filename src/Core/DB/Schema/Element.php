<?php
namespace Simplex\Core\DB\Schema;

interface Element
{
    /**
     * Builds output SQL
     * @return string
     */
    public function toString(): string;

    /**
     * Returns element name
     * @return string
     */
    public function getName(): string;
}