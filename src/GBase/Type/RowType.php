<?php

namespace Uroad\Utils\GBase\Type;

use Uroad\Utils\GBase\BaseBuilder;

class RowType extends Type
{
    public static $data = [];

    /**
     * row
     * 数据类型 ROW 一个或多个任意的数据类型组成
     * @param $mainField
     * @param BaseBuilder $builder
     * @example ROW("A","B")
     * @return string
     */
    public static function escapeData($mainField,BaseBuilder $builder)
    {
        $unescapedKeys = [];
        foreach (self::$data as $field=>$value) {
            if (is_string($value) || is_numeric($value)) {
                $builder->setBind("$mainField.$field", $value);
                array_push($unescapedKeys,"$mainField.$field");
                continue;
            }
            if (is_object($value) && in_array($value,Type::$type)) {
                $bind = Type::setBind($field,$value,$builder);
                array_push($unescapedKeys,$bind);
                continue;
            }
        }
        return $content = "ROW(".implode(',',$unescapedKeys).")";
    }
}