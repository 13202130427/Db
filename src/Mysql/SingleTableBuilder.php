<?php


namespace Uroad\Utils\Mysql;


/**
 * /**
 * @method BaseBuilder static
 * Class BaseBuilder
 * @package Uroad\Utils\GBase
 */
class SingleTableBuilder extends BaseBuilder
{
    /**
     * A reference to the database connection.
     *
     * @var BaseConnection
     */
    protected $db;
    protected $conn;


    public function __construct($table, BaseConnection &$db, array $options = null)
    {
        if (empty($tableName))
        {
            throw new \PDOException('A table must be specified when creating a new Query Builder.');
        }
        $this->db = $db;
        $this->conn = $db->getConn();
        $this->setTable($table);
        foreach ($options as $option) {
            $this->moreTableTransStartCheck = $option['moreTableTransStartCheck'];
        }
    }

    protected function setTable(string $table)
    {
        if (is_string($table)) {
            $table = explode(',', $table);
        }
        $this->table = array_merge($this->table,$table);
        return $this;
    }

    protected function bindJoin()
    {
        if (empty($this->join)) return '';
        foreach ($this->join as $as=>$joinData) {
            $joinConn = $joinData['type']." ".$joinData['table']." ".$as." ON ".$joinData['cond'];
            $this->sql .= "\n" .$joinConn;
        }
        return $this;
    }

    public function join(string $associatedTable,string $as,$cond,string $type = "LEFT JOIN")
    {
        $this->join[$as] = [
            'type' => $type,
            'table' => $associatedTable,
            'cond' => $cond
        ];
        return $this;
    }

    public function get(): array
    {
        if (empty($this->table)) throw new \Exception('未指定表');
        if (empty($this->field)) throw new \Exception('未指定查询字段');
        $this->sql = $this->_get();
        return $this->conn::query($this->sql);
    }
    protected function _get(): string
    {
        $this->sql = 'SELECT '.implode(',',$this->field) . ' FROM '.$this->table;
        if ($this->tableRename) $this->sql .= ' AS '.$this->tableRename;
        return $this->bindJoin().$this->bindWhere().$this->bindGroupBy().$this->bindOrderBy();
    }

    public function insert($data = []): int
    {
        if (empty($data)) $data = $this->updateData;
        if (empty($this->table)) throw new \Exception('未指定表');
        if (empty($data)) throw new \Exception('未传递数据');

        $this->sql = $this->_insert($this->table,$data);
        return $this->conn->exec($this->sql);
    }



    public function update($data = []): int
    {
        if (empty($data)) $data = $this->updateData;
        if (empty($this->table)) throw new \Exception('未指定表');
        if (empty($data)) throw new \Exception('未传递数据');

        $this->sql = $this->_update($this->table,$data);
        return $this->conn->exec($this->sql);
    }

    public function delete(): int
    {
        if (empty($this->table)) throw new \Exception('未指定表');

        $this->sql = $this->_delete($this->table);
        return $this->conn->exec($this->sql);
    }
}