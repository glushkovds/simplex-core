<?php

namespace Simplex\Extensions\Content\Model;

use Simplex\Core\DB\AQ;
use Simplex\Core\ModelBase;

/**
 * @property null|string $template_path
 */
class ModelContent extends ModelBase
{
    protected static $table = 'content';
    protected static $primaryKeyName = 'content_id';

    public static function aqModifyWithTemplate(AQ $AQ)
    {
        $AQ->join(ModelContentTemplate::class, 'template_id')
            ->select(['content.*', 'path' => 'template_path']);
    }

    public static function aqModifiersDefault(): array
    {
        return ['withTemplate'];
    }
}