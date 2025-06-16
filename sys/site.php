<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: sys/site.php
// 文件大小: 6858 字节
// 最后修改时间: 2025-05-20 23:03:18
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 系统设置
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 获取当前系统设置
$json_file = __DIR__ . '/../inc/json.php';
$site_config = [];

if (file_exists($json_file)) {
    $site_config = json_decode(file_get_contents($json_file), true);
}

// 没有配置文件时初始化默认配置
if (empty($site_config)) {
    $site_config = [
        'site_name' => '学生请假管理系统',
        'site_description' => '方便快捷的学生请假管理平台',
        'admin_email' => 'admin@example.com',
        'records_per_page' => 10,
        'upload_max_size' => 2097152,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif']
    ];
}

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'save':
            // 保存系统设置
            $site_name = isset($_POST['site_name']) ? safe_input($_POST['site_name']) : '';
            $site_description = isset($_POST['site_description']) ? safe_input($_POST['site_description']) : '';
            $admin_email = isset($_POST['admin_email']) ? safe_input($_POST['admin_email']) : '';
            $records_per_page = isset($_POST['records_per_page']) ? intval($_POST['records_per_page']) : 10;
            $upload_max_size = isset($_POST['upload_max_size']) ? intval($_POST['upload_max_size']) : 2097152;
            
            if (empty($site_name)) {
                json_result(1, '网站名称不能为空');
            }
            
            if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                json_result(1, '管理员邮箱格式不正确');
            }
            
            if ($records_per_page < 5 || $records_per_page > 100) {
                json_result(1, '每页记录数必须在5-100之间');
            }
            
            if ($upload_max_size < 512000 || $upload_max_size > 10485760) {
                json_result(1, '上传文件大小限制必须在500KB-10MB之间');
            }
            
            // 更新配置
            $site_config['site_name'] = $site_name;
            $site_config['site_description'] = $site_description;
            $site_config['admin_email'] = $admin_email;
            $site_config['records_per_page'] = $records_per_page;
            $site_config['upload_max_size'] = $upload_max_size;
            
            // 保存配置到文件
            $json_content = json_encode($site_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (file_put_contents($json_file, $json_content)) {
                json_result(0, '设置保存成功');
            } else {
                json_result(1, '设置保存失败，请检查文件权限');
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
    <div class="card-header">系统设置</div>
    <div class="card-body">
        <form id="settingsForm">
            <div class="form-group">
                <label class="form-label">网站名称</label>
                <input type="text" name="site_name" class="form-control" value="<?php echo $site_config['site_name']; ?>" placeholder="网站名称">
            </div>
            
            <div class="form-group">
                <label class="form-label">网站描述</label>
                <textarea name="site_description" class="form-control" rows="3" placeholder="网站描述"><?php echo $site_config['site_description']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">管理员邮箱</label>
                <input type="email" name="admin_email" class="form-control" value="<?php echo $site_config['admin_email']; ?>" placeholder="管理员邮箱">
            </div>
            
            <div class="form-group">
                <label class="form-label">每页记录数</label>
                <input type="number" name="records_per_page" class="form-control" value="<?php echo $site_config['records_per_page']; ?>" min="5" max="100" placeholder="每页记录数">
                <small class="text-muted">设置列表每页显示的记录数量，建议5-100之间</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">上传文件大小限制（字节）</label>
                <input type="number" name="upload_max_size" class="form-control" value="<?php echo $site_config['upload_max_size']; ?>" min="512000" max="10485760" placeholder="上传文件大小限制">
                <small class="text-muted">设置上传文件的最大大小，单位字节。当前值：<?php echo round($site_config['upload_max_size'] / 1024 / 1024, 2); ?>MB</small>
            </div>
            
            <div class="form-actions">
                <button type="button" id="submitBtn" class="btn btn-primary">保存设置</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = serializeForm('settingsForm');
            
            // 表单验证
            if (!formData.site_name) {
                showToast('请输入网站名称', 'warning');
                return;
            }
            
            if (!formData.admin_email) {
                showToast('请输入管理员邮箱', 'warning');
                return;
            }
            
            // 验证邮箱格式
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(formData.admin_email)) {
                showToast('管理员邮箱格式不正确', 'warning');
                return;
            }
            
            // 数值验证
            if (parseInt(formData.records_per_page) < 5 || parseInt(formData.records_per_page) > 100) {
                showToast('每页记录数必须在5-100之间', 'warning');
                return;
            }
            
            if (parseInt(formData.upload_max_size) < 512000 || parseInt(formData.upload_max_size) > 10485760) {
                showToast('上传文件大小限制必须在500KB-10MB之间', 'warning');
                return;
            }
            
            // 发送请求
            ajaxRequest('?do=site&act=save', formData, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                } else {
                    showToast(res.msg, 'error');
                }
            });
        });
    });
</script>

<?php include './inc/foot.php'; ?>
