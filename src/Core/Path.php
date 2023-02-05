<?php

namespace Simplex\Core;

class Path
{
    /**
     * @param string $dir for example /var/www/html/Layout/Cards/Product1/script.js
     * @return string for example /Layout/Cards/Product1/script.js
     */
    public static function dirToHref(string $dir): string
    {
        return str_replace(['\\', Container::getRequest()->getServerInfo()['DOCUMENT_ROOT']], ['/', ''], $dir);
    }
}