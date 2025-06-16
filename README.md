# PHP7+MySQL5.6 爱补课的学生请假系统 V1.0Beta

## 简介

这是一个基于PHP7和MySQL5.6开发的学生请假管理系统，旨在提供一个简单、高效的平台，用于学生请假申请和审批管理。
系统支持三种用户角色：学生、指导员和系统管理员，分别具有不同的功能和权限。
这是首发Beta版本(测试版)，轻量级适合PHP入门学习，仅供学习参考。

## 环境要求

- PHP 7.1+
- MySQL 5.6+
- 支持mysqli扩展
- 推荐使用Apache或Nginx作为Web服务器
- 浏览器支持HTML5和CSS3

## 安装步骤

1. 将所有文件上传到Web服务器的根目录或子目录
2. 确保以下目录和文件可写：
   - /inc 目录
   - /uploads 目录（规划中功能用到）
3. 访问 install.php 进行安装
4. 安装过程中可以选择是否导入演示数据
5. 安装完成后，您可以使用默认管理员账号登录系统

## 默认账户和密码

### 管理员账号
- 账号：admin
- 密码：admin123

### 演示账号（详见安装结果显示）
- 指导员账号：teacher1
- 密码：123456
- 学生账号：stu001
- 密码：123456

## 文件结构

```
/qinjia20250520/
├── inc/                  # 共用文件夹
│   ├── conn.php          # 数据库连接和公共参数
│   ├── pubs.php          # 公共PHP函数
│   ├── js.js             # 公共JavaScript函数
│   ├── css.css           # 公共CSS样式
│   ├── head.php          # 公共头部
│   ├── foot.php          # 公共底部
│   ├── json.php          # 网站设置缓存
│   └── sqls.php          # 数据库CRUD类
├── sys/                  # 系统管理员功能模块
│   ├── login.php         # 管理员登录
│   ├── pass.php          # 密码修改
│   ├── site.php          # 系统设置
│   ├── lius.php          # 学生管理
│   ├── inxs.php          # 学生导入
│   └── liqj.php          # 请假记录管理
├── stu/                  # 学生功能模块
│   ├── login.php         # 学生登录
│   ├── pass.php          # 密码修改
│   ├── qjia.php          # 请假申请
│   └── list.php          # 请假记录查看
├── zhi/                  # 指导员功能模块
│   ├── login.php         # 指导员登录
│   ├── pass.php          # 密码修改
│   ├── qjia.php          # 请假审批
│   ├── stux.php          # 学生管理
│   └── bang.php          # 学生绑定
├── index.php             # 主入口
├── sys.php               # 系统管理员入口
├── stu.php               # 学生入口
├── zhi.php               # 指导员入口
├── install.php           # 安装脚本
└── readme.txt            # 说明文档
```

## 主要功能

### 系统管理员功能
- 登录/退出/修改密码
- 系统设置管理
- 学生账号管理（增加、编辑、删除）
- 批量导入学生数据
- 查看所有请假记录

### 学生功能
- 登录/退出/修改密码
- 提交请假申请
- 查看个人请假记录

### 指导员功能
- 登录/退出/修改密码
- 审批学生请假申请
- 管理绑定的学生
- 批量绑定学生

## 数据库结构

### 学生表(stux)
| 字段名 | 类型 | 说明 | 必填 |
|-------|------|------|------|
| id | int(11) | 自增主键 | 是 |
| student_id | varchar(32) | 学号(唯一) | 是 |
| password | varchar(255) | 密码(加密存储) | 是 |
| real_name | varchar(50) | 实名 | 是 |
| phone | varchar(20) | 电话 | 是 |
| instructor_id | int(11) | 指导员ID | 否 |
| type | enum | 类型：student、instructor、admin | 是 |
| status | tinyint(1) | 状态：0-禁用，1-启用 | 是 |
| remark | text | 备注 | 否 |
| create_time | datetime | 创建时间 | 是 |

### 请假表(qjia)
| 字段名 | 类型 | 说明 | 必填 |
|-------|------|------|------|
| id | int(11) | 自增主键 | 是 |
| student_id | varchar(32) | 学号 | 是 |
| real_name | varchar(50) | 实名 | 是 |
| start_time | datetime | 开始时间 | 是 |
| end_time | datetime | 结束时间 | 是 |
| reason | text | 事由 | 是 |
| submit_user | varchar(32) | 提交账号 | 是 |
| verify_user | varchar(32) | 核销账号 | 否 |
| verify_time | datetime | 核销时间 | 否 |
| status | tinyint(1) | 状态：0-待审核，1-已批准，2-已驳回 | 是 |
| create_time | datetime | 创建时间 | 是 |

## 使用注意事项

1. 首次使用请先运行安装程序
2. 管理员初始密码较为简单，请及时修改
3. 学生需要先被指导员绑定后才能提交请假申请
4. 可以使用批量导入功能快速添加学生信息
5. 学生默认密码为学号，建议首次登录后修改密码
6. 请假记录提交后，需要指导员审批才能生效

## 技术特点

1. 原生PHP开发，不依赖第三方框架
2. HTML5+CSS3+AJAX实现前端交互
3. 响应式设计，适合手机和PC访问
4. 分页功能支持较大数据量
5. AJAX异步通信提升用户体验
6. 安全过滤防止SQL注入和XSS攻击

## 问题反馈请联系：
- 电话/微信：15058593138
- 邮箱：15058593138@qq.com
