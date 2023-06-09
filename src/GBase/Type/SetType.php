<?php

namespace Yying\Db\GBase\Type;


use Yying\Db\GBase\BaseBuilder;

class SetType extends Type
{
    public static $data = [];

    /**
     * collection
     * 数据类型 SET 无序元素集合 元素值唯一
     * @param $mainField
     * @param BaseBuilder $builder
     * @example SET{"A","B"}
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
        return $content = "SET{".implode(',',$unescapedKeys)."}";
    }

}