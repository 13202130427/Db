<?php

namespace Yying\Db\GBase\Type;


use Uroad\Utils\GBase\BaseBuilder;

class Type
{
    public static $data = [];
    const ROW = RowType::class;
    const LIST = ListType::class;
    const SET = SetType::class;
    const MULTISET = MultisetType::class;

    public static $type = [self::ROW,self::LIST,self::SET,self::MULTISET];

    public static function struct($data)
    {
        self::$data = $data;
        return self::class;
    }

    public static function setBind($mainField,$type,BaseBuilder $builder)
    {
        return $type::escapeData($mainField,$builder);
    }


}