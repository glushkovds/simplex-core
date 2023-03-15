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
        $AQ->leftJoin(ModelContentTemplate::class, 'template_id');
    }

    public static function aqModifiersDefault(): array
    {
        return ['withTemplate'];
    }

    public function loadFrom(string $path)
    {
        if (isset($this[$path])) {
            return;
        }

        if ($nc = self::findOne(['path' => $path, 'active' => 1])) {
            $nc['params'] = unserialize($nc['params']);
        }
        $this[$path] = $nc;
    }

    public function loadParent()
    {
        if (isset($this['p'])) {
            return;
        }

        if ($nc = ModelContent::findOne(['content_id' => $this['pid'], 'active' => 1])) {
            $nc['params'] = unserialize($nc['params']);
        }

        $this['p'] = $nc;
    }

    public function getTable(string $param, string $path = ''): array
    {
        $params = $path ? $this[$path]['params'] : $this['params'];
        return json_decode($params[$param] ?? '{"v":[]}', true)['v'];
    }
}