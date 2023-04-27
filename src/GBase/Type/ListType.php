<?php

namespace Uroad\Utils\GBase\Type;


use Uroad\Utils\GBase\BaseBuilder;

class ListType extends Type
{
    public static $data = [];

    /**
     * collection
     * 数据类型 LIST 有序元素集合 元素值可重复
     * @param $mainField
     * @param BaseBuilder $builder
     * @example LIST{ROW(),ROW()}
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
        return $content = "LIST{".implode(',',$unescapedKeys)."}";
    }

}