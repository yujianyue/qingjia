<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: sys/inxs.php
// 文件大小: 8171 字节
// 最后修改时间: 2025-06-12 18:29:11
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 管理员导入学生
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 连接数据库
$db = new Database();

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'import':
            // 导入学生
            $student_data = isset($_POST['student_data']) ? $_POST['student_data'] : '';
            
            if (empty($student_data)) {
                json_result(1, '请输入学生数据');
            }
            
            // 解析学生数据
            $rows = explode("\n", $student_data);
            $success = 0;
            $failed = 0;
            $errors = [];
            
            foreach ($rows as $i => $row) {
                $row = trim($row);
                if (empty($row)) continue;
                
                // 按制表符或逗号分隔数据
                $columns = preg_split('/[\t,]+/', $row);
                
                // 检查数据格式
                if (count($columns) < 3) {
                    $errors[] = "第" . ($i + 1) . "行: 数据不完整，至少需要学号、密码和姓名";
                    $failed++;
                    continue;
                }
                
                $student_id = trim($columns[0]);
                $password = trim($columns[1]);
                $real_name = trim($columns[2]);
                $phone = isset($columns[3]) ? trim($columns[3]) : '';
                $instructor_id = isset($columns[4]) && !empty($columns[4]) ? intval(trim($columns[4])) : null;
                $type = isset($columns[5]) && !empty($columns[5]) ? trim($columns[5]) : 'student';
                
                // 验证数据
                if (empty($student_id) || empty($real_name)) {
                    $errors[] = "第" . ($i + 1) . "行: 学号和姓名不能为空";
                    $failed++;
                    continue;
                }
                
                // 如果密码为空，设置为学号
                if (empty($password)) {
                    $password = $student_id;
                }
                
                // 检查学号是否已存在
                $exist = $db->get_one('stux', ['student_id' => $student_id]);
                if ($exist) {
                    $errors[] = "第" . ($i + 1) . "行: 学号 {$student_id} 已存在";
                    $failed++;
                    continue;
                }
                
                // 如果指定了指导员ID，检查是否存在
                if ($instructor_id) {
                    $instructor = $db->get_one('stux', ['id' => $instructor_id, 'type' => 'instructor']);
                    if (!$instructor) {
                        $errors[] = "第" . ($i + 1) . "行: 指导员ID {$instructor_id} 不存在";
                        $instructor_id = null;
                    }
                }
                
                // 验证用户类型
                if (!in_array($type, ['student', 'instructor', 'admin'])) {
                    $errors[] = "第" . ($i + 1) . "行: 用户类型 {$type} 无效，已设置为默认值";
                    $type = 'student';
                }
                
                // 加密密码
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // 插入数据
                $data = [
                    'student_id' => $student_id,
                    'password' => $password_hash,
                    'real_name' => $real_name,
                    'phone' => $phone,
                    'instructor_id' => $instructor_id,
                    'type' => $type,
                    'status' => 1,
                    'create_time' => date('Y-m-d H:i:s')
                ];
                
                $result = $db->insert('stux', $data);
                
                if ($result) {
                    $success++;
                } else {
                    $errors[] = "第" . ($i + 1) . "行: 插入失败 - " . $db->error();
                    $failed++;
                }
            }
            
            json_result(0, "导入完成：成功 {$success} 条，失败 {$failed} 条", [
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
    <div class="card-header">导入学生</div>
    <div class="card-body">
        <div class="alert alert-info">
            <p>请按照以下格式输入学生数据，每行一条记录：</p>
            <p><code>学号 密码 姓名 电话 指导员ID 类型</code></p>
            <p>说明：</p>
            <ul>
                <li>字段之间用制表符或逗号分隔</li>
                <li>学号、姓名为必填项</li>
                <li>密码若为空，则默认为学号</li>
                <li>指导员ID为可选项，若填写则必须是系统中已存在的指导员ID</li>
                <li>类型为可选项，可选值：student(学生)、instructor(指导员)、admin(系统管理员)，默认为student</li>
            </ul>
        </div>
        
        <form id="importForm">
            <div class="form-group">
                <label class="form-label">学生数据</label>
<textarea name="student_data" class="form-control" rows="15" placeholder="请输入学生数据，每行一条记录">
stu001	123456	张三	13800138001	1	student
stu002	123456	李四	13800138002	1	student
</textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" id="submitBtn" class="btn btn-primary">开始导入</button>
            </div>
        </form>
        
        <div id="result" class="mt-4" style="display: none;">
            <div class="card">
                <div class="card-header">导入结果</div>
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
            var formData = serializeForm('importForm');
            
            // 表单验证
            if (!formData.student_data) {
                showToast('请输入学生数据', 'warning');
                return;
            }
            
            // 确认导入
            if (!confirm('确定要导入这些学生数据吗？')) {
                return;
            }
            
            // 发送请求
            ajaxRequest('?do=inxs&act=import', formData, function(res) {
                if (res.code === 0) {
                    showToast(res.msg, 'success');
                    
                    // 显示结果
                    var resultDiv = document.getElementById('result');
                    var resultContent = document.getElementById('result-content');
                    
                    resultDiv.style.display = 'block';
                    
                    var html = '<p>导入结果：成功 ' + res.data.success + ' 条，失败 ' + res.data.failed + ' 条</p>';
                    
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
