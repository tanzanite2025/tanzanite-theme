# 高级购物车系统 - 完整集成指南

## 🎯 系统概述

购物车系统已完全集成 Tanzanite Setting 插件的所有配置，包括：

- ✅ **运费模板** - 从后端读取运费配置
- ✅ **税率管理** - 从后端读取税率配置
- ✅ **会员等级折扣** - 根据用户积分自动计算折扣
- ✅ **积分抵扣** - 支持使用积分抵扣订单金额
- ✅ **优惠券系统** - 支持多种类型的优惠券
- ✅ **礼品卡** - 支持礼品卡支付

---

## 📊 计算逻辑

### **完整的价格计算流程：**

```
1. 商品小计 = Σ(商品价格 × 数量)
2. 会员折扣 = 商品小计 × 会员等级折扣率
3. 优惠券折扣 = 根据优惠券类型计算
4. 积分抵扣 = 使用积分数 × 0.01（最多抵扣50%）
5. 折扣后小计 = 商品小计 - 会员折扣 - 优惠券折扣 - 积分抵扣
6. 运费 = 根据运费模板计算
7. 税费 = (折扣后小计 + 运费) × 税率
8. 最终总计 = 折扣后小计 + 运费 + 税费
```

---

## 🏆 会员等级系统

### **会员等级配置：**

| 等级 | 积分范围 | 折扣 |
|------|---------|------|
| **Ordinary** | 0 - 499 | 0% |
| **Bronze** | 500 - 1,999 | 5% |
| **Silver** | 2,000 - 4,999 | 10% |
| **Gold** | 5,000 - 9,999 | 15% |
| **Platinum** | 10,000+ | 20% |

### **自动计算：**

系统会自动根据用户的总积分判断会员等级，并在结账时应用相应的折扣。

---

## 🚚 运费计算

### **运费模板类型：**

1. **按重量** (`weight`) - 根据商品总重量计算
2. **按数量** (`quantity`) - 根据商品总数量计算
3. **按金额** (`amount`) - 根据订单金额计算
4. **按体积** (`volume`) - 根据商品总体积计算
5. **按件数** (`items`) - 根据商品种类数计算

### **示例配置：**

```json
{
  "id": 1,
  "name": "标准运费",
  "type": "weight",
  "base_fee": 10,
  "free_threshold": 100,
  "rules": [
    { "min": 0, "max": 1, "fee": 5 },
    { "min": 1, "max": 5, "fee": 10 },
    { "min": 5, "max": 999, "fee": 15 }
  ]
}
```

### **API 端点：**

```
GET /wp-json/tanzanite/v1/shipping-templates
```

---

## 💰 税率计算

### **税率配置：**

税率可以根据地区自动选择，支持多个税率叠加。

### **示例配置：**

```json
{
  "id": 1,
  "name": "California Sales Tax",
  "rate": 7.25,
  "region": "CA",
  "is_active": true
}
```

### **API 端点：**

```
GET /wp-json/tanzanite/v1/tax-rates
```

### **自动选择：**

```typescript
// 根据收货地址自动选择税率
calculation.shippingAddress.value = { region: 'CA' }
calculation.autoSelectTaxRates()
```

---

## 🎁 积分抵扣

### **规则：**

- 1 积分 = $0.01
- 最多抵扣订单金额的 50%
- 仅使用可用积分（不包括冻结积分）

### **使用方法：**

```typescript
const { calculation } = useCart()

// 启用积分抵扣
calculation.usePointsDiscount.value = true

// 设置使用的积分数量
calculation.setPointsUsage(1000) // 使用 1000 积分 = $10

// 获取抵扣金额
const discount = calculation.calculatePointsDiscount(subtotal)
```

### **API 端点：**

```
GET /wp-json/tanzanite/v1/loyalty/points
```

**响应示例：**

```json
{
  "total": 5000,
  "available": 4500,
  "tier": "gold"
}
```

---

## 🎫 优惠券系统

### **优惠券类型：**

1. **百分比折扣** (`percentage`) - 按百分比减免
2. **固定金额** (`fixed`) - 固定减免金额
3. **积分券** (`points`) - 使用积分值抵扣

### **应用优惠券：**

```typescript
const { calculation } = useCart()

// 应用优惠券
const result = await calculation.applyCoupon('SUMMER2024')

if (result.success) {
  console.log('优惠券应用成功')
} else {
  console.log('优惠券无效:', result.message)
}

// 移除优惠券
calculation.removeCoupon()
```

### **API 端点：**

```
POST /wp-json/tanzanite/v1/coupons/validate
Body: { "code": "SUMMER2024" }
```

**响应示例：**

```json
{
  "code": "SUMMER2024",
  "type": "percentage",
  "value": 20,
  "min_amount": 50
}
```

---

## 🛠️ 完整使用示例

### **1. 初始化购物车系统**

```vue
<script setup lang="ts">
const { calculation, priceBreakdown } = useCart()

// 页面加载时初始化
onMounted(async () => {
  await calculation.initialize()
})
</script>
```

### **2. 显示完整价格明细**

```vue
<template>
  <div class="price-breakdown">
    <!-- 商品小计 -->
    <div class="line-item">
      <span>商品小计</span>
      <span>{{ formatPrice(priceBreakdown.subtotal) }}</span>
    </div>

    <!-- 会员折扣 -->
    <div v-if="priceBreakdown.memberDiscount > 0" class="line-item discount">
      <span>
        会员折扣 ({{ priceBreakdown.memberTier.name }} -{{ priceBreakdown.memberTier.discount }}%)
      </span>
      <span>-{{ formatPrice(priceBreakdown.memberDiscount) }}</span>
    </div>

    <!-- 优惠券折扣 -->
    <div v-if="priceBreakdown.couponDiscount > 0" class="line-item discount">
      <span>优惠券</span>
      <span>-{{ formatPrice(priceBreakdown.couponDiscount) }}</span>
    </div>

    <!-- 积分抵扣 -->
    <div v-if="priceBreakdown.pointsDiscount > 0" class="line-item discount">
      <span>积分抵扣</span>
      <span>-{{ formatPrice(priceBreakdown.pointsDiscount) }}</span>
    </div>

    <!-- 运费 -->
    <div class="line-item">
      <span>运费</span>
      <span>{{ priceBreakdown.shipping === 0 ? '免运费' : formatPrice(priceBreakdown.shipping) }}</span>
    </div>

    <!-- 税费 -->
    <div class="line-item">
      <span>税费</span>
      <span>{{ formatPrice(priceBreakdown.tax) }}</span>
    </div>

    <!-- 总计 -->
    <div class="line-item total">
      <span>应付总额</span>
      <span>{{ formatPrice(priceBreakdown.total) }}</span>
    </div>
  </div>
</template>
```

### **3. 选择运费模板**

```vue
<template>
  <div class="shipping-templates">
    <h3>选择配送方式</h3>
    <div
      v-for="template in calculation.shippingTemplates.value"
      :key="template.id"
      @click="calculation.selectedShippingTemplate.value = template.id"
      class="template-option"
      :class="{ active: calculation.selectedShippingTemplate.value === template.id }"
    >
      <span>{{ template.name }}</span>
      <span>{{ template.base_fee === 0 ? '免运费' : formatPrice(template.base_fee) }}</span>
    </div>
  </div>
</template>
```

### **4. 积分抵扣控制**

```vue
<template>
  <div v-if="calculation.userPoints.value" class="points-section">
    <label>
      <input
        v-model="calculation.usePointsDiscount.value"
        type="checkbox"
      />
      使用积分抵扣（可用: {{ calculation.userPoints.value.available }} 积分）
    </label>

    <div v-if="calculation.usePointsDiscount.value">
      <input
        :value="calculation.pointsToUse.value"
        @input="calculation.setPointsUsage(parseInt($event.target.value) || 0)"
        type="number"
        :max="calculation.userPoints.value.available"
        min="0"
        placeholder="输入使用的积分数量"
      />
      <p class="hint">1 积分 = $0.01，最多抵扣订单金额的 50%</p>
    </div>
  </div>
</template>
```

### **5. 优惠券输入**

```vue
<template>
  <div class="coupon-section">
    <input
      v-model="couponCode"
      type="text"
      placeholder="输入优惠券代码"
    />
    <button @click="applyCoupon" :disabled="!couponCode">
      应用
    </button>

    <div v-if="calculation.appliedCoupon.value" class="applied-coupon">
      ✓ 优惠券已应用: {{ calculation.appliedCoupon.value.code }}
      <button @click="calculation.removeCoupon()">移除</button>
    </div>
  </div>
</template>

<script setup>
const couponCode = ref('')
const { calculation } = useCart()

const applyCoupon = async () => {
  const result = await calculation.applyCoupon(couponCode.value)
  if (result.success) {
    alert('优惠券应用成功！')
    couponCode.value = ''
  } else {
    alert(result.message)
  }
}
</script>
```

---

## 🔧 后端 API 要求

### **必需的 API 端点：**

| 端点 | 方法 | 说明 |
|------|------|------|
| `/tanzanite/v1/shipping-templates` | GET | 获取运费模板列表 |
| `/tanzanite/v1/tax-rates` | GET | 获取税率配置 |
| `/tanzanite/v1/loyalty/points` | GET | 获取用户积分信息 |
| `/tanzanite/v1/coupons/validate` | POST | 验证优惠券 |
| `/tanzanite/v1/orders` | POST | 创建订单 |

### **订单提交数据结构：**

```typescript
{
  items: [
    {
      product_id: 123,
      quantity: 2,
      price: 99.99
    }
  ],
  shipping_address: {
    name: "张三",
    phone: "13800138000",
    address: "某某街道123号",
    city: "北京",
    zip: "100000"
  },
  payment_method: "credit_card",
  notes: "请尽快发货",
  
  // 价格明细
  subtotal: 199.98,
  member_discount: 19.99,
  coupon_discount: 20.00,
  points_discount: 10.00,
  shipping: 10.00,
  tax: 16.00,
  total: 176.99,
  
  // 使用的积分
  points_used: 1000,
  
  // 优惠券代码
  coupon_code: "SUMMER2024"
}
```

---

## 📱 UI 组件

### **已实现的组件：**

1. **CartDrawer.vue** - 购物车抽屉
   - 商品列表
   - 数量控制
   - 价格汇总

2. **CheckoutModal.vue** - 结账弹窗
   - 收货地址表单
   - 支付方式选择
   - 完整价格明细（含所有折扣）
   - 优惠券输入
   - 积分抵扣控制
   - 订单备注

---

## 🎨 自定义配置

### **修改会员等级配置：**

编辑 `composables/useCartCalculation.ts`:

```typescript
export const MEMBER_TIERS: Record<string, MemberTier> = {
  ordinary: { name: 'Ordinary', min: 0, max: 499, discount: 0 },
  bronze: { name: 'Bronze', min: 500, max: 1999, discount: 5 },
  silver: { name: 'Silver', min: 2000, max: 4999, discount: 10 },
  gold: { name: 'Gold', min: 5000, max: 9999, discount: 15 },
  platinum: { name: 'Platinum', min: 10000, max: null, discount: 20 },
}
```

### **修改积分抵扣规则：**

```typescript
const calculatePointsDiscount = (subtotal: number): number => {
  if (!usePointsDiscount.value || !userPoints.value) {
    return 0
  }

  const maxDiscount = subtotal * 0.5 // 修改最大抵扣比例
  const pointsValue = pointsToUse.value * 0.01 // 修改积分价值
  const availablePoints = userPoints.value.available * 0.01

  return Math.min(pointsValue, availablePoints, maxDiscount)
}
```

---

## ✅ 完成清单

- [x] 购物车状态管理 (`useCart.ts`)
- [x] 高级计算系统 (`useCartCalculation.ts`)
- [x] 购物车抽屉组件 (`CartDrawer.vue`)
- [x] 结账弹窗组件 (`CheckoutModal.vue`)
- [x] 运费模板集成
- [x] 税率管理集成
- [x] 会员等级折扣
- [x] 积分抵扣功能
- [x] 优惠券系统
- [x] LocalStorage 持久化
- [x] 响应式设计
- [x] SEO 友好（无独立页面）

---

## 🚀 下一步

1. **后端集成** - 确保所有 API 端点正常工作
2. **测试** - 测试各种折扣组合
3. **UI 优化** - 根据实际需求调整样式
4. **错误处理** - 添加更完善的错误提示
5. **加载状态** - 添加骨架屏和加载动画

---

**购物车系统已完全集成 Tanzanite Setting 配置！** 🎉
