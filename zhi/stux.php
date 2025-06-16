<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: zhi/stux.php
// 文件大小: 10071 字节
// 最后修改时间: 2025-06-12 18:37:39
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 指导员管理学生
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
$keyword = isset($_GET['keyword']) ? safe_input($_GET['keyword']) : '';

// 构建查询条件
$where = ['instructor_id' => $_SESSION['user_id'], 'type' => 'student'];

if (!empty($keyword)) {
    $where_sql = "instructor_id = " . $_SESSION['user_id'] . " AND type = 'student' AND (student_id LIKE '%" . $db->conn->real_escape_string($keyword) . "%' OR real_name LIKE '%" . $db->conn->real_escape_string($keyword) . "%' OR phone LIKE '%" . $db->conn->real_escape_string($keyword) . "%')";
    
    // 获取总记录数
    $sql = "SELECT COUNT(*) as total FROM stux WHERE " . $where_sql;
    $total_result = $db->get_row($sql);
    $total = $total_result ? $total_result['total'] : 0;
    
    // 分页信息
    $pagination = pagination($total, $page, $limit);
    
    // 获取学生记录
    $sql = "SELECT * FROM stux WHERE " . $where_sql . " ORDER BY id ASC LIMIT " . $pagination['start'] . ", " . $limit;
    $students = $db->get_rows($sql);
} else {
    // 获取总记录数
    $total = $db->count('stux', $where);
    
    // 分页信息
    $pagination = pagination($total, $page, $limit);
    
    // 获取学生记录
    $order = 'id ASC';
    $students = $db->get_all('stux', $where, '*', $order, $limit, $pagination['start']);
}

// AJAX请求处理
if (isset($_GET['act'])) {
    $act = $_GET['act'];
    
    switch ($act) {
        case 'list':
            // 返回学生列表
            $data = [
                'list' => $students,
                'pagination' => $pagination
            ];
            
            json_result(0, '获取成功', $data);
            break;
            
        case 'detail':
            // 获取学生详情
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            $student = $db->get_one('stux', ['id' => $id, 'instructor_id' => $_SESSION['user_id']]);
            
            if (!$student) {
                json_result(1, '学生不存在或不属于您');
            }
            
            json_result(0, '获取成功', $student);
            break;
            
        case 'unbind':
            // 解绑学生
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            $student = $db->get_one('stux', ['id' => $id, 'instructor_id' => $_SESSION['user_id']]);
            
            if (!$student) {
                json_result(1, '学生不存在或不属于您');
            }
            
            $result = $db->update('stux', ['instructor_id' => null], ['id' => $id]);
            
            if ($result) {
                json_result(0, '解绑成功');
            } else {
                json_result(1, '解绑失败，请重试');
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
    <div class="card-header">我的学生</div>
    <div class="card-body">
        <div class="table-header">
            <div class="table-title">学生列表</div>
            <div class="table-actions">
                <div class="table-search">
                    <input type="text" id="keyword" class="form-control" placeholder="学号/姓名/电话">
                    <button type="button" id="search-btn" class="btn btn-primary">搜索</button>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="120">学号</th>
                        <th width="100">姓名</th>
                        <th width="140">电话</th>
                        <th>备注</th>
                        <th width="118">操作</th>
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
        <label class="form-label">学号</label>
        <div class="detail-student-id"></div>
    </div>
    <div class="form-group">
        <label class="form-label">姓名</label>
        <div class="detail-real-name"></div>
    </div>
    <div class="form-group">
        <label class="form-label">电话</label>
        <div class="detail-phone"></div>
    </div>
    <div class="form-group">
        <label class="form-label">备注</label>
        <div class="detail-remark"></div>
    </div>
</div>

<script>
    // 加载列表数据
    function loadList(page) {
        page = page || 1;
        var keyword = document.getElementById('keyword').value;
        
        // 更新URL参数但不刷新页面
        var url = new URL(window.location.href);
        url.searchParams.set('page', page);
        if (keyword) url.searchParams.set('keyword', keyword);
        else url.searchParams.delete('keyword');
        window.history.pushState({}, '', url);
        
        // 发送请求
        ajaxRequest('?do=stux&act=list&page=' + page + '&keyword=' + encodeURIComponent(keyword), {}, function(res) {
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
                var shortRemark = item.remark ? (item.remark.length > 20 ? item.remark.substring(0, 20) + '...' : item.remark) : '';
                
                html += '<tr>';
                html += '<td>' + item.id + '</td>';
                html += '<td>' + item.student_id + '</td>';
                html += '<td>' + item.real_name + '</td>';
                html += '<td>' + item.phone + '</td>';
                html += '<td>' + shortRemark + '</td>';
                html += '<td>';
                html += '<a href="javascript:void(0)" onclick="showDetail(' + item.id + ')" class="btn-link">详情</a>';
                html += ' | <a href="javascript:void(0)" onclick="unbindStudent(' + item.id + ')" class="btn-link text-danger">解绑</a>';
                html += '</td>';
                html += '</tr>';
            }
        }
        
        document.getElementById('list-container').innerHTML = html;
    }
    
    // 显示详情
    function showDetail(id) {
        ajaxRequest('?do=stux&act=detail&id=' + id, {}, function(res) {
            if (res.code === 0) {
                var data = res.data;
                
                // 创建模态框
                var content = document.getElementById('detail-modal-template').innerHTML;
                var modal = showModal('学生详情', content, [
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
                container.querySelector('.detail-student-id').textContent = data.student_id;
                container.querySelector('.detail-real-name').textContent = data.real_name;
                container.querySelector('.detail-phone').textContent = data.phone;
                container.querySelector('.detail-remark').textContent = data.remark || '无';
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 解绑学生
    function unbindStudent(id) {
        if (!confirm('确定要解绑这名学生吗？解绑后学生将无法提交请假申请。')) {
            return;
        }
        
        ajaxRequest('?do=stux&act=unbind', {id: id}, function(res) {
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
