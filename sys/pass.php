<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: sys/pass.php
// 文件大小: 4832 字节
// 最后修改时间: 2025-05-20 22:48:42
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 管理员修改密码
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'change':
            // 修改密码处理
            $old_password = isset($_POST['old_password']) ? $_POST['old_password'] : '';
            $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
            $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
            
            if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                json_result(1, '所有字段不能为空');
            }
            
            if ($new_password !== $confirm_password) {
                json_result(1, '两次输入的新密码不一致');
            }
            
            if (strlen($new_password) < 6) {
                json_result(1, '新密码长度不能少于6位');
            }
            
            // 连接数据库
            $db = new Database();
            
            // 查询用户
            $user = $db->get_one('stux', ['id' => $_SESSION['user_id']]);
            
            if (!$user) {
                json_result(1, '用户不存在');
            }
            
            // 验证旧密码
            if (!password_verify($old_password, $user['password'])) {
                json_result(1, '旧密码不正确');
            }
            
            // 更新密码
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $result = $db->update('stux', ['password' => $new_password_hash], ['id' => $_SESSION['user_id']]);
            
            if ($result) {
                json_result(0, '密码修改成功，请重新登录', ['url' => '?do=login']);
            } else {
                json_result(1, '密码修改失败，请重试');
            }
            break;
            
        default:
            json_result(1, '未知操作');
    }
    
    exit;
}
?>
<?php include './inc/head.php'; ?>

<div class="card">
    <div class="card-header">修改密码</div>
    <div class="card-body">
        <form id="passwordForm">
            <div class="form-group">
                <label class="form-label">旧密码</label>
                <input type="password" name="old_password" class="form-control" placeholder="请输入旧密码">
            </div>
            
            <div class="form-group">
                <label class="form-label">新密码</label>
                <input type="password" name="new_password" class="form-control" placeholder="请输入新密码">
            </div>
            
            <div class="form-group">
                <label class="form-label">确认新密码</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="请再次输入新密码">
            </div>
            
            <div class="form-actions">
                <button type="button" id="submitBtn" class="btn btn-primary">修改密码</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = serializeForm('passwordForm');
            
            // 表单验证
            if (!formData.old_password) {
                showToast('请输入旧密码', 'warning');
                return;
            }
            
            if (!formData.new_password) {
                showToast('请输入新密码', 'warning');
                return;
            }
            
            if (formData.new_password.length < 6) {
                showToast('新密码长度不能少于6位', 'warning');
                return;
            }
            
            if (!formData.confirm_password) {
                showToast('请确认新密码', 'warning');
                return;
            }
            
            if (formData.new_password !== formData.confirm_password) {
                showToast('两次输入的新密码不一致', 'warning');
                return;
            }
            
            // 发送请求
            ajaxRequest('?do=pass&act=change', formData, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                    setTimeout(function() {
                        window.location.href = res.data.url;
                    }, 1500);
                } else {
                    showToast(res.msg, 'error');
                }
            });
        });
    });
</script>

<?php include './inc/foot.php'; ?>
