/**
 * 本文件功能: 公共JavaScript函数
 * 版权声明: 保留发行权和署名权
 * 作者信息: 功能反馈:15058593138@qq.com(手机号同微信)
 */

// 获取URL参数
function getUrlParam(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if (r != null) return decodeURI(r[2]); return null;
}

// AJAX请求函数
function ajaxRequest(url, data, callback, method) {
    method = method || 'POST';
    
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    showToast('解析响应失败: ' + e.message);
                    return;
                }
                callback(response);
            } else {
                showToast('请求失败: ' + xhr.status);
            }
        }
    };
    
    var params = '';
    if (typeof data === 'object') {
        var arr = [];
        for (var key in data) {
            arr.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
        }
        params = arr.join('&');
    } else {
        params = data;
    }
    
    xhr.send(params);
}

// 表单序列化函数
function serializeForm(formId) {
    var form = document.getElementById(formId);
    if (!form) return {};
    
    var data = {};
    var elements = form.elements;
    
    for (var i = 0; i < elements.length; i++) {
        var element = elements[i];
        var name = element.name;
        
        if (!name) continue;
        
        var type = element.type;
        
        if (type === 'checkbox' || type === 'radio') {
            if (element.checked) {
                data[name] = element.value;
            }
        } else if (type !== 'button' && type !== 'reset' && type !== 'submit') {
            data[name] = element.value;
        }
    }
    
    return data;
}

// 显示提示消息
function showToast(message, type, duration) {
    type = type || 'info'; // info, success, warning, error
    duration = duration || 3000;
    
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.className += ' toast-show';
    }, 10);
    
    setTimeout(function() {
        toast.className = toast.className.replace(' toast-show', ' toast-hide');
        
        setTimeout(function() {
            document.body.removeChild(toast);
        }, 500);
    }, duration);
}

// 分页函数
function renderPagination(container, total, currentPage, pageSize, callback) {
    var totalPages = Math.ceil(total / pageSize);
    if (totalPages <= 1) return;
    
    var paginationHTML = '<div class="pagination">';
    
    // 首页
    paginationHTML += '<a href="javascript:void(0)" class="pagination-btn' + (currentPage === 1 ? ' disabled' : '') + '" data-page="1">首页</a>';
    
    // 上一页
    paginationHTML += '<a href="javascript:void(0)" class="pagination-btn' + (currentPage === 1 ? ' disabled' : '') + '" data-page="' + (currentPage - 1) + '">上一页</a>';
    
    // 页码选择
    paginationHTML += '<select class="pagination-select">';
    for (var i = 1; i <= totalPages; i++) {
        paginationHTML += '<option value="' + i + '"' + (i === currentPage ? ' selected' : '') + '>' + i + '</option>';
    }
    paginationHTML += '</select>';
    
    // 下一页
    paginationHTML += '<a href="javascript:void(0)" class="pagination-btn' + (currentPage === totalPages ? ' disabled' : '') + '" data-page="' + (currentPage + 1) + '">下一页</a>';
    
    // 末页
    paginationHTML += '<a href="javascript:void(0)" class="pagination-btn' + (currentPage === totalPages ? ' disabled' : '') + '" data-page="' + totalPages + '">末页</a>';
    
    paginationHTML += '</div>';
    
    container.innerHTML = paginationHTML;
    
    // 绑定分页事件
    var btns = container.querySelectorAll('.pagination-btn');
    for (var j = 0; j < btns.length; j++) {
        btns[j].addEventListener('click', function() {
            if (this.classList.contains('disabled')) return;
            
            var page = parseInt(this.getAttribute('data-page'));
            callback(page);
        });
    }
    
    var select = container.querySelector('.pagination-select');
    select.addEventListener('change', function() {
        var page = parseInt(this.value);
        callback(page);
    });
}

// 遮罩层函数
function showModal(title, content, buttons) {
    // 创建遮罩层
    var modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    
    // 创建遮罩内容
    var modalContainer = document.createElement('div');
    modalContainer.className = 'modal-container';
    
    // 创建标题
    var modalHeader = document.createElement('div');
    modalHeader.className = 'modal-header';
    modalHeader.innerHTML = '<h3>' + title + '</h3><a href="javascript:void(0)" class="modal-close">&times;</a>';
    
    // 创建内容
    var modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    modalContent.innerHTML = content;
    
    // 创建按钮
    var modalFooter = document.createElement('div');
    modalFooter.className = 'modal-footer';
    
    if (buttons && buttons.length > 0) {
        for (var i = 0; i < buttons.length; i++) {
            var btn = document.createElement('button');
            btn.className = 'btn btn-' + (buttons[i].type || 'default');
            btn.textContent = buttons[i].text;
            
            if (buttons[i].click) {
                (function(callback) {
                    btn.addEventListener('click', function() {
                        callback(modalContainer, modalOverlay);
                    });
                })(buttons[i].click);
            }
            
            modalFooter.appendChild(btn);
        }
    }
    
    // 组装遮罩层
    modalContainer.appendChild(modalHeader);
    modalContainer.appendChild(modalContent);
    modalContainer.appendChild(modalFooter);
    modalOverlay.appendChild(modalContainer);
    
    // 添加到页面
    document.body.appendChild(modalOverlay);
    
    // 绑定关闭事件
    var closeBtn = modalHeader.querySelector('.modal-close');
    closeBtn.addEventListener('click', function() {
        closeModal(modalContainer, modalOverlay);
    });
    
    // 返回遮罩层对象
    return {
        container: modalContainer,
        overlay: modalOverlay,
        close: function() {
            closeModal(modalContainer, modalOverlay);
        }
    };
}

// 关闭遮罩层
function closeModal(container, overlay) {
    container.classList.add('modal-closing');
    
    setTimeout(function() {
        document.body.removeChild(overlay);
    }, 300);
}

// 表格批量选择函数
function toggleCheckAll(checkAllId, checkClassName) {
    var checkAll = document.getElementById(checkAllId);
    var checkboxes = document.getElementsByClassName(checkClassName);
    
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = checkAll.checked;
    }
}

// 获取选中的复选框值
function getCheckedValues(checkClassName) {
    var checkboxes = document.getElementsByClassName(checkClassName);
    var values = [];
    
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            values.push(checkboxes[i].value);
        }
    }
    
    return values;
}

// 图片压缩上传
function compressAndUploadImage(file, maxWidth, url, callback) {
    maxWidth = maxWidth || 1280;
    
    // 检查文件类型
    if (!file.type.match(/image.*/)) {
        showToast('请选择图片文件', 'error');
        return;
    }
    
    var reader = new FileReader();
    
    reader.onload = function(e) {
        var img = new Image();
        img.src = e.target.result;
        
        img.onload = function() {
            var width = img.width;
            var height = img.height;
            
            // 如果图片宽度小于最大宽度，不进行压缩
            if (width <= maxWidth) {
                uploadImage(file, url, callback);
                return;
            }
            
            // 计算压缩后的尺寸
            var ratio = maxWidth / width;
            var newWidth = maxWidth;
            var newHeight = height * ratio;
            
            // 创建画布进行压缩
            var canvas = document.createElement('canvas');
            canvas.width = newWidth;
            canvas.height = newHeight;
            
            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, newWidth, newHeight);
            
            // 转换为Blob
            canvas.toBlob(function(blob) {
                // 创建新文件名
                var newFile = new File([blob], file.name, {
                    type: file.type,
                    lastModified: Date.now()
                });
                
                // 上传压缩后的图片
                uploadImage(newFile, url, callback);
            }, file.type);
        };
    };
    
    reader.readAsDataURL(file);
}

// 上传图片
function uploadImage(file, url, callback) {
    var formData = new FormData();
    formData.append('file', file);
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    showToast('解析响应失败: ' + e.message, 'error');
                    return;
                }
                callback(response);
            } else {
                showToast('上传失败: ' + xhr.status, 'error');
            }
        }
    };
    
    xhr.send(formData);
}

// 日期格式化
function formatDate(date, format) {
    format = format || 'YYYY-MM-DD';
    
    if (!(date instanceof Date)) {
        date = new Date(date);
    }
    
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    var hours = date.getHours();
    var minutes = date.getMinutes();
    var seconds = date.getSeconds();
    
    // 补零
    month = month < 10 ? '0' + month : month;
    day = day < 10 ? '0' + day : day;
    hours = hours < 10 ? '0' + hours : hours;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
}
