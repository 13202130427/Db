<?php


namespace Uroad\Utils\Mysql;


class BaseBuilder
{
    /**
     * A reference to the database connection.
     *
     * @var BaseConnection
     */
    protected $db;
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
    protected $isSingle;
    protected $beginTransaction = false;


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
                if (is_string($value)) $value = " '".$value."' ";
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
        $this->updateData = $data;
    }


    public function getInsertId()
    {
        return $this->conn::lastInsertId;
    }


    protected function _insert(string $table,array $values): string
    {
        $keys = [];
        $unescapedKeys = [];

        foreach ($values as $key=>$value) {
            array_push($keys,$key);
            array_push($unescapedKeys,$value);
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