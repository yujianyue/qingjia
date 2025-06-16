<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: zhi.php
// 文件大小: 1014 字节
// 最后修改时间: 2025-05-20 22:46:18
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 指导员入口
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

require_once './inc/conn.php';
require_once './inc/pubs.php';
require_once './inc/sqls.php';

// 获取操作指令
$do = isset($_GET['do']) ? $_GET['do'] : 'index';

// 路由
switch ($do) {
    case 'login':
        // 登录页面
        include './zhi/login.php';
        break;
    case 'logout':
        // 退出登录
        session_destroy();
        header('Location: ?do=login');
        exit;
    case 'qjia':
    case 'stux':
    case 'bang':
    case 'pass':
        // 检查指导员登录状态
        if (!check_login('instructor')) {
            header('Location: ?do=login');
            exit;
        }
        
        // 加载相应的功能模块
        include './zhi/' . $do . '.php';
        break;
    default:
        // 默认重定向到请假记录页面
        header('Location: ?do=qjia');
        exit;
}
?>
