<?php


use Yying\Db\Mysql\Db as MysqlDb;
use Yying\Db\GBase\Db as GBaseDb;
use Yying\Db\GBase\Type\RowType;
use Yying\Db\GBase\Type\SetType;

class Test
{
    /**
     * 实例
     * 显示查询
     * SELECT * FROM user a
     * LEFT JOIN user_role b ON a.id=b.user_id
     * LEFT JOIN role c ON c.id=b.role_id
     * LEFT JOIN department d ON a.department_id = d.id
     * WHERE a.channel = 1
     * AND b.role_type = 1
     * AND d.department_type = 1
     *隐式查询
     * SELECT * FROM (
     * SELECT a.*,b.id bid,c.id c_primaryKey,d.id did FROM user2022 a
     * LEFT JOIN user_role2022 b ON a.id=b.user_id
     * LEFT JOIN role2022 c ON c.id=b.role_id
     * LEFT JOIN department d ON a.department_id = d.id
     * WHERE a.channel = 1
     * AND b.role_type = 1
     * AND d.department_type = 1
     * UNION
     * SELECT a.*,b.id,c.id,d.id FROM user2023 a
     * LEFT JOIN user_role2023 b ON a.id=b.user_id
     * LEFT JOIN role2023 c ON c.id=b.role_id
     * LEFT JOIN department d ON a.department_id = d.id
     * WHERE a.channel = 1
     * AND b.role_type = 1
     * AND d.department_type = 1
     * ) a
     * LEFT JOIN (
     * SELECT * FROM user_role2022  UNION SELECT * FROM user_role2023
     * ) b ON a.id=b.user_id AND a.bid = b.id
     * LEFT JOIN (
     * SELECT * FROM role2022 UNION SELECT * FROM role2023
     * ) c ON c.id=b.role_id AND a.bid = b.id AND a.cid = c.id
     * LEFT JOIN department d ON a.department_id = d.id
     * WHERE a.channel = 1
     * AND b.role_type = 1
     * AND d.department_type = 1
     * GROUP BY department
     * ORDER BY created_at
     * 问题一：为啥不直接多表各查各的再合并
     * 回答：当用到GROUP BY 时，各分表数据独自聚合，达不到预期效果
     * 问题二：为啥主表查询时要关联分表
     * 回答：用作于WHERE条件过滤时将无用数据清除，关联分表也有多表逻辑避免产生脏数据
     * 问题三：问题一延伸：多表各自查询后合并，再通过子查询的方式进行后续的聚合操作
     * 回答：当查询字段用到聚合函数时数据会出错 如GROUP_CONCAT
     */
    public function MYSQL()
    {
        $db = MysqlDb::connect("127.0.0.1",3306,"test","root","root");
        $db->tables(['user2022','user2023'])->as('a')
            ->joins([
                'user2022' => 'user_role2022',
                'user2023' => 'user_role2023'
            ],'b','a.id=b.user_id','id')
            ->joins([
                'user2022' => 'role2022',
                'user2023' => 'role2023'
            ],'c','b.role_id=c.id','id')
            ->joins([
                'user2022' => 'department',
                'user2023' => 'department'
            ],'d','a.department_id=d.id')
            ->where('a.channel=? AND b.role_type=?',[1,1])
            ->where('d.department_type=?',1)
            ->groupBy('d.department_id')
            ->orderBy('a.created_at')
            ->select('a.*')
            ->get();
        $db->table('user')->insert([
            'id' => 1,
            'name' => '张三',
            'address' => "广州"
        ]);
        $db->table('user')->insert([
            ['id',1],
            ['name','张三'],
            ['address','广州'],
            ['phone',119]
        ]);
        $db->query("INSERT INTO user(id,name,address) VALUES(?,?,?)");

        $db->table('user')->insert([
            'id' => 1,
            'name' => '张三',
            'address' => '广州'
        ]);

    }

    public function GBaseSQL()
    {
        $db = GBaseDb::connect("127.0.0.1",3306,"test","root","root");
        $db->tables(['user2022','user2023'])->as('a')
            ->joins([
                'user2022' => 'user_role2022',
                'user2023' => 'user_role2023'
            ],'b','a.id=b.user_id','id')
            ->joins([
                'user2022' => 'role2022',
                'user2023' => 'role2023'
            ],'c','b.role_id=c.id','id')
            ->joins([
                'user2022' => 'department',
                'user2023' => 'department'
            ],'d','a.department_id=d.id')
            ->where('a.channel=? AND b.role_type=?',[1,1])
            ->where('? in d.department_type',1)
            ->groupBy('d.department_id')
            ->orderBy('a.created_at')
            ->select('a.*')
            ->get()->toArray();
        $db->query("INSERT INTO user(id,name,address) VALUES(?,?,ROW(?,?,?))");

        $db->beginTransaction();
        $db->table('user')->insert([
            'id' => 1,
            'name' => '张三',
            'address' => RowType::struct([
                'province' => "广东",
                'city' => SetType::struct(['广州','汕头'])
            ])
        ]);
        $db->commit();

        $db->table(function () use ($db) {
            return $db->tables(['user2022','user2023'])->select(['address']);
        })->select('province,city')->get()->all();



    }
}