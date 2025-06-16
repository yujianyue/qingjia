<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: stu/list.php
// 文件大小: 11610 字节
// 最后修改时间: 2025-05-20 22:50:58
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 学生请假记录列表
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
$keyword = isset($_GET['keyword']) ? safe_input($_GET['keyword']) : '';

// 构建查询条件
$where = ['student_id' => $_SESSION['student_id']];

if ($status >= 0) {
    $where['status'] = $status;
}

// 获取总记录数
$total = $db->count('qjia', $where);

// 分页信息
$pagination = pagination($total, $page, $limit);

// 获取请假记录
$order = 'create_time DESC';
$records = $db->get_all('qjia', $where, '*', $order, $limit, $pagination['start']);

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
            
            $record = $db->get_one('qjia', ['id' => $id, 'student_id' => $_SESSION['student_id']]);
            
            if (!$record) {
                json_result(1, '记录不存在');
            }
            
            json_result(0, '获取成功', $record);
            break;
            
        case 'cancel':
            // 取消请假申请（仅限待审核状态）
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            $record = $db->get_one('qjia', ['id' => $id, 'student_id' => $_SESSION['student_id']]);
            
            if (!$record) {
                json_result(1, '记录不存在');
            }
            
            if ($record['status'] != 0) {
                json_result(1, '只能取消待审核的请假申请');
            }
            
            $result = $db->delete('qjia', ['id' => $id]);
            
            if ($result) {
                json_result(0, '取消成功');
            } else {
                json_result(1, '取消失败，请重试');
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
    <div class="card-header">请假记录</div>
    <div class="card-body">
        <div class="table-header">
            <div class="table-title">我的请假记录</div>
            <div class="table-actions">
                <div class="table-search">
                    <select id="status-filter" class="form-control">
                        <option value="-1">全部状态</option>
                        <option value="0">待审核</option>
                        <option value="1">已批准</option>
                        <option value="2">已驳回</option>
                    </select>
                    <input type="text" id="keyword" class="form-control" placeholder="请输入关键词">
                    <button type="button" id="search-btn" class="btn btn-primary">搜索</button>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="140">开始时间</th>
                        <th width="140">结束时间</th>
                        <th>事由</th>
                        <th width="100">状态</th>
                        <th width="100">操作</th>
                    </tr>
                </thead>
                <tbody id="list-container">
                    <tr>
                        <td colspan="6" class="table-empty">加载中...</td>
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
        <label class="form-label">申请人</label>
        <div class="detail-real-name"></div>
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
        var keyword = document.getElementById('keyword').value;
        
        // 更新URL参数但不刷新页面
        var url = new URL(window.location.href);
        url.searchParams.set('page', page);
        url.searchParams.set('status', status);
        if (keyword) url.searchParams.set('keyword', keyword);
        else url.searchParams.delete('keyword');
        window.history.pushState({}, '', url);
        
        // 发送请求
        ajaxRequest('?do=list&act=list&page=' + page + '&status=' + status + '&keyword=' + encodeURIComponent(keyword), {}, function(res) {
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
            html = '<tr><td colspan="6" class="table-empty">暂无数据</td></tr>';
        } else {
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                var shortReason = item.reason.length > 20 ? item.reason.substring(0, 20) + '...' : item.reason;
                
                html += '<tr>';
                html += '<td>' + item.id + '</td>';
                html += '<td>' + item.start_time + '</td>';
                html += '<td>' + item.end_time + '</td>';
                html += '<td>' + shortReason + '</td>';
                html += '<td>' + statusMap[item.status] + '</td>';
                html += '<td>';
                html += '<a href="javascript:void(0)" onclick="showDetail(' + item.id + ')" class="btn-link">详情</a>';
                if (item.status == '0') {
                    html += ' | <a href="javascript:void(0)" onclick="cancelLeave(' + item.id + ')" class="btn-link text-danger">取消</a>';
                }
                html += '</td>';
                html += '</tr>';
            }
        }
        
        document.getElementById('list-container').innerHTML = html;
    }
    
    // 显示详情
    function showDetail(id) {
        ajaxRequest('?do=list&act=detail&id=' + id, {}, function(res) {
            if (res.code === 0) {
                var data = res.data;
                
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
                container.querySelector('.detail-real-name').textContent = data.real_name;
                container.querySelector('.detail-time').textContent = data.start_time + ' 至 ' + data.end_time;
                container.querySelector('.detail-reason').textContent = data.reason;
                container.querySelector('.detail-create-time').textContent = data.create_time;
                container.querySelector('.detail-status').innerHTML = statusMap[data.status];
                
                // 审批信息
                if (data.status != '0' && data.verify_user) {
                    container.querySelectorAll('.verify-info').forEach(function(el) {
                        el.style.display = 'block';
                    });
                    container.querySelector('.detail-verify-user').textContent = data.verify_user;
                    container.querySelector('.detail-verify-time').textContent = data.verify_time || '未记录';
                }
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 取消请假
    function cancelLeave(id) {
        if (!confirm('确定要取消这条请假申请吗？')) {
            return;
        }
        
        ajaxRequest('?do=list&act=cancel', {id: id}, function(res) {
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
        // 初始加载列表
        loadList(<?php echo $page; ?>);
        
        // 状态筛选
        document.getElementById('status-filter').value = '<?php echo $status; ?>';
        document.getElementById('status-filter').addEventListener('change', function() {
            loadList(1);
        });
        
        // 关键词搜索
        document.getElementById('keyword').value = '<?php echo $keyword; ?>';
        document.getElementById('search-btn').addEventListener('click', function() {
            loadList(1);
        });
        
        // 回车搜索
        document.getElementById('keyword').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadList(1);
            }
        });
    });
</script>

<?php include './inc/foot.php'; ?>
