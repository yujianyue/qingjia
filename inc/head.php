<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: inc/head.php
// 文件大小: 2247 字节
// 最后修改时间: 2025-05-20 22:43:50
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 公共头部
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 检查是否登录
if (!isset($_SESSION['user_id']) && !in_array(isset($_GET['do']) ? $_GET['do'] : '', ['login', 'install'])) {
    header('Location: ?do=login');
    exit;
}

// 获取当前模块
$current_do = isset($_GET['do']) ? $_GET['do'] : 'index';

// 加载网站配置
$site_config = [];
if (file_exists(__DIR__ . '/json.php')) {
    $site_config = json_decode(file_get_contents(__DIR__ . '/json.php'), true);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($site_config['site_name']) ? $site_config['site_name'] : '学生请假管理系统'; ?></title>
    <link rel="stylesheet" href="inc/css.css?v=<?php echo $version; ?>">
    <script src="inc/js.js?v=<?php echo $version; ?>"></script>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['user_id']) && $current_do != 'login'): ?>
        <div class="header">
            <div class="header-top">
                <div class="header-title"><?php echo isset($site_config['site_name']) ? $site_config['site_name'] : '学生请假管理系统'; ?></div>
                <div class="header-user">
                    <span>欢迎您，<?php echo $_SESSION['user_name']; ?></span>
                    <a href="?do=pass">修改密码</a>
                    <a href="?do=logout">退出</a>
                </div>
            </div>
            <div class="nav">
                <?php
                // 根据用户角色显示相应菜单
                $user_role = $_SESSION['user_type'] ?? '';
                $menu_list = isset($menus[$user_role]) ? $menus[$user_role] : [];
                
                foreach ($menu_list as $menu) {
                    $active = ($current_do == $menu['do']) ? 'active' : '';
                    echo '<a href="?do=' . $menu['do'] . '" class="nav-item ' . $active . '">' . $menu['name'] . '</a>';
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="content">
