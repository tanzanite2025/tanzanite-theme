# WhatsApp 聊天商品搜索 - API 修复

## 🔧 问题

WhatsAppChatModal.vue 中的商品搜索功能使用了错误的 API 端点：
- ❌ **旧端点**：`/wp-json/mytheme-vue/v1/search`
- ✅ **新端点**：`/wp-json/tanzanite/v1/products`

## ✅ 解决方案

### **修改内容：**

将商品搜索改为使用 Tanzanite Setting 插件的商品 API。

---

## 📝 修改详情

### **1. API 端点变更**

**之前：**
```typescript
const response = await $fetch('/wp-json/mytheme-vue/v1/search', {
  params: {
    q: searchQuery.value,
    type: 'product'
  }
})
```

**之后：**
```typescript
const response = await $fetch('/wp-json/tanzanite/v1/products', {
  params: {
    keyword: searchQuery.value,
    per_page: 20,
    status: 'publish'
  }
})
```

---

### **2. 数据格式映射**

#### **Tanzanite API 返回格式：**

```json
{
  "items": [
    {
      "id": 123,
      "title": "商品名称",
      "status": "publish",
      "excerpt": "商品简介",
      "slug": "product-slug",
      "thumbnail": "https://example.com/image.jpg",
      "prices": {
        "regular": 100,
        "sale": 80,
        "member": 75
      },
      "stock": {
        "quantity": 50,
        "alert": 10
      },
      "points": {
        "reward": 10,
        "limit": 100
      },
      "categories": [...],
      "sticky": false,
      "updated_at": "2024-01-01 10:00:00",
      "created_at": "2024-01-01 09:00:00",
      "preview_url": "https://example.com/product/product-slug"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total_pages": 5,
    "total": 100
  }
}
```

#### **前端需要的格式：**

```typescript
{
  id: number
  title: string
  url: string
  thumbnail: string
  price: string
}
```

#### **数据转换代码：**

```typescript
searchResults.value = response.items.map((item: any) => ({
  id: item.id,
  title: item.title,
  url: item.preview_url || `/product/${item.slug || item.id}`,
  thumbnail: item.thumbnail,
  price: item.prices?.sale > 0 
    ? `$${item.prices.sale}` 
    : (item.prices?.regular > 0 ? `$${item.prices.regular}` : '')
}))
```

---

## 🎯 API 参数说明

### **Tanzanite Products API**

**端点：** `GET /wp-json/tanzanite/v1/products`

**参数：**

| 参数 | 类型 | 说明 | 默认值 |
|------|------|------|--------|
| `keyword` | string | 搜索关键词 | - |
| `per_page` | integer | 每页数量 | 20 |
| `page` | integer | 页码 | 1 |
| `status` | string | 商品状态 | any |
| `category` | integer | 分类 ID | 0 |
| `author` | integer | 作者 ID | 0 |
| `sort` | string | 排序字段 | updated_at |
| `order` | string | 排序方向 | DESC |

**状态值：**
- `publish` - 已发布
- `draft` - 草稿
- `pending` - 待审核
- `private` - 私密
- `any` - 所有状态

**排序字段：**
- `updated_at` - 更新时间
- `price_regular` - 常规价格
- `stock_qty` - 库存数量
- `points_reward` - 积分奖励

---

## 🔍 价格显示逻辑

```typescript
// 优先显示促销价，其次显示常规价
price: item.prices?.sale > 0 
  ? `$${item.prices.sale}` 
  : (item.prices?.regular > 0 ? `$${item.prices.regular}` : '')
```

**显示规则：**
1. 如果有促销价（`sale > 0`），显示促销价
2. 否则显示常规价（`regular > 0`）
3. 如果都没有，显示空字符串

---

## 📊 完整的搜索流程

```
用户输入搜索关键词
    ↓
点击"搜索"按钮
    ↓
调用 searchProducts()
    ↓
请求 /wp-json/tanzanite/v1/products
    ↓
参数: { keyword, per_page: 20, status: 'publish' }
    ↓
获取响应数据
    ↓
转换数据格式
    ↓
显示商品列表（2列网格）
    ↓
点击商品卡片
    ↓
分享到聊天（卡片消息）
```

---

## 🎨 UI 显示

### **商品卡片：**

```vue
<div class="border border-white/10 rounded-lg p-3 hover:bg-white/[0.05] cursor-pointer">
  <img :src="product.thumbnail" class="w-full h-32 object-cover rounded-lg mb-2" />
  <h4 class="text-white text-sm font-medium truncate">{{ product.title }}</h4>
  <p class="text-white/70 text-xs mt-1">{{ product.price }}</p>
</div>
```

### **空状态：**

- 搜索前：`搜索商品以分享到聊天`
- 搜索后无结果：`未找到商品`
- 搜索中：`搜索中...`

---

## ✅ 验证清单

- [x] API 端点改为 Tanzanite Setting 插件
- [x] 参数名称改为 `keyword`
- [x] 添加 `status: 'publish'` 过滤
- [x] 数据格式转换正确
- [x] 价格显示逻辑正确
- [x] URL 生成正确
- [x] 缩略图显示正确

---

## 🔗 相关文件

| 文件 | 说明 |
|------|------|
| `WhatsAppChatModal.vue` | 聊天窗口组件（已修改） |
| `class-rest-products-controller.php` | 商品 API 控制器 |
| `class-rest-controller.php` | REST API 基类 |

---

## 📚 API 文档位置

Tanzanite Setting 插件的 REST API 文档：
- 命名空间：`tanzanite/v1`
- 基础路径：`/wp-json/tanzanite/v1`
- 商品端点：`/products`

---

## 🎉 完成

现在 WhatsApp 聊天窗口的商品搜索功能已经正确对接到 Tanzanite Setting 插件的商品 API！

**测试步骤：**
1. 打开客服聊天窗口
2. 切换到"商品分享"标签
3. 输入商品关键词搜索
4. 查看搜索结果
5. 点击商品分享到聊天
6. 验证卡片消息显示正确
