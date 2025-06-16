<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: inc/sqls.php
// 文件大小: 9120 字节
// 最后修改时间: 2025-05-20 22:40:22
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 数据库增删改查类
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

class Database {
    private $conn;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->conn = db_connect();
    }
    
    /**
     * 执行查询
     * @param string $sql SQL语句
     * @return mysqli_result|bool 查询结果
     */
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    /**
     * 获取单条记录
     * @param string $table 表名
     * @param array $where 条件数组
     * @param string $fields 字段列表
     * @return array|null 记录数组
     */
    public function get_one($table, $where = [], $fields = '*') {
        $sql = "SELECT {$fields} FROM `{$table}`";
        
        if (!empty($where)) {
            $where_sql = [];
            foreach ($where as $key => $value) {
                $where_sql[] = "`{$key}` = '" . $this->conn->real_escape_string($value) . "'";
            }
            $sql .= " WHERE " . implode(' AND ', $where_sql);
        }
        
        $sql .= " LIMIT 1";
        
        $result = $this->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * 获取多条记录
     * @param string $table 表名
     * @param array $where 条件数组
     * @param string $fields 字段列表
     * @param string $order 排序方式
     * @param int $limit 记录数限制
     * @param int $offset 偏移量
     * @return array 记录数组
     */
    public function get_all($table, $where = [], $fields = '*', $order = '', $limit = 0, $offset = 0) {
        $sql = "SELECT {$fields} FROM `{$table}`";
        
        if (!empty($where)) {
            $where_sql = [];
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] === 'LIKE') {
                        $where_sql[] = "`{$key}` LIKE '%" . $this->conn->real_escape_string($value[1]) . "%'";
                    } elseif ($value[0] === 'IN') {
                        $in_values = array_map(function($v) {
                            return "'" . $this->conn->real_escape_string($v) . "'";
                        }, $value[1]);
                        $where_sql[] = "`{$key}` IN (" . implode(',', $in_values) . ")";
                    } elseif ($value[0] === 'BETWEEN') {
                        $where_sql[] = "`{$key}` BETWEEN '" . $this->conn->real_escape_string($value[1]) . 
                                       "' AND '" . $this->conn->real_escape_string($value[2]) . "'";
                    } else {
                        $where_sql[] = "`{$key}` {$value[0]} '" . $this->conn->real_escape_string($value[1]) . "'";
                    }
                } else {
                    $where_sql[] = "`{$key}` = '" . $this->conn->real_escape_string($value) . "'";
                }
            }
            $sql .= " WHERE " . implode(' AND ', $where_sql);
        }
        
        if (!empty($order)) {
            $sql .= " ORDER BY {$order}";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $result = $this->query($sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * 插入记录
     * @param string $table 表名
     * @param array $data 数据数组
     * @return int|bool 插入ID或失败标志
     */
    public function insert($table, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "`{$key}`";
            $values[] = "'" . $this->conn->real_escape_string($value) . "'";
        }
        
        $sql = "INSERT INTO `{$table}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        
        if ($this->query($sql)) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * 更新记录
     * @param string $table 表名
     * @param array $data 数据数组
     * @param array $where 条件数组
     * @return bool 是否成功
     */
    public function update($table, $data, $where) {
        $set_sql = [];
        
        foreach ($data as $key => $value) {
            $set_sql[] = "`{$key}` = '" . $this->conn->real_escape_string($value) . "'";
        }
        
        $where_sql = [];
        
        foreach ($where as $key => $value) {
            $where_sql[] = "`{$key}` = '" . $this->conn->real_escape_string($value) . "'";
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $set_sql) . " WHERE " . implode(' AND ', $where_sql);
        
        return $this->query($sql);
    }
    
    /**
     * 删除记录
     * @param string $table 表名
     * @param array $where 条件数组
     * @return bool 是否成功
     */
    public function delete($table, $where) {
        $where_sql = [];
        
        foreach ($where as $key => $value) {
            $where_sql[] = "`{$key}` = '" . $this->conn->real_escape_string($value) . "'";
        }
        
        $sql = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $where_sql);
        
        return $this->query($sql);
    }
    
    /**
     * 批量删除记录
     * @param string $table 表名
     * @param string $field 字段名
     * @param array $values 值数组
     * @return bool 是否成功
     */
    public function batch_delete($table, $field, $values) {
        $values_sql = array_map(function($v) {
            return "'" . $this->conn->real_escape_string($v) . "'";
        }, $values);
        
        $sql = "DELETE FROM `{$table}` WHERE `{$field}` IN (" . implode(',', $values_sql) . ")";
        
        return $this->query($sql);
    }
    
    /**
     * 获取记录总数
     * @param string $table 表名
     * @param array $where 条件数组
     * @return int 记录总数
     */
    public function count($table, $where = []) {
        $sql = "SELECT COUNT(*) as total FROM `{$table}`";
        
        if (!empty($where)) {
            $where_sql = [];
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] === 'LIKE') {
                        $where_sql[] = "`{$key}` LIKE '%" . $this->conn->real_escape_string($value[1]) . "%'";
                    } else {
                        $where_sql[] = "`{$key}` {$value[0]} '" . $this->conn->real_escape_string($value[1]) . "'";
                    }
                } else {
                    $where_sql[] = "`{$key}` = '" . $this->conn->real_escape_string($value) . "'";
                }
            }
            $sql .= " WHERE " . implode(' AND ', $where_sql);
        }
        
        $result = $this->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        
        return 0;
    }
    
    /**
     * 执行自定义SQL查询并返回一行结果
     * @param string $sql SQL语句
     * @return array|null 查询结果
     */
    public function get_row($sql) {
        $result = $this->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * 执行自定义SQL查询并返回所有结果
     * @param string $sql SQL语句
     * @return array 查询结果
     */
    public function get_rows($sql) {
        $result = $this->query($sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * 获取错误信息
     * @return string 错误信息
     */
    public function error() {
        return $this->conn->error;
    }
    
    /**
     * 获取最后插入ID
     * @return int 最后插入ID
     */
    public function last_id() {
        return $this->conn->insert_id;
    }
    
    /**
     * 开始事务
     * @return bool 是否成功
     */
    public function begin_transaction() {
        return $this->conn->begin_transaction();
    }
    
    /**
     * 提交事务
     * @return bool 是否成功
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * 回滚事务
     * @return bool 是否成功
     */
    public function rollback() {
        return $this->conn->rollback();
    }
    
    /**
     * 析构函数
     */
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>
