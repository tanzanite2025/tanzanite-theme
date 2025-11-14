# 聊天后端 API 完成报告

## ✅ 已完成的工作

### **1. 数据库表结构** ✅

创建了两个数据库表：

#### **会话表 (`tanz_chat_conversations`)**

```sql
CREATE TABLE wp_tanz_chat_conversations (
    id VARCHAR(50) NOT NULL,              -- 会话 ID
    customer_id BIGINT UNSIGNED NOT NULL, -- 客户 ID
    agent_id BIGINT UNSIGNED NOT NULL,    -- 客服 ID
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- 状态：active/closed/pending
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY customer_id (customer_id),
    KEY agent_id (agent_id),
    KEY status (status),
    KEY updated_at (updated_at)
);
```

#### **消息表 (`tanz_chat_messages`)**

```sql
CREATE TABLE wp_tanz_chat_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id VARCHAR(50) NOT NULL,  -- 会话 ID
    sender_id BIGINT UNSIGNED NOT NULL,    -- 发送者 ID
    sender_type VARCHAR(20) NOT NULL,      -- 发送者类型：agent/customer
    message TEXT NOT NULL,                 -- 消息内容
    type VARCHAR(20) NOT NULL DEFAULT 'text', -- 消息类型：text/image/file
    attachment_url VARCHAR(500) NULL,      -- 附件 URL
    is_read TINYINT(1) NOT NULL DEFAULT 0, -- 是否已读
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY conversation_id (conversation_id),
    KEY sender_id (sender_id),
    KEY is_read (is_read),
    KEY created_at (created_at)
);
```

---

### **2. REST API 控制器** ✅

创建了 `Tanzanite_REST_Chat_Controller` 类，提供以下 API 端点：

#### **API 端点列表：**

| 端点 | 方法 | 说明 | 权限 |
|------|------|------|------|
| `/tanzanite/v1/chat/conversations` | GET | 获取会话列表 | 需登录 |
| `/tanzanite/v1/chat/messages/{conversation_id}` | GET | 获取消息列表 | 需登录 |
| `/tanzanite/v1/chat/send` | POST | 发送消息 | 需登录 |
| `/tanzanite/v1/chat/mark-read/{conversation_id}` | POST | 标记已读 | 需登录 |
| `/tanzanite/v1/chat/status` | GET | 获取在线状态 | 需登录 |
| `/tanzanite/v1/chat/upload` | POST | 上传文件 | 需登录 |
| `/tanzanite/v1/chat/unread-count` | GET | 获取未读消息数 | 需登录 |

---

## 📋 API 详细说明

### **1. 获取会话列表**

```
GET /wp-json/tanzanite/v1/chat/conversations
```

**参数：**
- `page` (integer) - 页码，默认 1
- `per_page` (integer) - 每页数量，默认 20，最大 100
- `status` (string) - 状态筛选：active/closed/pending

**返回示例：**
```json
{
  "items": [
    {
      "id": "conv-001",
      "customer_id": 123,
      "customer_name": "张三",
      "customer_avatar": "https://...",
      "customer_phone": "+86 138 xxxx xxxx",
      "agent_id": 456,
      "status": "active",
      "last_message": "你好，我想咨询...",
      "last_message_time": "2024-01-01 14:20:00",
      "unread_count": 2,
      "online": true,
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 14:20:00"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 50,
    "total_pages": 3
  }
}
```

---

### **2. 获取消息列表**

```
GET /wp-json/tanzanite/v1/chat/messages/{conversation_id}
```

**参数：**
- `page` (integer) - 页码，默认 1
- `per_page` (integer) - 每页数量，默认 50，最大 200

**返回示例：**
```json
{
  "items": [
    {
      "id": 1,
      "conversation_id": "conv-001",
      "sender_id": 123,
      "sender_name": "张三",
      "sender_type": "customer",
      "message": "你好，我想咨询订单问题",
      "type": "text",
      "attachment_url": null,
      "is_read": true,
      "created_at": "2024-01-01 14:20:00"
    },
    {
      "id": 2,
      "conversation_id": "conv-001",
      "sender_id": 456,
      "sender_name": "客服小王",
      "sender_type": "agent",
      "message": "您好，请问订单号是多少？",
      "type": "text",
      "attachment_url": null,
      "is_read": true,
      "created_at": "2024-01-01 14:21:00"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 100,
    "has_more": true
  }
}
```

---

### **3. 发送消息**

```
POST /wp-json/tanzanite/v1/chat/send
```

**请求体：**
```json
{
  "conversation_id": "conv-001",
  "message": "您好，我来帮您查询",
  "type": "text",
  "attachment_url": null
}
```

**返回示例：**
```json
{
  "message": {
    "id": 3,
    "conversation_id": "conv-001",
    "sender_id": 456,
    "sender_name": "客服小王",
    "sender_type": "agent",
    "message": "您好，我来帮您查询",
    "type": "text",
    "attachment_url": null,
    "created_at": "2024-01-01 14:22:00"
  }
}
```

---

### **4. 标记已读**

```
POST /wp-json/tanzanite/v1/chat/mark-read/{conversation_id}
```

**返回示例：**
```json
{
  "success": true,
  "unread_count": 0
}
```

---

### **5. 获取在线状态**

```
GET /wp-json/tanzanite/v1/chat/status?conversation_ids=conv-001,conv-002
```

**返回示例：**
```json
{
  "statuses": [
    {
      "conversation_id": "conv-001",
      "customer_id": 123,
      "online": true,
      "last_seen": 1704096000
    },
    {
      "conversation_id": "conv-002",
      "customer_id": 124,
      "online": false,
      "last_seen": 1704092400
    }
  ]
}
```

---

### **6. 上传文件**

```
POST /wp-json/tanzanite/v1/chat/upload
```

**请求：** `multipart/form-data`
- `file` - 文件

**返回示例：**
```json
{
  "success": true,
  "url": "https://example.com/uploads/2024/01/image.jpg",
  "type": "image/jpeg",
  "size": 102400
}
```

---

### **7. 获取未读消息数**

```
GET /wp-json/tanzanite/v1/chat/unread-count
```

**返回示例：**
```json
{
  "count": 5
}
```

---

## 🔐 权限说明

所有 API 端点都需要用户登录（`is_user_logged_in`）。

### **会话权限：**
- 客服只能查看分配给自己的会话
- 客户只能查看自己的会话
- 发送消息时自动判断发送者类型（agent/customer）

### **在线状态判断：**
- 用户 5 分钟内有活动视为在线
- 使用 `last_activity` user meta 存储最后活动时间

---

## 📁 文件位置

| 文件 | 路径 | 说明 |
|------|------|------|
| API 控制器 | `includes/rest-api/class-rest-chat-controller.php` | 聊天 API 实现 |
| 数据库安装 | `includes/legacy-pages.php` (line 878-914) | 数据库表创建 |
| 控制器注册 | `includes/legacy-pages.php` (line 976) | 注册到 REST API |

---

## 🔄 数据库安装

数据库表会在插件激活或更新时自动创建。如果需要手动触发：

1. 进入 WordPress 后台
2. 停用 Tanzanite Setting 插件
3. 重新启用插件

或者在 WordPress 数据库中手动执行 SQL（见上方表结构）。

---

## ⏭️ 下一步：App 端对接

现在后端 API 已经完成，接下来需要：

1. ✅ 修改 App 的 API 基础 URL
2. ✅ 创建 API 服务层（`api.ts`）
3. ✅ 修改 `ChatList.tsx` 对接会话列表
4. ✅ 修改 `Chat.tsx` 对接消息 API
5. ✅ 实现文件上传功能
6. ✅ 添加登录功能

详细步骤请参考：`CHAT-APP-INTEGRATION-PLAN.md`

---

## 🎉 总结

**后端聊天 API 已完成！**

✅ 数据库表创建完成
✅ REST API 控制器实现完成
✅ 7 个 API 端点全部可用
✅ 权限控制完善
✅ 在线状态检测
✅ 文件上传支持

现在可以开始对接 App 端了！🚀
