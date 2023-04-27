<?php


namespace Uroad\Utils\Mysql;


class MoreTableBuilder extends BaseBuilder
{
    /**
     * A reference to the database connection.
     *
     * @var BaseConnection
     */
    protected $db;
    protected $conn;
    protected $joinTablePrimary;

    protected $moreTableTransStartCheck;//多表事务开启是否校验


    public function __construct($tables, BaseConnection &$db, array $options = null)
    {
        if (empty($tables)) throw new \PDOException('A table must be specified when creating a new Query Builder.');
        $this->db = $db;
        $this->conn = $db->getConn();
        $this->setTables($tables);
        foreach ($options as $option) {
            $this->moreTableTransStartCheck = $option['moreTableTransStartCheck'];
        }
    }

    protected function setTables($tables)
    {
        if (is_string($tables)) {
            $tables = explode(',', $tables);
        }
        $this->table = $tables;
        $this->isSingle = false;
        return $this;
    }

    /**
     * @param array $tables 多张被关联表 格式 关联表=>被关联表
     * @param string $as 被关联表别名
     * @param string $cond 关联语句
     * @param string $associatedTablePrimaryKey 被关联表主键 （只支持单一主键）存在时会在合并数据时对被关联表的ID做限制
     * @param string $type 关联类型
     * @return $this
     */
    public function joins(array $tables,$as,$cond,$associatedTablePrimaryKey = '',$type = "LEFT JOIN")
    {
        $this->join[$as] = [
            'type' => $type,
            'tables' => $tables,
            'cond' => $cond
        ];
        //存储关联分表主键
        $associatedTablePrimaryKey && $this->joinTablePrimary[$as] = $associatedTablePrimaryKey;
        return $this;
    }


    public function get(): array
    {
        if (empty($this->table)) throw new \Exception('未指定表');
        if (empty($this->field)) throw new \Exception('未指定查询字段');
        if (empty($this->tableRename)) throw new \Exception('多表联合查询必须指定主表别名');
        $this->sql = $this->_get();
        return $this->conn::query($this->sql);
    }


    protected function _get(): string
    {
        $this->sql = 'SELECT '.implode(',',$this->field) . ' FROM (';
        $contentSql = [];
        foreach ($this->table as $table) {
            $sql = 'SELECT '.$this->tableRename.'.*,';
            //获取关联分表对应的主键数据
            foreach ($this->joinTablePrimary as $as => $primaryKey) {
                $sql .= $as.".".$primaryKey." AS ".$as."_primaryKey,";
            }
            $sql = rtrim($sql,',');
            $sql .= ' FROM '.$table .' AS '.$this->tableRename;
            $sql .= $this->bindJoin($table).$this->bindWhere();
            $contentSql[] = $sql;
        }
        $this->sql .= implode(' UNION ',$contentSql);
        $this->sql .= ') '.$this->tableRename;
        return $this->bindJoins().$this->bindWhere().$this->bindGroupBy().$this->bindOrderBy();
    }

    protected function bindJoin($table)
    {
        if (empty($this->join)) return '';
        foreach ($this->join as $as=>$joinData) {
            foreach ($joinData['tables'] as $mainTable=>$associatedTable) {
                if ($table != $mainTable) continue;
                $joinConn = $joinData['type']." ".$associatedTable." ".$as." ON ".$joinData['cond'];
                $this->sql .= "\n" .$joinConn;
            }
        }
        return $this;
    }

    protected function bindJoins()
    {
        if (empty($this->join)) return '';
        foreach ($this->join as $as=>$joinData) {
            //合并查询
            $sonSqlArr = [];
            foreach ($joinData['tables'] as $mainTable=>$associatedTable) {
                array_push($sonSqlArr,"SELECT * FROM ".$associatedTable);
            }
            $this->sql .= $joinData['type']." (".implode(' UNION ',$sonSqlArr).") ".$as ." ON ".$joinData['cond'];
            if (isset($this->joinTablePrimary[$as])) {//被关联表主键限制
                $this->sql .= " AND ".$this->tableRename.".".$as."_primaryKey"." = ".$as.".".$this->joinTablePrimary[$as];
            }
        }
        return $this;
    }

    public function insert($data = []): int
    {
        if (empty($data)) $data = $this->updateData;
        if (empty($this->table)) throw new \Exception('未指定表');
        if (empty($data)) throw new \Exception('未传递数据');

        $num = 0;
        if ($this->moreTableTransStartCheck && !$this->beginTransaction) throw new \Exception('未开启事务');
        foreach ($this->table as $table) {
            $this->sql = $this->_insert($table,$data);
            $num += $this->conn->exec($this->sql);
        }
        return $num;
    }



    public function update($data = []): int
    {
        if (empty($data)) $data = $this->updateData;
        if (empty($this->table)) throw new \Exception('未指定表');
        if (empty($data)) throw new \Exception('未传递数据');

        $num = 0;
        if ($this->moreTableTransStartCheck && !$this->beginTransaction) throw new \Exception('未开启事务');
        foreach ($this->table as $table) {
            $this->sql = $this->_update($table,$data);
            $num += $this->conn->exec($this->sql);
        }
        return $num;
    }

    public function delete(): int
    {
        if (empty($this->table)) throw new \Exception('未指定表');

        $num = 0;
        if ($this->moreTableTransStartCheck && !$this->beginTransaction) throw new \Exception('未开启事务');
        foreach ($this->table as $table) {
            $this->sql = $this->_delete($table);
            $num += $this->conn->exec($this->sql);
        }
        return $num;

    }


}