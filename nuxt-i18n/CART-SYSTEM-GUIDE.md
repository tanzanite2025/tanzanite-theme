# 购物车系统使用指南

## 📦 系统概述

购物车和结账系统已实现为**全局弹窗组件**，避免创建独立页面，对 SEO 更友好。

---

## 🎯 核心特性

### ✅ SEO 优势
- 无独立页面，不会被 Google 索引
- 不浪费爬取配额
- 用户体验更流畅

### ✅ 功能特性
- 🛒 购物车抽屉（右侧滑出）
- 💳 结账弹窗（居中显示）
- 📦 实时库存检查
- 💰 自动计算运费和税费
- 💾 LocalStorage 持久化
- 📱 移动端友好

---

## 📁 文件结构

```
app/
├── composables/
│   └── useCart.ts              # 购物车状态管理
├── components/
│   ├── CartDrawer.vue          # 购物车抽屉
│   └── CheckoutModal.vue       # 结账弹窗
└── app.vue                     # 全局包含组件
```

---

## 🚀 使用方法

### 1. 在商品页面添加"加入购物车"按钮

```vue
<template>
  <div class="product-page">
    <h1>{{ product.title }}</h1>
    <p>{{ formatPrice(product.price) }}</p>
    
    <!-- 加入购物车按钮 -->
    <button
      @click="handleAddToCart"
      class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
    >
      🛒 加入购物车
    </button>
  </div>
</template>

<script setup lang="ts">
const { addToCart, openCart } = useCart()

const product = {
  id: 123,
  title: '商品名称',
  slug: 'product-slug',
  price: 99.99,
  thumbnail: 'https://example.com/image.jpg',
  maxStock: 10,
}

const handleAddToCart = () => {
  const result = addToCart(product)
  
  if (result.success) {
    // 添加成功，打开购物车
    openCart()
  } else {
    // 库存不足
    alert(result.message)
  }
}
</script>
```

### 2. 在导航栏显示购物车图标

```vue
<template>
  <header class="site-header">
    <nav>
      <!-- 购物车按钮 -->
      <button
        @click="openCart"
        class="relative p-2 hover:bg-gray-100 rounded-lg"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
        
        <!-- 购物车数量徽章 -->
        <span
          v-if="cartCount > 0"
          class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
        >
          {{ cartCount }}
        </span>
      </button>
    </nav>
  </header>
</template>

<script setup lang="ts">
const { openCart, cartCount } = useCart()
</script>
```

### 3. 在 GradientDockMenu 中添加购物车按钮

```vue
<template>
  <div class="dock-menu">
    <!-- 其他按钮 -->
    
    <!-- 购物车按钮 -->
    <button
      @click="openCart"
      class="dock-item"
      aria-label="购物车"
    >
      <div class="relative">
        <ShoppingCartIcon class="w-6 h-6" />
        <span
          v-if="cartCount > 0"
          class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
        >
          {{ cartCount }}
        </span>
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
const { openCart, cartCount } = useCart()
</script>
```

---

## 🎨 组件 API

### `useCart()` Composable

#### 状态
```typescript
const {
  cartItems,              // 购物车商品列表
  isCartOpen,             // 购物车是否打开
  isCheckoutOpen,         // 结账是否打开
  cartCount,              // 购物车商品总数
  subtotal,               // 小计
  shipping,               // 运费
  tax,                    // 税费
  total,                  // 总计
} = useCart()
```

#### 方法
```typescript
// 添加到购物车
addToCart(product: {
  id: number
  title: string
  slug: string
  price: number
  thumbnail?: string
  sku?: string
  maxStock?: number
})

// 更新数量
updateQuantity(id: number, quantity: number)

// 增加/减少数量
incrementQuantity(id: number)
decrementQuantity(id: number)

// 移除商品
removeFromCart(id: number)

// 清空购物车
clearCart()

// 打开/关闭购物车
openCart()
closeCart()
toggleCart()

// 打开/关闭结账
openCheckout()
closeCheckout()
backToCart()

// 格式化价格
formatPrice(price: number) // 返回 "$99.99"
```

---

## 💡 高级用法

### 1. 自定义运费计算

编辑 `composables/useCart.ts`:

```typescript
const shipping = computed(() => {
  // 满100免运费
  if (subtotal.value >= 100) return 0
  
  // 根据重量计算
  const totalWeight = cartItems.value.reduce((sum, item) => {
    return sum + (item.weight || 0) * item.quantity
  }, 0)
  
  if (totalWeight < 1) return 5
  if (totalWeight < 5) return 10
  return 15
})
```

### 2. 自定义税费计算

```typescript
const tax = computed(() => {
  // 根据地区计算不同税率
  const taxRate = shippingAddress.value?.state === 'CA' ? 0.0725 : 0.05
  return (subtotal.value + shipping.value) * taxRate
})
```

### 3. 集成后端 API

在 `CheckoutModal.vue` 的 `handleSubmit` 方法中：

```typescript
const handleSubmit = async () => {
  try {
    const response = await $fetch('/wp-json/tanzanite/v1/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: {
        items: cartItems.value.map(item => ({
          product_id: item.id,
          quantity: item.quantity,
          price: item.price,
        })),
        shipping_address: form.value,
        payment_method: form.value.paymentMethod,
        notes: form.value.notes,
        subtotal: subtotal.value,
        shipping: shipping.value,
        tax: tax.value,
        total: total.value,
      }
    })
    
    if (response.success) {
      clearCart()
      closeCheckout()
      // 跳转到订单详情页
      navigateTo(`/orders/${response.order_id}`)
    }
  } catch (error) {
    console.error('Order failed:', error)
  }
}
```

---

## 🎯 SEO 最佳实践

### 1. 阻止旧页面被索引（如果有）

在 `public/robots.txt`:

```
User-agent: *
Disallow: /cart
Disallow: /checkout
```

### 2. 商品页面添加结构化数据

```vue
<script setup>
useHead({
  script: [
    {
      type: 'application/ld+json',
      children: JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.title,
        offers: {
          '@type': 'Offer',
          price: product.price,
          priceCurrency: 'USD',
          availability: 'https://schema.org/InStock',
          url: `https://example.com/products/${product.slug}`
        }
      })
    }
  ]
})
</script>
```

---

## 📱 移动端优化

购物车和结账组件已针对移动端优化：

- ✅ 购物车：全屏抽屉（右侧滑出）
- ✅ 结账：全屏弹窗（居中显示）
- ✅ 触摸友好的按钮尺寸
- ✅ 响应式布局
- ✅ 流畅的动画效果

---

## 🔧 自定义样式

所有组件使用 Tailwind CSS，可以轻松自定义：

```vue
<!-- 修改购物车宽度 -->
<div class="max-w-md">  <!-- 改为 max-w-lg 或 max-w-xl -->

<!-- 修改主题色 -->
<button class="bg-blue-500">  <!-- 改为 bg-purple-500 -->

<!-- 修改圆角 -->
<div class="rounded-lg">  <!-- 改为 rounded-xl 或 rounded-2xl -->
```

---

## 🎉 完成！

现在你的网站已经拥有了一个完整的购物车系统，无需独立页面，对 SEO 友好！

**测试步骤：**
1. 在商品页面点击"加入购物车"
2. 查看右侧滑出的购物车抽屉
3. 点击"去结账"打开结账弹窗
4. 填写收货信息和选择支付方式
5. 提交订单

**需要帮助？**
- 查看 `composables/useCart.ts` 了解状态管理
- 查看 `components/CartDrawer.vue` 了解购物车 UI
- 查看 `components/CheckoutModal.vue` 了解结账流程
