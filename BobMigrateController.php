<?php


namespace App\Controllers;


use App\Models\User;
use App\Utils\Hash;

class BobMigrateController
{
    public function authAdmin($request, $response)
    {
        $password = $request->getParam('password');
        $password = trim($password);
        $user = User::where('is_admin', '=', 1)->first();
        if (!Hash::checkPassword($user->pass, $password)) {
            return json_encode(['code' => 0, 'msg' => '账号密码错误']);
        }

        return json_encode(['code' => 1, 'msg' => '输入正确']);
    }

    public function migration($request, $response)
    {
        $tables = $request->getParam('tables');
        $db_host = $request->getParam('db_host');
        $db_username = $request->getParam('db_username');
        $db_password = $request->getParam('db_password');
        $db_database1 = $request->getParam('db_database1');
        $db_database2 = $request->getParam('db_database2');
        if (!$tables = explode(',', $tables)) {
            return json_encode(['code' => 0, 'msg' => '需要迁移的表名不能为空']);
        }
        try {
            $mysqli = $this->mysqli_conn($db_host, $db_username, $db_password);
            $column_1 = $this->getColumns($mysqli, $db_database1, $tables);
            $column_2 = $this->getColumns($mysqli, $db_database2, $tables);
        } catch (\Exception $e) {
            return json_encode(['code' => 0, 'msg' => $e->getMessage()]);
        }
        $mysqli->autocommit(false);
        foreach ($tables as $table) {
            $col_1 = $column_1[$table];
            $col_2 = $column_2[$table];
            // 两个数组不同的值
//            $arr1 = array_merge(array_diff($col_1,$col_2),array_diff($col_2,$col_1));
            // 两个数组相同的值
            $arr2 = array_intersect($col_1, $col_2);
            $select = "";
            foreach ($arr2 as $value) {
                $select .= '`' . $value . '`,';
            }
            $select = rtrim($select, ',');
            $mysqli->query("TRUNCATE TABLE `{$db_database2}`.`$table`");
            $mysqli->query("INSERT INTO `{$db_database2}`.`$table` ($select) SELECT {$select} FROM `{$db_database1}`.`$table`");
        }
        if (!$mysqli->errno) {
            $mysqli->commit();
        } else {
            $mysqli->rollback();
            return json_encode(['code' => 0, 'msg' => $mysqli->error]);
        }
        $mysqli->close();

        return json_encode(['code' => 1, 'msg' => "导入成功"]);
    }

    protected function mysqli_conn($db_host, $db_username, $db_password)
    {
        $mysqli = mysqli_connect($db_host, $db_username, $db_password); //连接到数据库
        if ($mysqli->errno) {
            throw new \Exception($mysqli->error);
        }
        $mysqli->query("set names 'utf8'"); //编码转化
        if (!$mysqli) {
            throw new \Exception($mysqli->error);
        }

        return $mysqli;
    }

    protected function getColumns($mysqli, $db_database, $tables)
    {
        $db_selecct = $mysqli->select_db($db_database); //选择数据库
        if (!$db_selecct) {
            throw new \Exception("could not to the database: " . $db_database);
        }
        $sqlTable = "SHOW TABLES";
        $queryTable = $mysqli->query($sqlTable); //执行查询
        $tables2 = [];
        while ($arr = $queryTable->fetch_assoc()) {
            $tables2[] = array_values($arr)[0];
        }
        $column = [];
        foreach ($tables as $tab) {
            if (!in_array($tab, $tables2)) {
                throw new \Exception("数据库[ " . $db_database . ' ]里面不存在 ' . $tab . " 表，请重新输入");
            }
            $result = $mysqli->query("DESCRIBE `{$tab}`");
            $column2 = [];
            while ($arr = $result->fetch_assoc()) {
                $column2[] = $arr['Field'];
            }
            $column[$tab] = array_filter($column2);
        }

        return $column;
    }
}
