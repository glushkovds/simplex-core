<?php
namespace Simplex\Core\DB\Schema\Params;

class ConstraintParams
{
    const REFERENCE_OPTION_RESTRICT = 'RESTRICT';
    const REFERENCE_OPTION_CASCADE = 'CASCADE';
    const REFERENCE_OPTION_SET_NULL = 'SET NULL';
    const REFERENCE_OPTION_NO_ACTION = 'NO ACTION';
    const REFERENCE_OPTION_SET_DEFAULT = 'SET DEFAULT';

    const REFERENCE_MATCH_FULL = 'FULL';
    const REFERENCE_MATCH_PARTIAL = 'PARTIAL';
    const REFERENCE_MATCH_SIMPLE = 'SIMPLE';

    public $isPrimary = false;
    public $isUnique = false;
    public $isUniqueIndex = false;
    public $isForeign = false;
    public $keyParts = [];
    public $referenceColumns = [];
    public $reference = '';
    public $onDelete = '';
    public $onUpdate = '';
    public $match = '';
}