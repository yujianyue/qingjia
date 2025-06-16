<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: index.php
// 文件大小: 1045 字节
// 最后修改时间: 2025-05-20 22:45:46
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 主入口
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

require_once './inc/conn.php';
require_once './inc/pubs.php';
require_once './inc/sqls.php';

// 如果数据库未安装，重定向到安装页面
$db = db_connect();
if (!$db) {
    header('Location: install.php');
    exit;
}

// 检查用户类型并重定向
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
    
    switch ($user_type) {
        case 'admin':
            header('Location: sys.php');
            break;
        case 'student':
            header('Location: stu.php');
            break;
        case 'instructor':
            header('Location: zhi.php');
            break;
        default:
            // 未知用户类型，进入登录页面
            header('Location: sys.php?do=login');
    }
    exit;
}

// 未登录用户进入系统登录页
header('Location: sys.php?do=login');
exit;
?>
