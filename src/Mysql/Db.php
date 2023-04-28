<?php

namespace Yying\Db\Mysql;


class Db
{
    protected static $driver = "mysql";

    /**
     * @param string $host
     * @param int $port
     * @param string $database
     * @param string $user
     * @param string $password
     * @param bool $noPersistent
     * @param bool $moreTableTransStartCheck
     * @return BaseConnection
     */
    public static function connect(string $host,int $port,string $database,string $user,string $password,bool $noPersistent = false,bool $moreTableTransStartCheck = false)
    {
        $options = [];
        if ($noPersistent) $options[\PDO::ATTR_PERSISTENT] = false;
        $conn = new \PDO(self::$driver.":host=$host;port=$port;dbname=$database",$user,$password);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        $conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        return (new BaseConnection())->load($conn,$moreTableTransStartCheck);
    }

}
