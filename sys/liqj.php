<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: sys/liqj.php
// 文件大小: 18829 字节
// 最后修改时间: 2025-06-12 18:30:07
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 管理员查看请假记录
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 连接数据库
$db = new Database();

// 获取当前页码
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page);
$limit = 10; // 每页显示10条

// 获取查询条件
$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
$student_id = isset($_GET['student_id']) ? safe_input($_GET['student_id']) : '';
$keyword = isset($_GET['keyword']) ? safe_input($_GET['keyword']) : '';
$start_date = isset($_GET['start_date']) ? safe_input($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? safe_input($_GET['end_date']) : '';

// 构建查询条件
$where_sql = "1=1";

if ($status >= 0) {
    $where_sql .= " AND status = " . $status;
}

if (!empty($student_id)) {
    $where_sql .= " AND student_id LIKE '%" . $db->conn->real_escape_string($student_id) . "%'";
}

if (!empty($keyword)) {
    $where_sql .= " AND (real_name LIKE '%" . $db->conn->real_escape_string($keyword) . "%' OR reason LIKE '%" . $db->conn->real_escape_string($keyword) . "%')";
}

if (!empty($start_date)) {
    $where_sql .= " AND start_time >= '" . $start_date . " 00:00:00'";
}

if (!empty($end_date)) {
    $where_sql .= " AND end_time <= '" . $end_date . " 23:59:59'";
}

// 获取总记录数
$sql = "SELECT COUNT(*) as total FROM qjia WHERE " . $where_sql;
$total_result = $db->get_row($sql);
$total = $total_result ? $total_result['total'] : 0;

// 分页信息
$pagination = pagination($total, $page, $limit);

// 获取请假记录
$sql = "SELECT * FROM qjia WHERE " . $where_sql . " ORDER BY create_time DESC LIMIT " . $pagination['start'] . ", " . $limit;
$records = $db->get_rows($sql);

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'list':
            // 返回请假记录列表
            $data = [
                'list' => $records,
                'pagination' => $pagination
            ];
            
            json_result(0, '获取成功', $data);
            break;
            
        case 'detail':
            // 获取请假记录详情
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            $record = $db->get_one('qjia', ['id' => $id]);
            
            if (!$record) {
                json_result(1, '记录不存在');
            }
            
            // 获取学生信息
            $student = $db->get_one('stux', ['student_id' => $record['student_id']]);
            
            // 获取指导员信息
            $instructor = null;
            if ($student && !empty($student['instructor_id'])) {
                $instructor = $db->get_one('stux', ['id' => $student['instructor_id']]);
            }
            
            $data = [
                'record' => $record,
                'student' => $student,
                'instructor' => $instructor
            ];
            
            json_result(0, '获取成功', $data);
            break;
            
        case 'approve':
            // 批准请假申请
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            $record = $db->get_one('qjia', ['id' => $id]);
            
            if (!$record) {
                json_result(1, '记录不存在');
            }
            
            if ($record['status'] != 0) {
                json_result(1, '只能处理待审核的请假申请');
            }
            
            $result = $db->update('qjia', [
                'status' => 1, // 已批准
                'verify_user' => $_SESSION['student_id'],
                'verify_time' => date('Y-m-d H:i:s')
            ], ['id' => $id]);
            
            if ($result) {
                json_result(0, '批准成功');
            } else {
                json_result(1, '批准失败，请重试');
            }
            break;
            
        case 'reject':
            // 驳回请假申请
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            $record = $db->get_one('qjia', ['id' => $id]);
            
            if (!$record) {
                json_result(1, '记录不存在');
            }
            
            if ($record['status'] != 0) {
                json_result(1, '只能处理待审核的请假申请');
            }
            
            $result = $db->update('qjia', [
                'status' => 2, // 已驳回
                'verify_user' => $_SESSION['student_id'],
                'verify_time' => date('Y-m-d H:i:s')
            ], ['id' => $id]);
            
            if ($result) {
                json_result(0, '驳回成功');
            } else {
                json_result(1, '驳回失败，请重试');
            }
            break;
            
        case 'delete':
            // 删除请假记录
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            $result = $db->delete('qjia', ['id' => $id]);
            
            if ($result) {
                json_result(0, '删除成功');
            } else {
                json_result(1, '删除失败，请重试');
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
    <div class="card-header">请假记录管理</div>
    <div class="card-body">
        <div class="table-header">
            <div class="table-title">请假记录列表</div>
            <div class="table-actions">
                <div class="table-search">
                    <select id="status-filter" class="form-control">
                        <option value="-1">全部状态</option>
                        <option value="0">待审核</option>
                        <option value="1">已批准</option>
                        <option value="2">已驳回</option>
                    </select>
                    <input type="text" id="student-id" class="form-control" placeholder="学号">
                    <input type="text" id="keyword" class="form-control" placeholder="姓名/事由">
                    <input type="date" id="start-date" class="form-control" placeholder="开始日期">
                    <input type="date" id="end-date" class="form-control" placeholder="结束日期">
                    <button type="button" id="search-btn" class="btn btn-primary">搜索</button>
                    <button type="button" id="reset-btn" class="btn btn-default">重置</button>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="100">学号</th>
                        <th width="80">姓名</th>
                        <th width="140">开始时间</th>
                        <th width="140">结束时间</th>
                        <th>事由</th>
                        <th width="108">状态</th>
                        <th width="160">操作</th>
                    </tr>
                </thead>
                <tbody id="list-container">
                    <tr>
                        <td colspan="8" class="table-empty">加载中...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div id="pagination" class="pagination"></div>
    </div>
</div>

<!-- 详情模态框 -->
<div id="detail-modal-template" style="display: none;">
    <div class="form-group">
        <label class="form-label">学号</label>
        <div class="detail-student-id"></div>
    </div>
    <div class="form-group">
        <label class="form-label">姓名</label>
        <div class="detail-real-name"></div>
    </div>
    <div class="form-group">
        <label class="form-label">指导员</label>
        <div class="detail-instructor"></div>
    </div>
    <div class="form-group">
        <label class="form-label">请假时间</label>
        <div class="detail-time"></div>
    </div>
    <div class="form-group">
        <label class="form-label">请假事由</label>
        <div class="detail-reason"></div>
    </div>
    <div class="form-group">
        <label class="form-label">提交时间</label>
        <div class="detail-create-time"></div>
    </div>
    <div class="form-group">
        <label class="form-label">状态</label>
        <div class="detail-status"></div>
    </div>
    <div class="form-group verify-info" style="display: none;">
        <label class="form-label">审批人</label>
        <div class="detail-verify-user"></div>
    </div>
    <div class="form-group verify-info" style="display: none;">
        <label class="form-label">审批时间</label>
        <div class="detail-verify-time"></div>
    </div>
</div>

<script>
    // 状态文本映射
    var statusMap = {
        '0': '<span class="badge badge-info">待审核</span>',
        '1': '<span class="badge badge-success">已批准</span>',
        '2': '<span class="badge badge-danger">已驳回</span>'
    };
    
    // 加载列表数据
    function loadList(page) {
        page = page || 1;
        var status = document.getElementById('status-filter').value;
        var student_id = document.getElementById('student-id').value;
        var keyword = document.getElementById('keyword').value;
        var start_date = document.getElementById('start-date').value;
        var end_date = document.getElementById('end-date').value;
        
        // 更新URL参数但不刷新页面
        var url = new URL(window.location.href);
        url.searchParams.set('page', page);
        url.searchParams.set('status', status);
        if (student_id) url.searchParams.set('student_id', student_id);
        else url.searchParams.delete('student_id');
        if (keyword) url.searchParams.set('keyword', keyword);
        else url.searchParams.delete('keyword');
        if (start_date) url.searchParams.set('start_date', start_date);
        else url.searchParams.delete('start_date');
        if (end_date) url.searchParams.set('end_date', end_date);
        else url.searchParams.delete('end_date');
        window.history.pushState({}, '', url);
        
        // 发送请求
        var params = 'page=' + page + '&status=' + status;
        if (student_id) params += '&student_id=' + encodeURIComponent(student_id);
        if (keyword) params += '&keyword=' + encodeURIComponent(keyword);
        if (start_date) params += '&start_date=' + encodeURIComponent(start_date);
        if (end_date) params += '&end_date=' + encodeURIComponent(end_date);
        
        ajaxRequest('?do=liqj&act=list&' + params, {}, function(res) {
            if (res.code === 0) {
                renderList(res.data.list);
                renderPagination(document.getElementById('pagination'), res.data.pagination.total, res.data.pagination.page, res.data.pagination.size, function(page) {
                    loadList(page);
                });
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 渲染列表
    function renderList(list) {
        var html = '';
        
        if (list.length === 0) {
            html = '<tr><td colspan="8" class="table-empty">暂无数据</td></tr>';
        } else {
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                var shortReason = item.reason.length > 20 ? item.reason.substring(0, 20) + '...' : item.reason;
                
                html += '<tr>';
                html += '<td>' + item.id + '</td>';
                html += '<td>' + item.student_id + '</td>';
                html += '<td>' + item.real_name + '</td>';
                html += '<td>' + item.start_time + '</td>';
                html += '<td>' + item.end_time + '</td>';
                html += '<td>' + shortReason + '</td>';
                html += '<td>' + statusMap[item.status] + '</td>';
                html += '<td>';
                html += '<a href="javascript:void(0)" onclick="showDetail(' + item.id + ')" class="btn-link">详情</a>';
                if (item.status == '0') {
                    html += ' | <a href="javascript:void(0)" onclick="approveLeave(' + item.id + ')" class="btn-link text-success">批准</a>';
                    html += ' | <a href="javascript:void(0)" onclick="rejectLeave(' + item.id + ')" class="btn-link text-danger">驳回</a>';
                }
                html += ' | <a href="javascript:void(0)" onclick="deleteLeave(' + item.id + ')" class="btn-link text-danger">删除</a>';
                html += '</td>';
                html += '</tr>';
            }
        }
        
        document.getElementById('list-container').innerHTML = html;
    }
    
    // 显示详情
    function showDetail(id) {
        ajaxRequest('?do=liqj&act=detail&id=' + id, {}, function(res) {
            if (res.code === 0) {
                var data = res.data;
                var record = data.record;
                var student = data.student;
                var instructor = data.instructor;
                
                // 创建模态框
                var content = document.getElementById('detail-modal-template').innerHTML;
                var modal = showModal('请假详情', content, [
                    {
                        text: '关闭',
                        type: 'default',
                        click: function(container, overlay) {
                            closeModal(container, overlay);
                        }
                    }
                ]);
                
                // 填充数据
                var container = modal.container;
                container.querySelector('.detail-student-id').textContent = record.student_id;
                container.querySelector('.detail-real-name').textContent = record.real_name;
                container.querySelector('.detail-instructor').textContent = instructor ? instructor.real_name : '未绑定';
                container.querySelector('.detail-time').textContent = record.start_time + ' 至 ' + record.end_time;
                container.querySelector('.detail-reason').textContent = record.reason;
                container.querySelector('.detail-create-time').textContent = record.create_time;
                container.querySelector('.detail-status').innerHTML = statusMap[record.status];
                
                // 审批信息
                if (record.status != '0' && record.verify_user) {
                    container.querySelectorAll('.verify-info').forEach(function(el) {
                        el.style.display = 'block';
                    });
                    container.querySelector('.detail-verify-user').textContent = record.verify_user;
                    container.querySelector('.detail-verify-time').textContent = record.verify_time || '未记录';
                }
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 批准请假
    function approveLeave(id) {
        if (!confirm('确定要批准这条请假申请吗？')) {
            return;
        }
        
        ajaxRequest('?do=liqj&act=approve', {id: id}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadList(); // 重新加载列表
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 驳回请假
    function rejectLeave(id) {
        if (!confirm('确定要驳回这条请假申请吗？')) {
            return;
        }
        
        ajaxRequest('?do=liqj&act=reject', {id: id}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadList(); // 重新加载列表
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 删除请假记录
    function deleteLeave(id) {
        if (!confirm('确定要删除这条请假记录吗？此操作不可恢复！')) {
            return;
        }
        
        ajaxRequest('?do=liqj&act=delete', {id: id}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                loadList(); // 重新加载列表
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 页面加载完成后执行
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化表单值
        document.getElementById('status-filter').value = '<?php echo $status; ?>';
        document.getElementById('student-id').value = '<?php echo $student_id; ?>';
        document.getElementById('keyword').value = '<?php echo $keyword; ?>';
        document.getElementById('start-date').value = '<?php echo $start_date; ?>';
        document.getElementById('end-date').value = '<?php echo $end_date; ?>';
        
        // 初始加载列表
        loadList(<?php echo $page; ?>);
        
        // 搜索按钮点击事件
        document.getElementById('search-btn').addEventListener('click', function() {
            loadList(1);
        });
        
        // 重置按钮点击事件
        document.getElementById('reset-btn').addEventListener('click', function() {
            document.getElementById('status-filter').value = '-1';
            document.getElementById('student-id').value = '';
            document.getElementById('keyword').value = '';
            document.getElementById('start-date').value = '';
            document.getElementById('end-date').value = '';
            loadList(1);
        });
        
        // 回车搜索
        document.getElementById('keyword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadList(1);
            }
        });
        
        document.getElementById('student-id').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadList(1);
            }
        });
    });
</script>

<?php include './inc/foot.php'; ?>
