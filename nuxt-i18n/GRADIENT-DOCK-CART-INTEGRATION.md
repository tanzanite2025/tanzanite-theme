# GradientDockMenu 购物车集成

## 🎯 更新说明

已将 GradientDockMenu 底部中心的购物车按钮集成到新的购物车弹窗系统。

---

## 📝 主要改动

### **1. 按钮功能变更**

**之前：**
- 点击按钮跳转到 `/cart` 页面
- 使用 WooCommerce Store API 获取数据

**现在：**
- 点击按钮打开购物车抽屉弹窗
- 使用本地购物车状态管理
- 实时显示购物车数据

---

### **2. 数据来源变更**

| 数据项 | 之前 | 现在 |
|--------|------|------|
| **商品数量** | `summary.value?.items_count` | `cartCount.value` |
| **总重量** | `summary.value?.items_weight` | 计算自 `cartItems` |
| **总价** | `summary.value?.totals?.total_price` | `total.value` |

---

### **3. 代码变更**

#### **按钮点击事件：**

```vue
<!-- 之前 -->
<button @click="goToCart">

<!-- 现在 -->
<button @click="openCartDrawer">
```

#### **数据计算：**

```typescript
// 之前
const itemsCount = computed(() => summary.value?.items_count ?? 0)
const weightDisplay = computed(() => {
  const weight = summary.value?.items_weight ?? 0
  return `${weight} g`
})
const priceDisplay = computed(() => {
  const total = summary.value?.totals?.total_price ?? 0
  const symbol = summary.value?.totals?.currency_symbol ?? '$'
  return `${symbol}${total.toFixed(2)}`
})

// 现在
const { cartCount, cartItems, total, openCart, formatPrice } = useCart()

const itemsCount = computed(() => cartCount.value)

const weightDisplay = computed(() => {
  const weight = cartItems.value.reduce((sum: number, item: any) => {
    return sum + (item.weight || 0) * item.quantity
  }, 0)
  return `${weight.toFixed(1)} g`
})

const priceDisplay = computed(() => {
  return formatPrice(total.value)
})
```

#### **点击处理：**

```typescript
// 之前
const goToCart = () => {
  window.location.href = cartUrl.value
}

// 现在
const openCartDrawer = () => {
  openCart()
  if (typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('ui:popup-open', { detail: { id: 'cart-drawer' } }))
  }
}
```

---

## 🎨 UI 显示

### **购物车按钮布局：**

```
┌─────────────────────────────────┐
│  🛒 3  •  ⚖️ 1.5g  •  💰 $150  │
└─────────────────────────────────┘
```

- **左侧** - 购物车图标 + 商品数量
- **中间** - 重量图标 + 总重量
- **右侧** - 价格图标 + 总价

---

## 🔄 实时更新

购物车按钮会实时响应购物车状态变化：

1. **添加商品** - 数量和价格立即更新
2. **修改数量** - 重量和价格立即更新
3. **删除商品** - 所有数据立即更新
4. **清空购物车** - 显示为 0

---

## 📦 商品重量

### **添加商品时包含重量：**

```typescript
const { addToCart } = useCart()

addToCart({
  id: 123,
  title: '商品名称',
  slug: 'product-slug',
  price: 99.99,
  quantity: 1,
  thumbnail: 'https://example.com/image.jpg',
  weight: 500, // 重量（克）
})
```

### **重量计算：**

```typescript
总重量 = Σ(商品重量 × 数量)
```

**示例：**
- 商品 A: 500g × 2 = 1000g
- 商品 B: 300g × 1 = 300g
- **总重量: 1300g = 1.3kg**

---

## 🎯 用户体验提升

### **之前的问题：**
- ❌ 点击按钮需要页面跳转
- ❌ 加载时间长
- ❌ 用户离开当前页面
- ❌ SEO 不友好（独立购物车页面）

### **现在的优势：**
- ✅ 即时打开购物车抽屉
- ✅ 无需页面跳转
- ✅ 用户保持在当前页面
- ✅ SEO 友好（无独立页面）
- ✅ 流畅的动画效果
- ✅ 实时数据更新

---

## 🔧 技术细节

### **事件系统：**

打开购物车时会触发全局事件：

```typescript
window.dispatchEvent(new CustomEvent('ui:popup-open', { 
  detail: { id: 'cart-drawer' } 
}))
```

这确保了：
1. 其他弹窗会自动关闭
2. 避免多个弹窗同时打开
3. 统一的弹窗管理

### **状态管理：**

购物车状态使用 `useCart()` composable 管理：

```typescript
const {
  cartCount,      // 商品总数
  cartItems,      // 商品列表
  total,          // 总价
  openCart,       // 打开购物车
  formatPrice,    // 格式化价格
} = useCart()
```

---

## 📱 响应式设计

购物车按钮在不同屏幕尺寸下的表现：

| 屏幕尺寸 | 按钮宽度 | 按钮高度 | 字体大小 |
|---------|---------|---------|---------|
| **移动端** | 200px | 48px | 13px |
| **平板** | 200px | 52px | 13px |
| **桌面** | 200px | 52px | 13px |

---

## 🚀 使用示例

### **在商品页面添加到购物车：**

```vue
<template>
  <button @click="handleAddToCart">
    加入购物车
  </button>
</template>

<script setup>
const { addToCart } = useCart()

const product = {
  id: 123,
  title: '商品名称',
  slug: 'product-slug',
  price: 99.99,
  thumbnail: 'https://example.com/image.jpg',
  weight: 500, // 重量（克）
  maxStock: 10,
}

const handleAddToCart = () => {
  const result = addToCart(product)
  
  if (result.success) {
    // 添加成功，GradientDockMenu 会自动更新显示
    console.log('商品已添加到购物车')
  } else {
    alert(result.message)
  }
}
</script>
```

---

## ✅ 完成清单

- [x] 修改按钮点击事件（跳转 → 打开弹窗）
- [x] 集成 `useCart()` composable
- [x] 实时显示商品数量
- [x] 实时计算总重量
- [x] 实时显示总价
- [x] 添加 weight 字段到 CartItem 接口
- [x] 触发全局弹窗事件
- [x] 保持现有 UI 样式
- [x] 保持响应式设计

---

## 🎉 总结

GradientDockMenu 的购物车按钮现在完全集成到新的购物车系统中：

1. **点击按钮** → 打开购物车抽屉
2. **实时数据** → 显示真实的购物车状态
3. **无需跳转** → 用户体验更流畅
4. **SEO 友好** → 无独立购物车页面

**购物车系统现在完全统一！** 🛒✨
