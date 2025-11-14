# Tanzanite Customer Service Plugin

客服管理插件 - 独立的轻量级插件，用于管理客服信息并提供 REST API。

## 功能特性

- ✅ 管理客服信息（姓名、邮箱、头像、状态、排序）
- ✅ 提供 REST API 端点获取启用的客服列表
- ✅ 简洁的管理界面
- ✅ 支持多个客服
- ✅ 支持启用/禁用状态
- ✅ 支持自定义排序

## 安装方法

### 方法 1: 直接上传到服务器

1. 将整个 `tanzanite-customer-service` 文件夹上传到服务器的 `/wp-content/plugins/` 目录
2. 在 WordPress 后台 → 插件 → 已安装的插件
3. 找到 "Tanzanite Customer Service" 并点击"启用"

### 方法 2: 打包成 ZIP 上传

1. 将 `tanzanite-customer-service` 文件夹压缩成 `tanzanite-customer-service.zip`
2. 在 WordPress 后台 → 插件 → 安装插件 → 上传插件
3. 选择 ZIP 文件并安装
4. 启用插件

## 使用方法

### 1. 管理客服

启用插件后，在 WordPress 后台左侧菜单会出现 "Customer Service" 菜单项（图标是商务人士）。

点击进入管理页面，可以：
- 添加新客服（点击 "Add Agent" 按钮）
- 编辑现有客服信息
- 删除客服（点击 "Remove" 按钮）
- 设置客服状态（Active/Inactive）
- 调整客服排序（Order 字段）

### 2. 使用 REST API

插件提供了一个公开的 REST API 端点：

```
GET https://tanzanite.site/wp-json/tanzanite/v1/customer-service/agents
```

**返回格式：**

```json
{
  "success": true,
  "data": [
    {
      "id": "1699999999",
      "name": "Customer Service",
      "email": "support@example.com",
      "avatar": "https://example.com/avatar.jpg"
    }
  ]
}
```

**特点：**
- 只返回状态为 "Active" 的客服
- 按 "Order" 字段升序排序
- 不需要认证，可以直接从前端调用

### 3. 在前端使用

在你的 Nuxt/Vue 应用中调用 API：

```javascript
// 获取客服列表
async function getCustomerServiceAgents() {
  const response = await fetch('https://tanzanite.site/wp-json/tanzanite/v1/customer-service/agents');
  const result = await response.json();
  
  if (result.success) {
    return result.data; // 客服列表
  }
  return [];
}
```

## 数据存储

客服数据存储在 WordPress 的 `wp_options` 表中，选项名为 `tz_customer_service_agents`。

## 系统要求

- WordPress 5.8+
- PHP 8.0+

## 技术栈

- PHP 8.0+ (使用了箭头函数、null 合并运算符等现代语法)
- WordPress REST API
- jQuery (用于管理页面的交互)

## 更新日志

### 1.0.0 (2024-11-12)
- 首次发布
- 基础的客服管理功能
- REST API 端点
