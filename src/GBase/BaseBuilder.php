<?php


namespace Yying\Db\GBase;


use Yying\Db\GBase\Type\Type;

class BaseBuilder
{
    /**
     * A reference to the database connection.
     *
     * @var BaseConnection
     */
    protected $db;
    /**
     * @var \PDO
     */
    protected $conn;

    protected $sql;
    protected $table;
    protected $tableRename;
    protected $field;
    protected $join;
    protected $where;
    protected $groupBy;
    protected $orderBy;
    protected $updateData;
    protected $binds;
    protected $bindsKeyCount;
    protected $isSingle;
    protected $beginTransaction = false;

    protected function exec()
    {
        //如果开启了事务 且事务状态为FALSE 不给执行
        if ($this->beginTransaction && $this->db->getTransStatus() === false) return false;
        $statement = $this->conn->prepare($this->sql);
        try {
            return $statement->execute($this->binds);
        } catch (\PDOException $exception) {
            if ($this->beginTransaction && $this->db->getTransStatus() === true) $this->db->setTransStatus(false);
            throw new \PDOException($exception);
        }
    }


    public function select($field)
    {
        if (is_string($field)) {
            $field = explode(',', $field);
        }
        $this->field = $field;
        return $this;
    }

    public function where($sql,$param,$type = 'AND')
    {
        if (is_string($param)) {
            $param = explode(',', $param);
        }
        if (!in_array($type,['AND','OR'])) {
            throw new \Exception('不符合的关联关系');
        }
        if (mb_substr_count($sql,'?') !== count($param)) {
            throw new \Exception('参数有误!');
        }
        foreach ($param as $value) {
            $index = stripos($sql,'?');
            if ($index !== false) {
                $bind = $this->setBind("restrictions", $value);
                if (is_string($value)) $value = " :$bind ";
                $sql = substr_replace($sql,$value,$index,1);
            }
        }
        $this->where[] = $type .' '.$sql;
        return $this;
    }


    public function groupBy($cond)
    {
        if (is_string($cond)) {
            $cond = explode(',', $cond);
        }
        foreach ($cond as $value) {
            $this->groupBy[] = $value;
        }
        return $this;
    }

    public function orderBy($cond)
    {
        if (is_string($cond)) {
            $cond = explode(',', $cond);
        }
        foreach ($cond as $value) {
            $this->orderBy[] = $value;
        }
        return $this;
    }

    public function as($name)
    {
        $this->tableRename = $name;
        return $this;
    }


    public function setData(array $data)
    {
        $this->escapeData($data);
    }

    /**
     * 对 更新数据进行转义 方法识别
     * @param $data
     */
    protected function escapeData($data)
    {
        foreach ($data as $field=>$value) {
            if (is_string($value) || is_numeric($value)) {
                $bind = $this->setBind($field, $value);
                $this->updateData[$field] = ":$bind";
                continue;
            }
            if (is_object($value) && in_array($value,Type::$type)) {
                $bind = Type::setBind($field,$value,$this);
                $this->updateData[$field] = $bind;
                continue;
            }
            throw new \Exception('错误的数据类型！');
        }
    }


    /**
     * Stores a bind value after ensuring that it's unique.
     * While it might be nicer to have named keys for our binds array
     * with PHP 7+ we get a huge memory/performance gain with indexed
     * arrays instead, so lets take advantage of that here.
     *
     * @param string  $key
     * @param mixed   $value
     *
     * @return string
     */
    public function setBind(string $key, $value = null): string
    {
        if (! array_key_exists($key, $this->binds))
        {
            $this->binds[$key] = $value;
            return $key;
        }

        if (! array_key_exists($key, $this->bindsKeyCount))
        {
            $this->bindsKeyCount[$key] = 0;
        }
        $count = $this->bindsKeyCount[$key]++;

        $this->binds[':'.$key . $count] = $value;

        return $key . $count;
    }

    protected function _insert(string $table,array $values): string
    {
        $keys = [];
        $unescapedKeys = [];
        foreach ($values as $key=>$value) {
            array_push($keys,$key);
            if (is_string($value)) {
                array_push($unescapedKeys,$value);
                continue;
            }
            //特殊类型

        }
        return 'INSERT INTO '.$table.' (' . implode(', ',$keys).') VALUES ('.implode(', ',$unescapedKeys).')';
    }

    protected function _update(string $table,array $values): string
    {
        $valStr = [];

        foreach ($values as $key=>$value)
        {
            $valStr[] = $key.' = '.$value;
        }

        return 'UPDATE '.$table.' SET '.implode(', ',$valStr).$this->bindWhere();
    }

    protected function _delete(string $table)
    {
        return 'DELETE FROM '.$table.$this->bindWhere();
    }

    protected function _merge(string $mainTable,string $associatedTable,string $mainTableField,string $associatedTableField,$type,$finalHandle)
    {
        return "MERGE INTO $mainTable USING $associatedTable ON $mainTable.$mainTableField $type $associatedTable.$associatedTableField".$this->bindWhere()." THEN $finalHandle";
    }




    protected function bindWhere()
    {
        if (empty($this->where)) return '';
        return ' WHERE '. "\n" . implode("\n", $this->where);
    }

    protected function bindGroupBy()
    {
        if (empty($this->groupBy)) return '';
        return ' GROUP BY ' .implode(',',$this->groupBy);
    }

    protected function bindOrderBy()
    {
        if (empty($this->orderBy)) return '';
        return ' ORDER BY ' . implode(',', $this->orderBy);
    }

}