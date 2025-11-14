# LeverAndPoint 动态会员等级配置

## 🎯 问题

之前 LeverAndPoint.vue 组件中的会员等级折扣百分比是**硬编码**的，不会随后台 Tanzanite Setting 的配置变化而更新。

### **问题详情：**

1. **表格中的折扣是硬编码的**
2. **顶部用户信息的折扣也是硬编码的**
3. **与后台配置不一致**

---

## ✅ 解决方案

### **1. 创建公开的 REST API 端点**

**端点：** `GET /wp-json/tanzanite/v1/loyalty/settings`

**权限：** 公开访问（`__return_true`）

**响应示例：**

```json
{
  "tiers": {
    "ordinary": {
      "name": "Ordinary",
      "min": 0,
      "max": 499,
      "discount": 0,
      "points_discount": 0,
      "stackable": true
    },
    "bronze": {
      "name": "Bronze",
      "min": 500,
      "max": 1999,
      "discount": 5,
      "points_discount": 0,
      "stackable": true
    },
    "silver": {
      "name": "Silver",
      "min": 2000,
      "max": 4999,
      "discount": 10,
      "points_discount": 5,
      "stackable": true
    },
    "gold": {
      "name": "Gold",
      "min": 5000,
      "max": 9999,
      "discount": 15,
      "points_discount": 10,
      "stackable": true
    },
    "platinum": {
      "name": "Platinum",
      "min": 10000,
      "max": null,
      "discount": 20,
      "points_discount": 15,
      "stackable": true
    }
  }
}
```

---

### **2. 前端动态加载配置**

#### **加载配置：**

```typescript
// LeverAndPoint.vue
const tierConfigs = ref([])

const loadTierConfigs = async () => {
  try {
    const response = await $fetch('/wp-json/tanzanite/v1/loyalty/settings')
    if (response?.tiers) {
      tierConfigs.value = Object.entries(response.tiers).map(([key, config]) => ({
        key,
        name: config.name,
        min: config.min,
        max: config.max,
        discount: config.discount,
        pointsDiscount: config.points_discount || 0,
        stackable: config.stackable !== false
      }))
    }
  } catch (error) {
    console.error('Failed to load tier configs:', error)
  }
}

onMounted(() => {
  loadTierConfigs()
})
```

#### **动态渲染表格：**

```vue
<template>
  <!-- 动态渲染会员等级表格 -->
  <div
    v-for="tier in tierConfigs"
    :key="tier.key"
    class="grid grid-cols-2 md:grid-cols-[1.1fr_1fr_1fr_1fr] gap-1.5 md:gap-0 items-center py-2 px-3 border border-white/10 rounded-[10px] bg-white/[0.04] odd:bg-white/[0.03]"
  >
    <div class="text-[13px] text-white/90">
      {{ tier.name }}
    </div>
    <div class="text-[13px] text-white/90">
      {{ tier.min }}{{ tier.max !== null ? '–' + tier.max : '+' }}
    </div>
    <div class="text-[13px] text-white/90">
      {{ tier.discount }}%
    </div>
    <div class="text-[13px] text-white/90">
      {{ tier.pointsDiscount }}%
    </div>
  </div>
</template>
```

#### **动态计算用户折扣：**

```typescript
const levelDiscounts = computed(() => {
  const lvl = (levelName.value || '').toString().toLowerCase()
  if (!lvl || lvl === '—') return { product: 0, points: 0, stackable: false }
  
  // 从后台加载的配置中查找
  const config = tierConfigs.value.find(t => t.key === lvl)
  if (config) {
    return {
      product: config.discount,
      points: config.pointsDiscount,
      stackable: config.stackable
    }
  }
  
  return { product: 0, points: 0, stackable: false }
})
```

---

## 🔄 实时更新流程

```
1. 管理员在后台修改会员等级折扣
   ↓
2. 保存到 WordPress 选项表
   (tanzanite_loyalty_config)
   ↓
3. 用户打开 LeverAndPoint 弹窗
   ↓
4. onMounted() 调用 loadTierConfigs()
   ↓
5. 从 API 获取最新配置
   GET /wp-json/tanzanite/v1/loyalty/settings
   ↓
6. 更新 tierConfigs.value
   ↓
7. Vue 响应式系统自动更新 UI
   ↓
8. ✅ 显示最新的折扣百分比
```

---

## 📊 对比

### **之前（硬编码）：**

```typescript
const levelDiscounts = computed(() => {
  const map = {
    ordinary: { product: 5, points: 0 },   // ❌ 硬编码
    bronze: { product: 10, points: 0 },    // ❌ 硬编码
    silver: { product: 15, points: 5 },    // ❌ 硬编码
    gold: { product: 20, points: 10 },     // ❌ 硬编码
    supreme: { product: 30, points: 15 }   // ❌ 硬编码
  }
  return map[lvl] || { product: 0, points: 0 }
})
```

**问题：**
- ❌ 与后台配置不一致
- ❌ 修改后台配置不会更新前端
- ❌ 需要手动修改代码

### **现在（动态加载）：**

```typescript
const levelDiscounts = computed(() => {
  const config = tierConfigs.value.find(t => t.key === lvl)
  if (config) {
    return {
      product: config.discount,        // ✅ 从 API 读取
      points: config.pointsDiscount,   // ✅ 从 API 读取
      stackable: config.stackable      // ✅ 从 API 读取
    }
  }
  return { product: 0, points: 0, stackable: false }
})
```

**优势：**
- ✅ 与后台配置完全同步
- ✅ 修改后台配置立即生效
- ✅ 无需修改代码

---

## 🎯 测试步骤

### **1. 测试动态加载：**

1. 打开浏览器开发者工具（Network 标签）
2. 点击 GradientDockMenu 的分享按钮
3. 打开 LeverAndPoint 弹窗
4. 查看 Network 标签，应该看到：
   ```
   GET /wp-json/tanzanite/v1/loyalty/settings
   Status: 200 OK
   ```

### **2. 测试实时更新：**

1. 登录 WordPress 后台
2. 进入 Tanzanite Settings → Loyalty Points
3. 修改某个等级的折扣百分比（例如：Bronze 从 5% 改为 8%）
4. 保存设置
5. 刷新前端页面
6. 打开 LeverAndPoint 弹窗
7. ✅ 应该显示新的折扣百分比（8%）

### **3. 测试用户信息：**

1. 登录一个会员账号
2. 打开 LeverAndPoint 弹窗
3. 查看顶部用户信息区域
4. ✅ "Product discount rate" 应该显示正确的百分比
5. ✅ "Points discount rate" 应该显示正确的百分比

---

## 📝 后端实现

### **API 端点注册：**

```php
// includes/legacy-pages.php
public function register_rest_routes(): void {
    // ...
    
    // Loyalty Settings REST API - 公开访问会员等级配置
    register_rest_route(
        'tanzanite/v1',
        '/loyalty/settings',
        array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'rest_get_loyalty_settings' ),
            'permission_callback' => '__return_true', // 公开访问
        )
    );
}
```

### **API 回调方法：**

```php
public function rest_get_loyalty_settings( $request ) {
    $config_json = get_option( 'tanzanite_loyalty_config', '' );
    
    if ( empty( $config_json ) ) {
        $config = $this->get_default_loyalty_config();
    } else {
        $config = json_decode( $config_json, true );
        if ( ! is_array( $config ) ) {
            $config = $this->get_default_loyalty_config();
        }
    }
    
    // 返回简化的配置，只包含前端需要的字段
    $response = array(
        'tiers' => array(),
    );
    
    if ( isset( $config['tiers'] ) && is_array( $config['tiers'] ) ) {
        foreach ( $config['tiers'] as $key => $tier ) {
            $response['tiers'][$key] = array(
                'name'            => $tier['name'] ?? $tier['label'] ?? ucfirst( $key ),
                'min'             => intval( $tier['min'] ?? 0 ),
                'max'             => $tier['max'] === null ? null : intval( $tier['max'] ),
                'discount'        => intval( $tier['discount'] ?? 0 ),
                'points_discount' => intval( $tier['redeem']['percent_of_total'] ?? 0 ),
                'stackable'       => boolval( $tier['redeem']['stack_with_percent'] ?? true ),
            );
        }
    }
    
    return rest_ensure_response( $response );
}
```

---

## ✅ 完成清单

- [x] 创建 REST API 端点 `/tanzanite/v1/loyalty/settings`
- [x] 设置为公开访问（`__return_true`）
- [x] 实现 API 回调方法 `rest_get_loyalty_settings()`
- [x] 前端添加 `loadTierConfigs()` 方法
- [x] 在 `onMounted()` 中调用加载方法
- [x] 动态渲染会员等级表格
- [x] 动态计算用户折扣信息
- [x] 移除所有硬编码的折扣值

---

## 🎉 总结

**现在 LeverAndPoint.vue 组件完全与后台 Tanzanite Setting 同步！**

- ✅ **实时更新** - 后台修改立即生效
- ✅ **无需重新部署** - 配置存储在数据库中
- ✅ **公开访问** - 所有用户都能看到最新配置
- ✅ **动态渲染** - 自动适应任意数量的等级

**管理员只需在后台修改配置，前端会自动显示最新的折扣百分比！** 🔄✨
