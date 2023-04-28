<?php


namespace Yying\Db\GBase;


class ResultBuilder
{
    protected $statement;
    protected $db;
    protected $conn;

    public function __construct(\PDOStatement $statement, BaseConnection &$db)
    {
        $this->statement = $statement;
        $this->db = $db;
        $this->conn = $db->getConn();
    }

    public function get()
    {
        return $this->statement->fetchAll();
    }



}