<?php


namespace Uroad\Utils\Mysql;



class BaseConnection
{
    public static $options;
    protected static $moreTableTransStartCheck = false;//多表事务开启是否校验
    protected $conn;



    /**
     * Returns an instance of the query builder for this connection.
     *
     * @param string $table
     *
     * @return SingleTableBuilder
     * @throws \PDOException
     */
    public function table($table)
    {
        if (empty($table)) throw new \PDOException('表名不能为空');
        $className = str_replace('BaseConnection', 'SingleTableBuilder', get_class($this));
        return new $className($table, $this,[
            'moreTableTransStartCheck' => self::$moreTableTransStartCheck
        ]);
    }


    /**
     * Returns an instance of the query builder for this connection.
     *
     * @param string|array $tables
     *
     * @return MoreTableBuilder
     * @throws \PDOException
     */
    public function tables($tables)
    {
        if (empty($tables)) throw new \PDOException('表名不能为空');
        $className = str_replace('BaseConnection', 'MoreTableBuilder', get_class($this));
        return new $className($tables, $this);
    }


    public function load($conn,$moreTableTransStartCheck)
    {
        self::$moreTableTransStartCheck = $moreTableTransStartCheck;
        $this->conn =$conn;
        return $this;
    }

    public function getConn()
    {
        return $this->conn;
    }



}