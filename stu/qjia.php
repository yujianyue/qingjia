<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: stu/qjia.php
// 文件大小: 6398 字节
// 最后修改时间: 2025-05-20 22:50:06
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 学生请假申请
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 连接数据库
$db = new Database();

// 获取当前学生信息
$student = $db->get_one('stux', ['id' => $_SESSION['user_id']]);

// 获取指导员信息
$instructor = null;
if (!empty($student['instructor_id'])) {
    $instructor = $db->get_one('stux', ['id' => $student['instructor_id']]);
}

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'submit':
            // 提交请假申请
            $start_time = isset($_POST['start_time']) ? safe_input($_POST['start_time']) : '';
            $end_time = isset($_POST['end_time']) ? safe_input($_POST['end_time']) : '';
            $reason = isset($_POST['reason']) ? safe_input($_POST['reason']) : '';
            
            if (empty($start_time) || empty($end_time) || empty($reason)) {
                json_result(1, '所有字段不能为空');
            }
            
            // 检查时间是否合法
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            
            if ($start_timestamp === false || $end_timestamp === false) {
                json_result(1, '时间格式不正确');
            }
            
            if ($start_timestamp > $end_timestamp) {
                json_result(1, '开始时间不能晚于结束时间');
            }
            
            // 插入请假记录
            $data = [
                'student_id' => $student['student_id'],
                'real_name' => $student['real_name'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'reason' => $reason,
                'submit_user' => $student['student_id'],
                'status' => 0, // 待审核
                'create_time' => date('Y-m-d H:i:s')
            ];
            
            $result = $db->insert('qjia', $data);
            
            if ($result) {
                json_result(0, '请假申请提交成功，请等待审批');
            } else {
                json_result(1, '请假申请提交失败，请重试');
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
    <div class="card-header">请假申请</div>
    <div class="card-body">
        <?php if (!$instructor): ?>
        <div class="alert alert-warning">
            您尚未绑定指导员，请联系指导员进行绑定后再提交请假申请。
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <p>您的指导员：<?php echo $instructor['real_name']; ?></p>
            <p>联系电话：<?php echo $instructor['phone']; ?></p>
        </div>
        
        <form id="leaveForm">
            <div class="form-group">
                <label class="form-label">开始时间</label>
                <input type="datetime-local" name="start_time" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">结束时间</label>
                <input type="datetime-local" name="end_time" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">请假事由</label>
                <textarea name="reason" class="form-control" rows="5" placeholder="请详细描述请假原因"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" id="submitBtn" class="btn btn-primary">提交申请</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 设置时间控件默认值
        var now = new Date();
        var tomorrow = new Date();
        tomorrow.setDate(now.getDate() + 1);
        
        // 格式化为datetime-local格式 (YYYY-MM-DDTHH:MM)
        function formatDatetimeLocal(date) {
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0') + 'T' + 
                   String(date.getHours()).padStart(2, '0') + ':' + 
                   String(date.getMinutes()).padStart(2, '0');
        }
        
        var startInput = document.querySelector('input[name="start_time"]');
        var endInput = document.querySelector('input[name="end_time"]');
        
        if (startInput && endInput) {
            startInput.value = formatDatetimeLocal(now);
            endInput.value = formatDatetimeLocal(tomorrow);
            
            // 提交按钮点击事件
            document.getElementById('submitBtn').addEventListener('click', function() {
                var formData = serializeForm('leaveForm');
                
                // 表单验证
                if (!formData.start_time) {
                    showToast('请选择开始时间', 'warning');
                    return;
                }
                
                if (!formData.end_time) {
                    showToast('请选择结束时间', 'warning');
                    return;
                }
                
                if (!formData.reason) {
                    showToast('请填写请假事由', 'warning');
                    return;
                }
                
                if (formData.reason.length < 5) {
                    showToast('请假事由太简短，请详细描述', 'warning');
                    return;
                }
                
                // 发送请求
                ajaxRequest('?do=qjia&act=submit', formData, function(res) {
                    if (res.code === 0) {
                        showToast(res.msg, 'success');
                        setTimeout(function() {
                            window.location.href = '?do=list';
                        }, 1500);
                    } else {
                        showToast(res.msg, 'error');
                    }
                });
            });
        }
    });
</script>

<?php include './inc/foot.php'; ?>
