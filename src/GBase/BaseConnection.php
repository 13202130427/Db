<?php


namespace Yying\Db\GBase;



class BaseConnection
{
    protected static $moreTableTransStartCheck = false;//多表事务开启是否校验
    /**
     * @var \PDO
     */
    protected $conn = null;
    /**
     * @var \PDOStatement
     */
    protected $statement;
    protected $binds;
    protected $beginTransaction = false;
    /**
     * @var boolean
     */
    protected $transStatus = true;




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
        $this->binds = [];
        if (!$this->conn) throw new \PDOException('未建立database链接');
        if (empty($table)) throw new \PDOException('表名不能为空');
        if (is_object($table) && ($table instanceof SingleTableBuilder || $table instanceof MoreTableBuilder)) {
            //传入一个闭包 获取这个闭包的SQL
            $sonTable = $table->getObj();
            $table = "(".$sonTable['sql'].")";
            $this->binds = $sonTable['binds'];
        }

        return new SingleTableBuilder($table, $this,[
            'binds' => $this->binds
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
        if (!$this->conn) throw new \PDOException('未建立database链接');
        if (empty($tables)) throw new \PDOException('表名不能为空');
        return new MoreTableBuilder($tables, $this,[
            'moreTableTransStartCheck' => self::$moreTableTransStartCheck
        ]);
    }

    public function query($sql,$param = [])
    {
        $this->statement = $this->conn->prepare($sql);
        $this->statement->execute($param);
        return new ResultBuilder($this->statement,$this);
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

    public function beginTransaction()
    {
        if ($this->beginTransaction) {
            return false;
        }
        if ($this->conn->inTransaction()) {
            return false;
        }
        $this->beginTransaction = true;
        $this->conn->beginTransaction();
    }

    public function setTransStatus(bool $bool)
    {
        $this->transStatus = $bool;
    }

    public function getTransStatus()
    {
        return  $this->transStatus;
    }

    public function rollBack()
    {
        $this->conn->rollBack();
    }

    public function commit()
    {
        if (!$this->beginTransaction) {
            return false;
        }
        if (!$this->conn->inTransaction()) {
            return false;
        }
        if ($this->transStatus === false) {
            $this->rollBack();
            return false;
        }
        try {
            $this->conn->commit();
            return true;
        }catch (\PDOException $exception) {
            $this->rollBack();
            return false;
        }
    }



}