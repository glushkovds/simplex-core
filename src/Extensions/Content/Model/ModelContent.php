<?php

namespace Simplex\Extensions\Content\Model;

use Simplex\Core\Buffer;
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

    public function loadFrom(string $path): ?ModelContent
    {
        return Buffer::getOrSet('content.' . $path, function () use ($path) {
            if ($nc = self::findOne(['path' => $path, 'active' => 1])) {
                $nc['params'] = unserialize($nc['params']);
            }

            return $nc ?? null;
        });
    }

    public function loadParent()
    {
        $pid = $this['pid'];
        return Buffer::getOrSet('content.parent.' . $pid, function () use ($pid) {
            if ($nc = self::findOne(['content_id' => $this['pid'], 'active' => 1])) {
                $nc['params'] = unserialize($nc['params']);
            }

            return $nc ?? null;
        });
    }

    public function getTable(string $param, string $path = ''): array
    {
        $params = $path ? $this->loadFrom($path)['params'] : $this['params'];
        return json_decode($params[$param] ?? '{"v":[]}', true)['v'];
    }
}