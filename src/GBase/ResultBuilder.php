<?php


namespace Yying\Db\GBase;


class ResultBuilder
{
    protected $statement;
    protected $db;
    protected $conn;
    protected $item;

    public function __construct(\PDOStatement $statement, BaseConnection &$db)
    {
        $this->statement = $statement;
        $this->db = $db;
        $this->conn = $db->getConn();
    }

    public function get()
    {
        $this->item = $this->statement->fetchAll(\PDO::FETCH_OBJ);
        return $this;
    }

    public function first()
    {
        return $this->statement->fetch(\PDO::FETCH_OBJ);
    }

    public function all()
    {
        return $this->item;
    }

    public function toArray()
    {
        if (empty($this->item)) return [];
        return get_object_vars($this->item);
    }


}