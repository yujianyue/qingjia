<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: install.php
// 文件大小: 20979 字节
// 最后修改时间: 2025-05-20 22:45:30
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 数据库安装脚本
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

require_once './inc/conn.php';

// 检查是否为AJAX请求
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// AJAX处理
if ($is_ajax && isset($_GET['act'])) {
    $action = $_GET['act'];
    
    switch ($action) {
        case 'check':
            // 检查环境
            $result = [
                'code' => 0,
                'msg' => '环境检查完成',
                'data' => [
                    'php_version' => PHP_VERSION,
                    'php_version_ok' => version_compare(PHP_VERSION, '7.0.0', '>='),
                    'mysqli_extension' => extension_loaded('mysqli'),
                    'gd_extension' => extension_loaded('gd'),
                    'pdo_extension' => extension_loaded('pdo'),
                    'writeable' => [
                        'inc' => is_writable('./inc'),
                        'uploads' => is_dir('./uploads') ? is_writable('./uploads') : mkdir('./uploads', 0777, true)
                    ]
                ]
            ];
            
            // 检查数据库连接
            $db_connect = false;
            try {
                $conn = new mysqli($db_host, $db_user, $db_pass);
                if (!$conn->connect_error) {
                    $db_connect = true;
                }
                $conn->close();
            } catch (Exception $e) {
                // 数据库连接失败
            }
            
            $result['data']['db_connect'] = $db_connect;
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
            
        case 'install':
            // 创建数据库
            $result = [
                'code' => 0,
                'msg' => '安装成功',
                'data' => []
            ];
            
            try {
                // 连接数据库
                $conn = new mysqli($db_host, $db_user, $db_pass);
                
                if ($conn->connect_error) {
                    throw new Exception("数据库连接失败: " . $conn->connect_error);
                }
                
                // 创建数据库
                $sql = "CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
                if (!$conn->query($sql)) {
                    throw new Exception("创建数据库失败: " . $conn->error);
                }
                
                // 选择数据库
                $conn->select_db($db_name);
                
                // 创建学生表
                $sql = "CREATE TABLE IF NOT EXISTS `stux` (
                    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
                    `student_id` varchar(32) NOT NULL COMMENT '学号',
                    `password` varchar(255) NOT NULL COMMENT '密码',
                    `real_name` varchar(50) NOT NULL COMMENT '实名',
                    `phone` varchar(20) NOT NULL COMMENT '电话',
                    `instructor_id` int(11) DEFAULT NULL COMMENT '指导员ID',
                    `type` enum('student','instructor','admin') NOT NULL DEFAULT 'student' COMMENT '类型：学生|指导员|系统',
                    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0-禁用，1-启用',
                    `remark` text COMMENT '备注',
                    `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `student_id` (`student_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='学生表'";
                
                if (!$conn->query($sql)) {
                    throw new Exception("创建学生表失败: " . $conn->error);
                }
                
                $result['data']['stux'] = true;
                
                // 创建请假表
                $sql = "CREATE TABLE IF NOT EXISTS `qjia` (
                    `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
                    `student_id` varchar(32) NOT NULL COMMENT '学号',
                    `real_name` varchar(50) NOT NULL COMMENT '实名',
                    `start_time` datetime NOT NULL COMMENT '开始时间',
                    `end_time` datetime NOT NULL COMMENT '结束时间',
                    `reason` text NOT NULL COMMENT '事由',
                    `submit_user` varchar(32) NOT NULL COMMENT '提交账号',
                    `verify_user` varchar(32) DEFAULT NULL COMMENT '核销账号',
                    `verify_time` datetime DEFAULT NULL COMMENT '核销时间',
                    `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0-待审核，1-已批准，2-已驳回',
                    `create_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
                    PRIMARY KEY (`id`),
                    KEY `student_id` (`student_id`),
                    KEY `status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='请假表'";
                
                if (!$conn->query($sql)) {
                    throw new Exception("创建请假表失败: " . $conn->error);
                }
                
                $result['data']['qjia'] = true;
                
                // 创建系统管理员账号
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                $sql = "INSERT INTO `stux` (`student_id`, `password`, `real_name`, `phone`, `type`, `status`) 
                        VALUES ('admin', '{$admin_password}', '系统管理员', '15058593138', 'admin', 1)";
                
                if (!$conn->query($sql) && $conn->errno != 1062) { // 1062 是重复键错误
                    throw new Exception("创建管理员账号失败: " . $conn->error);
                }
                
                $result['data']['admin'] = true;
                
                // 导入演示数据
                if (isset($_GET['demo']) && $_GET['demo'] == 1) {
                    // 创建演示指导员账号
                    $instructor_password = password_hash('123456', PASSWORD_DEFAULT);
                    $sql = "INSERT INTO `stux` (`student_id`, `password`, `real_name`, `phone`, `type`, `status`) 
                            VALUES ('teacher1', '{$instructor_password}', '张指导', '13800138001', 'instructor', 1),
                                   ('teacher2', '{$instructor_password}', '李指导', '13800138002', 'instructor', 1)";
                    
                    if (!$conn->query($sql) && $conn->errno != 1062) {
                        throw new Exception("创建演示指导员账号失败: " . $conn->error);
                    }
                    
                    // 获取指导员ID
                    $sql = "SELECT id FROM `stux` WHERE student_id = 'teacher1' LIMIT 1";
                    $instructor_result = $conn->query($sql);
                    $instructor_id = 0;
                    
                    if ($instructor_result && $instructor_result->num_rows > 0) {
                        $instructor_row = $instructor_result->fetch_assoc();
                        $instructor_id = $instructor_row['id'];
                    }
                    
                    // 创建演示学生账号
                    for ($i = 1; $i <= 30; $i++) {
                        $student_id = sprintf('stu%03d', $i);
                        $student_password = password_hash('123456', PASSWORD_DEFAULT);
                        $real_name = '学生' . $i;
                        $phone = '139' . str_pad($i, 8, '0', STR_PAD_LEFT);
                        
                        $sql = "INSERT INTO `stux` (`student_id`, `password`, `real_name`, `phone`, `instructor_id`, `type`, `status`) 
                                VALUES ('{$student_id}', '{$student_password}', '{$real_name}', '{$phone}', {$instructor_id}, 'student', 1)";
                        
                        $conn->query($sql);
                    }
                    
                    // 创建演示请假记录
                    for ($i = 1; $i <= 30; $i++) {
                        $student_id = sprintf('stu%03d', $i % 10 + 1);
                        $sql = "SELECT real_name FROM `stux` WHERE student_id = '{$student_id}' LIMIT 1";
                        $student_result = $conn->query($sql);
                        $real_name = '';
                        
                        if ($student_result && $student_result->num_rows > 0) {
                            $student_row = $student_result->fetch_assoc();
                            $real_name = $student_row['real_name'];
                        }
                        
                        $start_time = date('Y-m-d H:i:s', strtotime("-" . ($i % 10) . " days"));
                        $end_time = date('Y-m-d H:i:s', strtotime($start_time . " +3 days"));
                        $reason = '请假事由' . $i;
                        $status = $i % 3; // 0-待审核，1-已批准，2-已驳回
                        
                        $sql = "INSERT INTO `qjia` (`student_id`, `real_name`, `start_time`, `end_time`, `reason`, `submit_user`, `verify_user`, `verify_time`, `status`) 
                                VALUES ('{$student_id}', '{$real_name}', '{$start_time}', '{$end_time}', '{$reason}', '{$student_id}', " . 
                                ($status == 0 ? "NULL, NULL" : "'teacher1', NOW()") . ", {$status})";
                        
                        $conn->query($sql);
                    }
                    
                    $result['data']['demo'] = true;
                }
                
                $conn->close();
                
            } catch (Exception $e) {
                $result = [
                    'code' => 1,
                    'msg' => $e->getMessage()
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 - 学生请假管理系统</title>
    <link rel="stylesheet" href="inc/css.css?v=<?php echo $version; ?>">
    <script src="inc/js.js?v=<?php echo $version; ?>"></script>
    <style>
        .install-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .install-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        .install-header h2 {
            margin: 0;
            color: #333;
        }
        .install-body {
            padding: 20px;
        }
        .install-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            background-color: #f9f9f9;
        }
        .install-step {
            margin-bottom: 30px;
        }
        .install-step-title {
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .install-check-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .install-check-item:last-child {
            border-bottom: none;
        }
        .install-check-status {
            font-weight: bold;
        }
        .install-check-status.success {
            color: #2ecc71;
        }
        .install-check-status.error {
            color: #e74c3c;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h2>学生请假管理系统 - 安装向导</h2>
        </div>
        <div class="install-body">
            <div class="install-step" id="step1">
                <div class="install-step-title">环境检查</div>
                <div id="env-check-list">
                    <div class="alert">正在检查环境，请稍候...</div>
                </div>
            </div>
            
            <div class="install-step hidden" id="step2">
                <div class="install-step-title">数据库安装</div>
                <div id="db-install-result">
                    <div class="alert">请点击下方"安装数据库"按钮开始安装...</div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="install-demo" checked> 安装演示数据（包含测试账号和数据）
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="install-step hidden" id="step3">
                <div class="install-step-title">安装完成</div>
                <div id="install-complete">
                    <div class="alert alert-success">
                        <p>恭喜您，系统安装成功！</p>
                        <p>默认管理员账号：</p>
                        <ul>
                            <li>账号：admin</li>
                            <li>密码：admin123</li>
                        </ul>
                        <p>演示账号（如果已安装演示数据）：</p>
                        <ul>
                            <li>指导员账号：teacher1，密码：123456</li>
                            <li>学生账号：stu001，密码：123456</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="install-footer">
            <button class="btn btn-primary" id="check-env">检查环境</button>
            <button class="btn btn-primary hidden" id="install-db">安装数据库</button>
            <a href="index.php" class="btn btn-success hidden" id="goto-index">进入系统</a>
        </div>
    </div>

    <script>
        // 检查环境
        function checkEnvironment() {
            document.getElementById('check-env').disabled = true;
            
            ajaxRequest('install.php?act=check', {}, function(res) {
                if (res.code === 0) {
                    var html = '';
                    var envData = res.data;
                    var allPass = true;
                    
                    html += '<div class="install-check-item">';
                    html += '<span>PHP版本：' + envData.php_version + '（要求 >= 7.0）</span>';
                    html += '<span class="install-check-status ' + (envData.php_version_ok ? 'success' : 'error') + '">' + (envData.php_version_ok ? '通过' : '未通过') + '</span>';
                    html += '</div>';
                    
                    if (!envData.php_version_ok) allPass = false;
                    
                    html += '<div class="install-check-item">';
                    html += '<span>MySQLi扩展</span>';
                    html += '<span class="install-check-status ' + (envData.mysqli_extension ? 'success' : 'error') + '">' + (envData.mysqli_extension ? '通过' : '未通过') + '</span>';
                    html += '</div>';
                    
                    if (!envData.mysqli_extension) allPass = false;
                    
                    html += '<div class="install-check-item">';
                    html += '<span>GD扩展（图像处理）</span>';
                    html += '<span class="install-check-status ' + (envData.gd_extension ? 'success' : 'error') + '">' + (envData.gd_extension ? '通过' : '未通过') + '</span>';
                    html += '</div>';
                    
                    html += '<div class="install-check-item">';
                    html += '<span>PDO扩展</span>';
                    html += '<span class="install-check-status ' + (envData.pdo_extension ? 'success' : 'error') + '">' + (envData.pdo_extension ? '通过' : '未通过') + '</span>';
                    html += '</div>';
                    
                    html += '<div class="install-check-item">';
                    html += '<span>数据库连接</span>';
                    html += '<span class="install-check-status ' + (envData.db_connect ? 'success' : 'error') + '">' + (envData.db_connect ? '通过' : '未通过') + '</span>';
                    html += '</div>';
                    
                    if (!envData.db_connect) allPass = false;
                    
                    html += '<div class="install-check-item">';
                    html += '<span>inc目录可写</span>';
                    html += '<span class="install-check-status ' + (envData.writeable.inc ? 'success' : 'error') + '">' + (envData.writeable.inc ? '通过' : '未通过') + '</span>';
                    html += '</div>';
                    
                    if (!envData.writeable.inc) allPass = false;
                    
                    html += '<div class="install-check-item">';
                    html += '<span>uploads目录可写</span>';
                    html += '<span class="install-check-status ' + (envData.writeable.uploads ? 'success' : 'error') + '">' + (envData.writeable.uploads ? '通过' : '未通过') + '</span>';
                    html += '</div>';
                    
                    if (!envData.writeable.uploads) allPass = false;
                    
                    document.getElementById('env-check-list').innerHTML = html;
                    
                    if (allPass) {
                        document.getElementById('step2').classList.remove('hidden');
                        document.getElementById('check-env').classList.add('hidden');
                        document.getElementById('install-db').classList.remove('hidden');
                    } else {
                        document.getElementById('check-env').disabled = false;
                        showToast('环境检查未通过，请修复后重试', 'error');
                    }
                } else {
                    document.getElementById('check-env').disabled = false;
                    showToast(res.msg || '检查环境失败', 'error');
                }
            });
        }
        
        // 安装数据库
        function installDatabase() {
            document.getElementById('install-db').disabled = true;
            
            var installDemo = document.getElementById('install-demo').checked ? 1 : 0;
            
            ajaxRequest('install.php?act=install&demo=' + installDemo, {}, function(res) {
                if (res.code === 0) {
                    document.getElementById('db-install-result').innerHTML = '<div class="alert alert-success">数据库安装成功！</div>';
                    
                    var html = '<ul>';
                    html += '<li>学生表(stux): ' + (res.data.stux ? '创建成功' : '创建失败') + '</li>';
                    html += '<li>请假表(qjia): ' + (res.data.qjia ? '创建成功' : '创建失败') + '</li>';
                    html += '<li>管理员账号: ' + (res.data.admin ? '创建成功' : '创建失败') + '</li>';
                    
                    if (installDemo && res.data.demo) {
                        html += '<li>演示数据: 创建成功</li>';
                    }
                    
                    html += '</ul>';
                    
                    document.getElementById('db-install-result').innerHTML += html;
                    
                    document.getElementById('step3').classList.remove('hidden');
                    document.getElementById('install-db').classList.add('hidden');
                    document.getElementById('goto-index').classList.remove('hidden');
                } else {
                    document.getElementById('install-db').disabled = false;
                    document.getElementById('db-install-result').innerHTML = '<div class="alert alert-danger">安装失败：' + (res.msg || '未知错误') + '</div>';
                }
            });
        }
        
        // 事件绑定
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('check-env').addEventListener('click', checkEnvironment);
            document.getElementById('install-db').addEventListener('click', installDatabase);
            
            // 自动检查环境
            checkEnvironment();
        });
    </script>
</body>
</html>
