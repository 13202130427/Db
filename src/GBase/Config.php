<?php

namespace Yying\Db\GBase;


class Config
{
    protected static $driver = "GBase ODBC 8.4 Driver";

    /**
     * 原理：通过ODBC的方式，使用GBase驱动来连接GBase数据库
     * 1、安装GBase8s 数据库 官网 GBase+8s+V8.8+安装手册.pdf
     * 2、安装GBase odbc 驱动 rpm –ivh gbaseodbc_8.4_1.0_x86_64.rpm
     * 3、安装unixODBC 驱动管理器
     * @param array group 参数配置
     * @return BaseConnection
     */
    public static function connect($group = null)
    {
        $conn = new \PDO("odbc:DRIVER=".self::$driver.";SERVER=".$group['hostname'].";PORT=".$group['port'].";DATABASE=".$group['database'].";",$group['username'],$group['password']);
        return (new BaseConnection())->load($conn,$group['moreTableTransStartCheck'] ?? false);
    }

}
