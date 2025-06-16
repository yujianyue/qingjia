<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: inc/pubs.php
// 文件大小: 7226 字节
// 最后修改时间: 2025-05-20 22:39:38
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 公共PHP函数
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

/**
 * 返回JSON格式响应
 * @param int $code 状态码（0为成功，其他为失败）
 * @param string $msg 消息内容
 * @param array $data 返回数据
 * @return string JSON字符串
 */
function json_result($code, $msg, $data = []) {
    $result = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 检查用户是否已登录
 * @param string $role 用户角色（可选）
 * @return bool 是否已登录
 */
function check_login($role = '') {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    
    if (!empty($role) && $_SESSION['user_type'] != $role) {
        return false;
    }
    
    return true;
}

/**
 * 安全过滤输入
 * @param string $str 需要过滤的字符串
 * @return string 过滤后的字符串
 */
function safe_input($str) {
    $str = trim($str);
    $str = stripslashes($str);
    $str = htmlspecialchars($str, ENT_QUOTES);
    return $str;
}

/**
 * 防止SQL注入
 * @param mixed $input 输入值
 * @return mixed 过滤后的值
 */
function sql_safe($input) {
    global $conn;
    
    if (is_array($input)) {
        foreach ($input as $key => $val) {
            $input[$key] = sql_safe($val);
        }
        return $input;
    }
    
    if (!isset($conn)) {
        $conn = db_connect();
    }
    
    if (get_magic_quotes_gpc()) {
        $input = stripslashes($input);
    }
    
    return $conn->real_escape_string($input);
}

/**
 * 分页函数
 * @param int $total 总记录数
 * @param int $page 当前页码
 * @param int $size 每页大小
 * @return array 分页信息
 */
function pagination($total, $page = 1, $size = 10) {
    $page = max(1, intval($page));
    $total_pages = ceil($total / $size);
    
    return [
        'total' => $total,
        'page' => $page,
        'size' => $size,
        'total_pages' => $total_pages,
        'start' => ($page - 1) * $size,
        'has_prev' => $page > 1,
        'has_next' => $page < $total_pages
    ];
}

/**
 * CSV导入函数
 * @param string $file_path CSV文件路径
 * @param array $fields 字段映射
 * @return array 导入的数据
 */
function import_csv($file_path, $fields) {
    $data = [];
    
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $item = [];
            foreach ($fields as $index => $field) {
                $item[$field] = isset($row[$index]) ? $row[$index] : '';
            }
            $data[] = $item;
        }
        fclose($handle);
    }
    
    return $data;
}

/**
 * 生成随机密码
 * @param int $length 密码长度
 * @return string 生成的密码
 */
function generate_password($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * 密码加密
 * @param string $password 原始密码
 * @return string 加密后的密码
 */
function password_encrypt($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * 密码验证
 * @param string $password 原始密码
 * @param string $hash 加密后的密码
 * @return bool 验证结果
 */
function password_verify_custom($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * 上传文件
 * @param array $file $_FILES数组元素
 * @param string $dir 上传目录
 * @return array 上传结果
 */
function upload_file($file, $dir = '') {
    global $upload_max_size, $upload_allowed_types, $upload_path;
    
    // 检查上传错误
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['code' => 1, 'msg' => '文件上传失败，错误码：' . $file['error']];
    }
    
    // 检查文件大小
    if ($file['size'] > $upload_max_size) {
        return ['code' => 1, 'msg' => '文件大小超过限制'];
    }
    
    // 检查文件类型
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $upload_allowed_types)) {
        return ['code' => 1, 'msg' => '不支持的文件类型'];
    }
    
    // 设置上传路径
    $upload_dir = $upload_path;
    if (!empty($dir)) {
        $upload_dir .= $dir . '/';
    }
    
    // 确保目录存在
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // 生成唯一文件名
    $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    // 移动上传的文件
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['code' => 1, 'msg' => '文件保存失败'];
    }
    
    return [
        'code' => 0,
        'msg' => '上传成功',
        'data' => [
            'filename' => $filename,
            'filepath' => $filepath,
            'filesize' => $file['size'],
            'filetype' => $file['type']
        ]
    ];
}

/**
 * 获取当前日期时间
 * @param string $format 日期格式
 * @return string 格式化的日期时间
 */
function get_datetime($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * 从txt文件导入数据到数据库
 * @param string $file_path 文件路径
 * @param array $fields 字段映射
 * @return array 导入结果
 */
function import_txt_to_db($file_path, $fields, $table) {
    global $conn;
    
    if (!file_exists($file_path)) {
        return ['code' => 1, 'msg' => '文件不存在'];
    }
    
    if (!isset($conn)) {
        $conn = db_connect();
    }
    
    $data = [];
    $success = 0;
    $errors = [];
    
    $content = file_get_contents($file_path);
    $rows = explode("\n", $content);
    
    foreach ($rows as $index => $row) {
        if (empty(trim($row))) continue;
        
        $columns = explode("\t", $row);
        
        if (count($columns) < count($fields)) {
            $errors[] = "第" . ($index + 1) . "行: 列数不足";
            continue;
        }
        
        $item = [];
        foreach ($fields as $idx => $field) {
            $item[$field] = isset($columns[$idx]) ? trim($columns[$idx]) : '';
        }
        
        $data[] = $item;
        
        // 插入数据库
        $fields_sql = implode("`, `", array_keys($item));
        $values_sql = implode("', '", array_map([$conn, 'real_escape_string'], array_values($item)));
        
        $sql = "INSERT INTO `{$table}` (`{$fields_sql}`) VALUES ('{$values_sql}')";
        
        if ($conn->query($sql)) {
            $success++;
        } else {
            $errors[] = "第" . ($index + 1) . "行: " . $conn->error;
        }
    }
    
    return [
        'code' => 0,
        'msg' => "导入完成，成功: {$success}, 失败: " . count($errors),
        'data' => [
            'success' => $success,
            'errors' => $errors
        ]
    ];
}
?>
