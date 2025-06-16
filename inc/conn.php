<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: inc/conn.php
// 文件大小: 2024 字节
// 最后修改时间: 2025-06-12 18:32:02
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 数据库连接及公共配置
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 数据库连接参数
$db_host = 'localhost';
$db_user = 'qinjia_chalide';
$db_pass = '3KEpfZicRCtDPPPN';
$db_name = 'qinjia_chalide';
$db_port = 3306;
$db_charset = 'utf8mb4';

// 系统版本号（用于刷新缓存）
$version = 'V1.0.01@'.date("YmdHis");

// 文件上传配置
$upload_max_size = 2 * 1024 * 1024; // 2MB
$upload_allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$upload_path = '../uploads/';

// 用户角色定义
$user_types = [
    'student' => '学生',
    'instructor' => '指导员',
    'admin' => '系统'
];

// 菜单配置
$menus = [
    'admin' => [
        ['name' => '设置', 'do' => 'site'],
        ['name' => '账号管理', 'do' => 'lius'],
        ['name' => '导入学生', 'do' => 'inxs'],
        ['name' => '请假记录', 'do' => 'liqj'],
        ['name' => '修改密码', 'do' => 'pass']
    ],
    'student' => [
        ['name' => '我的请假', 'do' => 'qjia'],
        ['name' => '请假记录', 'do' => 'list'],
        ['name' => '修改密码', 'do' => 'pass']
    ],
    'instructor' => [
        ['name' => '请假记录', 'do' => 'qjia'],
        ['name' => '我的学生', 'do' => 'stux'],
        ['name' => '学生绑定', 'do' => 'bang'],
        ['name' => '修改密码', 'do' => 'pass']
    ]
];

// 数据库连接
function db_connect() {
    global $db_host, $db_user, $db_pass, $db_name, $db_port, $db_charset;
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    
    $conn->set_charset($db_charset);
    
    return $conn;
}

// 会话初始化
session_start();

// 默认时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告设置（生产环境应移除）
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
