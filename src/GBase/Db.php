<?php

namespace Uroad\Utils\GBase;


class Db
{
    protected static $driver = "GBase ODBC 8.4 Driver";

    /**
     * 原理：通过ODBC的方式，使用GBase驱动来连接GBase数据库
     * 1、安装GBase8s 数据库 官网 GBase+8s+V8.8+安装手册.pdf
     * 2、安装GBase odbc 驱动 rpm –ivh gbaseodbc_8.4_1.0_x86_64.rpm
     * 3、安装unixODBC 驱动管理器
     * @param string $host
     * @param int $port
     * @param string $database
     * @param string $user
     * @param string $password
     * @param bool $moreTableTransStartCheck
     * @return BaseConnection
     */
    public static function connect(string $host,int $port,string $database,string $user,string $password,bool $moreTableTransStartCheck = false)
    {
        $conn = new \PDO("odbc:DRIVER=".self::$driver.";SERVER=".$host.";PORT=".$port.";DATABASE=".$database.";"."UID=$user;PWD=$password");
        return (new BaseConnection())->load($conn,$moreTableTransStartCheck);
    }

}
