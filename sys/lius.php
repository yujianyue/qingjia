<?php

// PHP爱补课请假系统 V1.0Beta
// 演示地址: http://qinjia.chalide.cn
// 更新关注: /weivote
// 文件路径: sys/lius.php
// 文件大小: 27616 字节
// 最后修改时间: 2025-06-12 18:31:19
// 作者: yujianyue
// 邮件: 15058593138@qq.com
// 版权所有,保留发行权和署名权
/**
 * 本文件功能: 管理员管理学生
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
$type = isset($_GET['type']) ? safe_input($_GET['type']) : '';
$status = isset($_GET['status']) ? intval($_GET['status']) : -1;
$keyword = isset($_GET['keyword']) ? safe_input($_GET['keyword']) : '';

// 构建查询条件
$where_sql = "1=1";

if (!empty($type)) {
    $where_sql .= " AND type = '" . $db->conn->real_escape_string($type) . "'";
}

if ($status >= 0) {
    $where_sql .= " AND status = " . $status;
}

if (!empty($keyword)) {
    $where_sql .= " AND (student_id LIKE '%" . $db->conn->real_escape_string($keyword) . "%' OR real_name LIKE '%" . $db->conn->real_escape_string($keyword) . "%' OR phone LIKE '%" . $db->conn->real_escape_string($keyword) . "%')";
}

// 获取总记录数
$sql = "SELECT COUNT(*) as total FROM stux WHERE " . $where_sql;
$total_result = $db->get_row($sql);
$total = $total_result ? $total_result['total'] : 0;

// 分页信息
$pagination = pagination($total, $page, $limit);

// 获取学生记录
$sql = "SELECT * FROM stux WHERE " . $where_sql . " ORDER BY id ASC LIMIT " . $pagination['start'] . ", " . $limit;
$students = $db->get_rows($sql);

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
            
            $student = $db->get_one('stux', ['id' => $id]);
            
            if (!$student) {
                json_result(1, '学生不存在');
            }
            
            // 获取指导员信息
            $instructor = null;
            if (!empty($student['instructor_id'])) {
                $instructor = $db->get_one('stux', ['id' => $student['instructor_id']]);
            }
            
            $data = [
                'student' => $student,
                'instructor' => $instructor
            ];
            
            json_result(0, '获取成功', $data);
            break;
            
        case 'add':
            // 添加学生
            $student_id = isset($_POST['student_id']) ? safe_input($_POST['student_id']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $real_name = isset($_POST['real_name']) ? safe_input($_POST['real_name']) : '';
            $phone = isset($_POST['phone']) ? safe_input($_POST['phone']) : '';
            $type = isset($_POST['type']) ? safe_input($_POST['type']) : 'student';
            $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            $remark = isset($_POST['remark']) ? safe_input($_POST['remark']) : '';
            
            if (empty($student_id) || empty($real_name)) {
                json_result(1, '学号和姓名不能为空');
            }
            
            // 检查学号是否已存在
            $exist = $db->get_one('stux', ['student_id' => $student_id]);
            if ($exist) {
                json_result(1, '学号已存在');
            }
            
            // 如果密码为空，设置为学号
            if (empty($password)) {
                $password = $student_id;
            }
            
            // 检查用户类型
            if (!in_array($type, ['student', 'instructor', 'admin'])) {
                json_result(1, '用户类型无效');
            }
            
            // 验证指导员ID
            if ($instructor_id) {
                $instructor = $db->get_one('stux', ['id' => $instructor_id, 'type' => 'instructor']);
                if (!$instructor) {
                    json_result(1, '指导员不存在');
                }
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
                'status' => $status,
                'remark' => $remark,
                'create_time' => date('Y-m-d H:i:s')
            ];
            
            $result = $db->insert('stux', $data);
            
            if ($result) {
                json_result(0, '添加成功');
            } else {
                json_result(1, '添加失败，请重试');
            }
            break;
            
        case 'edit':
            // 编辑学生
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $real_name = isset($_POST['real_name']) ? safe_input($_POST['real_name']) : '';
            $phone = isset($_POST['phone']) ? safe_input($_POST['phone']) : '';
            $instructor_id = isset($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
            $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
            $remark = isset($_POST['remark']) ? safe_input($_POST['remark']) : '';
            
            if ($id <= 0 || empty($real_name)) {
                json_result(1, 'ID和姓名不能为空');
            }
            
            // 检查学生是否存在
            $student = $db->get_one('stux', ['id' => $id]);
            if (!$student) {
                json_result(1, '学生不存在');
            }
            
            // 验证指导员ID
            if ($instructor_id) {
                $instructor = $db->get_one('stux', ['id' => $instructor_id, 'type' => 'instructor']);
                if (!$instructor) {
                    json_result(1, '指导员不存在');
                }
            }
            
            // 更新数据
            $data = [
                'real_name' => $real_name,
                'phone' => $phone,
                'instructor_id' => $instructor_id,
                'status' => $status,
                'remark' => $remark
            ];
            
            $result = $db->update('stux', $data, ['id' => $id]);
            
            if ($result) {
                json_result(0, '更新成功');
            } else {
                json_result(1, '更新失败，请重试');
            }
            break;
            
        case 'reset_password':
            // 重置密码
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            // 检查学生是否存在
            $student = $db->get_one('stux', ['id' => $id]);
            if (!$student) {
                json_result(1, '用户不存在');
            }
            
            // 将密码重置为学号
            $password_hash = password_hash($student['student_id'], PASSWORD_DEFAULT);
            
            $result = $db->update('stux', ['password' => $password_hash], ['id' => $id]);
            
            if ($result) {
                json_result(0, '密码已重置为学号');
            } else {
                json_result(1, '重置失败，请重试');
            }
            break;
            
        case 'delete':
            // 删除学生
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            
            if ($id <= 0) {
                json_result(1, '参数错误');
            }
            
            // 防止删除自己
            if ($id == $_SESSION['user_id']) {
                json_result(1, '不能删除当前登录用户');
            }
            
            $result = $db->delete('stux', ['id' => $id]);
            
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

// 获取指导员列表（用于下拉选择）
$instructors = $db->get_all('stux', ['type' => 'instructor'], 'id, real_name, student_id');
?>
<?php include './inc/head.php'; ?>

<div class="card">
    <div class="card-header">学生管理</div>
    <div class="card-body">
        <div class="table-header">
            <div class="table-title">用户列表</div>
            <div class="table-actions">
                <div class="table-search">
                    <select id="type-filter" class="form-control">
                        <option value="">全部类型</option>
                        <option value="student">学生</option>
                        <option value="instructor">指导员</option>
                        <option value="admin">管理员</option>
                    </select>
                    <select id="status-filter" class="form-control">
                        <option value="-1">全部状态</option>
                        <option value="1">启用</option>
                        <option value="0">禁用</option>
                    </select>
                    <input type="text" id="keyword" class="form-control" placeholder="学号/姓名/电话">
                    <button type="button" id="search-btn" class="btn btn-primary">搜索</button>
                    <button type="button" id="add-btn" class="btn btn-success">添加用户</button>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th width="100">学号</th>
                        <th width="100">姓名</th>
                        <th width="120">电话</th>
                        <th width="80">类型</th>
                        <th width="80">状态</th>
                        <th>备注</th>
                        <th width="199">操作</th>
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

<!-- 添加/编辑模态框模板 -->
<div id="form-modal-template" style="display: none;">
    <form id="user-form">
        <input type="hidden" name="id" value="">
        
        <div class="form-group student-id-group">
            <label class="form-label">学号</label>
            <input type="text" name="student_id" class="form-control" placeholder="学号">
        </div>
        
        <div class="form-group password-group">
            <label class="form-label">初始密码</label>
            <input type="password" name="password" class="form-control" placeholder="密码，留空则默认为学号">
        </div>
        
        <div class="form-group">
            <label class="form-label">姓名</label>
            <input type="text" name="real_name" class="form-control" placeholder="姓名">
        </div>
        
        <div class="form-group">
            <label class="form-label">电话</label>
            <input type="text" name="phone" class="form-control" placeholder="电话">
        </div>
        
        <div class="form-group">
            <label class="form-label">用户类型</label>
            <select name="type" class="form-control">
                <option value="student">学生</option>
                <option value="instructor">指导员</option>
                <option value="admin">管理员</option>
            </select>
        </div>
        
        <div class="form-group student-instructor-group">
            <label class="form-label">绑定指导员</label>
            <select name="instructor_id" class="form-control">
                <option value="">未绑定</option>
                <?php foreach ($instructors as $instructor): ?>
                <option value="<?php echo $instructor['id']; ?>"><?php echo $instructor['real_name'] . ' (' . $instructor['student_id'] . ')'; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">状态</label>
            <select name="status" class="form-control">
                <option value="1">启用</option>
                <option value="0">禁用</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">备注</label>
            <textarea name="remark" class="form-control" rows="3" placeholder="备注"></textarea>
        </div>
    </form>
</div>

<!-- 详情模态框模板 -->
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
        <label class="form-label">用户类型</label>
        <div class="detail-type"></div>
    </div>
    <div class="form-group detail-instructor-group" style="display: none;">
        <label class="form-label">指导员</label>
        <div class="detail-instructor"></div>
    </div>
    <div class="form-group">
        <label class="form-label">状态</label>
        <div class="detail-status"></div>
    </div>
    <div class="form-group">
        <label class="form-label">备注</label>
        <div class="detail-remark"></div>
    </div>
    <div class="form-group">
        <label class="form-label">创建时间</label>
        <div class="detail-create-time"></div>
    </div>
</div>

<script>
    // 类型文本映射
    var typeMap = {
        'student': '学生',
        'instructor': '指导员',
        'admin': '管理员'
    };
    
    // 状态文本映射
    var statusMap = {
        '0': '<span class="badge badge-danger">禁用</span>',
        '1': '<span class="badge badge-success">启用</span>'
    };
    
    // 加载列表数据
    function loadList(page) {
        page = page || 1;
        var type = document.getElementById('type-filter').value;
        var status = document.getElementById('status-filter').value;
        var keyword = document.getElementById('keyword').value;
        
        // 更新URL参数但不刷新页面
        var url = new URL(window.location.href);
        url.searchParams.set('page', page);
        url.searchParams.set('type', type);
        url.searchParams.set('status', status);
        if (keyword) url.searchParams.set('keyword', keyword);
        else url.searchParams.delete('keyword');
        window.history.pushState({}, '', url);
        
        // 发送请求
        var params = 'page=' + page + '&type=' + type + '&status=' + status;
        if (keyword) params += '&keyword=' + encodeURIComponent(keyword);
        
        ajaxRequest('?do=lius&act=list&' + params, {}, function(res) {
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
                var shortRemark = item.remark ? (item.remark.length > 20 ? item.remark.substring(0, 20) + '...' : item.remark) : '';
                
                html += '<tr>';
                html += '<td>' + item.id + '</td>';
                html += '<td>' + item.student_id + '</td>';
                html += '<td>' + item.real_name + '</td>';
                html += '<td>' + (item.phone || '') + '</td>';
                html += '<td>' + typeMap[item.type] + '</td>';
                html += '<td>' + statusMap[item.status] + '</td>';
                html += '<td>' + shortRemark + '</td>';
                html += '<td>';
                html += '<a href="javascript:void(0)" onclick="showDetail(' + item.id + ')" class="btn-link">详情</a>';
                html += ' | <a href="javascript:void(0)" onclick="editUser(' + item.id + ')" class="btn-link">编辑</a>';
                html += ' | <a href="javascript:void(0)" onclick="resetPassword(' + item.id + ')" class="btn-link">改密</a>';
                
                // 不能删除自己
                if (item.id != <?php echo $_SESSION['user_id']; ?>) {
                    html += ' | <a href="javascript:void(0)" onclick="deleteUser(' + item.id + ')" class="btn-link text-danger">删除</a>';
                }
                
                html += '</td>';
                html += '</tr>';
            }
        }
        
        document.getElementById('list-container').innerHTML = html;
    }
    
    // 显示详情
    function showDetail(id) {
        ajaxRequest('?do=lius&act=detail&id=' + id, {}, function(res) {
            if (res.code === 0) {
                var data = res.data;
                var student = data.student;
                var instructor = data.instructor;
                
                // 创建模态框
                var content = document.getElementById('detail-modal-template').innerHTML;
                var modal = showModal('用户详情', content, [
                    {
                        text: '编辑',
                        type: 'primary',
                        click: function(container, overlay) {
                            closeModal(container, overlay);
                            editUser(id);
                        }
                    },
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
                container.querySelector('.detail-student-id').textContent = student.student_id;
                container.querySelector('.detail-real-name').textContent = student.real_name;
                container.querySelector('.detail-phone').textContent = student.phone || '未填写';
                container.querySelector('.detail-type').textContent = typeMap[student.type];
                container.querySelector('.detail-status').innerHTML = statusMap[student.status];
                container.querySelector('.detail-remark').textContent = student.remark || '无';
                container.querySelector('.detail-create-time').textContent = student.create_time;
                
                // 指导员信息
                if (student.type === 'student' && instructor) {
                    container.querySelector('.detail-instructor-group').style.display = 'block';
                    container.querySelector('.detail-instructor').textContent = instructor.real_name + ' (' + instructor.student_id + ')';
                }
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 添加用户
    function addUser() {
        // 创建模态框
        var content = document.getElementById('form-modal-template').innerHTML;
        var modal = showModal('添加用户', content, [
            {
                text: '提交',
                type: 'primary',
                click: function(container, overlay) {
                    submitForm(container, 'add');
                }
            },
            {
                text: '取消',
                type: 'default',
                click: function(container, overlay) {
                    closeModal(container, overlay);
                }
            }
        ]);
        
        // 类型变更事件
        var typeSelect = modal.container.querySelector('select[name="type"]');
        typeSelect.addEventListener('change', function() {
            toggleFormByType(modal.container, this.value);
        });
        
        // 初始化表单
        toggleFormByType(modal.container, 'student');
    }
    
    // 编辑用户
    function editUser(id) {
        ajaxRequest('?do=lius&act=detail&id=' + id, {}, function(res) {
            if (res.code === 0) {
                var data = res.data;
                var student = data.student;
                
                // 创建模态框
                var content = document.getElementById('form-modal-template').innerHTML;
                var modal = showModal('编辑用户', content, [
                    {
                        text: '提交',
                        type: 'primary',
                        click: function(container, overlay) {
                            submitForm(container, 'edit');
                        }
                    },
                    {
                        text: '取消',
                        type: 'default',
                        click: function(container, overlay) {
                            closeModal(container, overlay);
                        }
                    }
                ]);
                
                // 隐藏学号和密码字段（编辑模式不能修改）
                modal.container.querySelector('.student-id-group').style.display = 'none';
                modal.container.querySelector('.password-group').style.display = 'none';
                
                // 填充表单数据
                var form = modal.container.querySelector('#user-form');
                form.id.value = student.id;
                form.real_name.value = student.real_name;
                form.phone.value = student.phone || '';
                form.type.value = student.type;
                form.status.value = student.status;
                form.remark.value = student.remark || '';
                
                if (student.instructor_id) {
                    form.instructor_id.value = student.instructor_id;
                }
                
                // 类型变更事件
                var typeSelect = modal.container.querySelector('select[name="type"]');
                typeSelect.addEventListener('change', function() {
                    toggleFormByType(modal.container, this.value);
                });
                
                // 根据类型初始化表单
                toggleFormByType(modal.container, student.type);
                
                // 如果编辑的是当前登录用户，禁用类型和状态字段
                if (student.id == <?php echo $_SESSION['user_id']; ?>) {
                    form.type.disabled = true;
                    form.status.disabled = true;
                }
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 根据用户类型切换表单显示
    function toggleFormByType(container, type) {
        var instructorGroup = container.querySelector('.student-instructor-group');
        
        if (type === 'student') {
            instructorGroup.style.display = 'block';
        } else {
            instructorGroup.style.display = 'none';
        }
    }
    
    // 提交表单
    function submitForm(container, action) {
        var form = container.querySelector('#user-form');
        var formData = new FormData(form);
        var data = {};
        
        for (var pair of formData.entries()) {
            data[pair[0]] = pair[1];
        }
        
        // 表单验证
        if (action === 'add' && !data.student_id) {
            showToast('请输入学号', 'warning');
            return;
        }
        
        if (!data.real_name) {
            showToast('请输入姓名', 'warning');
            return;
        }
        
        // 发送请求
        ajaxRequest('?do=lius&act=' + action, data, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
                closeModal(container.parentNode, container.parentNode.parentNode);
                loadList(); // 重新加载列表
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 重置密码
    function resetPassword(id) {
        if (!confirm('确定要将该用户的密码重置为学号吗？')) {
            return;
        }
        
        ajaxRequest('?do=lius&act=reset_password', {id: id}, function(res) {
            if (res.code === 0) {
                showToast(res.msg, 'success');
            } else {
                showToast(res.msg, 'error');
            }
        });
    }
    
    // 删除用户
    function deleteUser(id) {
        if (!confirm('确定要删除该用户吗？此操作不可恢复！')) {
            return;
        }
        
        ajaxRequest('?do=lius&act=delete', {id: id}, function(res) {
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
        document.getElementById('type-filter').value = '<?php echo $type; ?>';
        document.getElementById('status-filter').value = '<?php echo $status; ?>';
        document.getElementById('keyword').value = '<?php echo $keyword; ?>';
        
        // 初始加载列表
        loadList(<?php echo $page; ?>);
        
        // 搜索按钮点击事件
        document.getElementById('search-btn').addEventListener('click', function() {
            loadList(1);
        });
        
        // 添加按钮点击事件
        document.getElementById('add-btn').addEventListener('click', function() {
            addUser();
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
