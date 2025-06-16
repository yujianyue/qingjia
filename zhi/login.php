<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: zhi/login.php
// 文件大小: 4296 字节
// 最后修改时间: 2025-05-20 22:47:42
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 指导员登录
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'login':
            // 登录处理
            $username = isset($_POST['username']) ? safe_input($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            
            if (empty($username) || empty($password)) {
                json_result(1, '用户名和密码不能为空');
            }
            
            // 连接数据库
            $db = new Database();
            
            // 查询用户
            $user = $db->get_one('stux', ['student_id' => $username]);
            
            if (!$user) {
                json_result(1, '用户名或密码错误');
            }
            
            // 验证密码
            if (!password_verify($password, $user['password'])) {
                json_result(1, '用户名或密码错误');
            }
            
            // 验证用户类型
            if ($user['type'] !== 'instructor') {
                json_result(1, '您不是指导员，请前往相应入口登录');
            }
            
            // 验证用户状态
            if ($user['status'] !== '1') {
                json_result(1, '账号已被禁用，请联系管理员');
            }
            
            // 设置会话
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['real_name'];
            $_SESSION['user_type'] = $user['type'];
            $_SESSION['student_id'] = $user['student_id'];
            
            json_result(0, '登录成功', ['url' => 'zhi.php?do=qjia']);
            break;
            
        default:
            json_result(1, '未知操作');
    }
    
    exit;
}
?>
<?php include './inc/head.php'; ?>

<div class="login-container">
    <div class="login-title">指导员登录</div>
    
    <form id="loginForm" class="login-form">
        <div class="form-group">
            <label class="form-label">用户名</label>
            <input type="text" name="username" class="form-control" placeholder="请输入用户名">
        </div>
        
        <div class="form-group">
            <label class="form-label">密码</label>
            <input type="password" name="password" class="form-control" placeholder="请输入密码">
        </div>
        
        <div class="form-actions">
            <button type="button" id="loginBtn" class="btn btn-primary">登录</button>
        </div>
    </form>
    
    <div class="login-links" style="margin-top: 20px; text-align: center;">
        <a href="sys.php?do=login">管理员登录</a> | 
        <a href="stu.php?do=login">学生登录</a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 登录按钮点击事件
        document.getElementById('loginBtn').addEventListener('click', function() {
            var formData = serializeForm('loginForm');
            
            // 表单验证
            if (!formData.username) {
                showToast('请输入用户名', 'warning');
                return;
            }
            
            if (!formData.password) {
                showToast('请输入密码', 'warning');
                return;
            }
            
            // 发送登录请求
            ajaxRequest('zhi.php?do=login&act=login', formData, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                    // 登录成功后跳转
                    setTimeout(function() {
                        window.location.href = res.data.url;
                    }, 1000);
                } else {
                    showToast(res.msg, 'error');
                }
            });
        });
        
        // 回车键提交表单
        document.getElementById('loginForm').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginBtn').click();
            }
        });
    });
</script>

<?php include './inc/foot.php'; ?>
