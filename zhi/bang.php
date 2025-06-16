<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: zhi/bang.php
// 文件大小: 5932 字节
// 最后修改时间: 2025-05-20 22:52:38
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 指导员绑定学生
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 连接数据库
$db = new Database();

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'bind':
            // 绑定学生
            $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : '';
            
            if (empty($student_ids)) {
                json_result(1, '请输入学号');
            }
            
            // 解析学号，支持多个学号，可以用逗号、空格、换行等分隔
            $ids = preg_split('/[\s,，;；]+/', $student_ids);
            $ids = array_filter($ids); // 移除空元素
            
            if (empty($ids)) {
                json_result(1, '解析学号失败，请检查格式');
            }
            
            // 记录绑定结果
            $success = 0;
            $failed = 0;
            $errors = [];
            
            foreach ($ids as $student_id) {
                $student_id = trim($student_id);
                
                if (empty($student_id)) {
                    continue;
                }
                
                // 检查学号是否存在
                $student = $db->get_one('stux', ['student_id' => $student_id]);
                
                if (!$student) {
                    $errors[] = "学号 {$student_id} 不存在";
                    $failed++;
                    continue;
                }
                
                // 检查用户类型是否为学生
                if ($student['type'] !== 'student') {
                    $errors[] = "用户 {$student_id} 不是学生账号";
                    $failed++;
                    continue;
                }
                
                // 检查是否已经被绑定
                if (!empty($student['instructor_id']) && $student['instructor_id'] != $_SESSION['user_id']) {
                    $errors[] = "学生 {$student_id} 已被其他指导员绑定";
                    $failed++;
                    continue;
                }
                
                // 更新学生的指导员ID
                $result = $db->update('stux', ['instructor_id' => $_SESSION['user_id']], ['id' => $student['id']]);
                
                if ($result) {
                    $success++;
                } else {
                    $errors[] = "绑定学生 {$student_id} 失败";
                    $failed++;
                }
            }
            
            json_result(0, "绑定完成：成功 {$success} 个，失败 {$failed} 个", [
                'success' => $success,
                'failed' => $failed,
                'errors' => $errors
            ]);
            break;
            
        default:
            json_result(1, '未知操作');
    }
    
    exit;
}
?>
<?php include './inc/head.php'; ?>

<div class="card">
    <div class="card-header">绑定学生</div>
    <div class="card-body">
        <div class="alert alert-info">
            <p>请输入需要绑定的学生学号，多个学号可以用逗号、空格、换行等符号分隔。</p>
            <p>例如：stu001,stu002,stu003 或 stu001 stu002 stu003</p>
        </div>
        
        <form id="bindForm">
            <div class="form-group">
                <label class="form-label">学生学号</label>
                <textarea name="student_ids" class="form-control" rows="10" placeholder="请输入学生学号，多个学号可以用逗号、空格或换行分隔"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" id="submitBtn" class="btn btn-primary">提交绑定</button>
            </div>
        </form>
        
        <div id="result" class="mt-4" style="display: none;">
            <div class="card">
                <div class="card-header">绑定结果</div>
                <div class="card-body">
                    <div id="result-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('submitBtn').addEventListener('click', function() {
            var formData = serializeForm('bindForm');
            
            // 表单验证
            if (!formData.student_ids) {
                showToast('请输入学生学号', 'warning');
                return;
            }
            
            // 发送请求
            ajaxRequest('?do=bang&act=bind', formData, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                    
                    // 显示结果
                    var resultDiv = document.getElementById('result');
                    var resultContent = document.getElementById('result-content');
                    
                    resultDiv.style.display = 'block';
                    
                    var html = '<p>绑定结果：成功 ' + res.data.success + ' 个，失败 ' + res.data.failed + ' 个</p>';
                    
                    if (res.data.errors && res.data.errors.length > 0) {
                        html += '<p>失败详情：</p><ul>';
                        
                        for (var i = 0; i < res.data.errors.length; i++) {
                            html += '<li>' + res.data.errors[i] + '</li>';
                        }
                        
                        html += '</ul>';
                    }
                    
                    resultContent.innerHTML = html;
                } else {
                    showToast(res.msg, 'error');
                }
            });
        });
    });
</script>

<?php include './inc/foot.php'; ?>
