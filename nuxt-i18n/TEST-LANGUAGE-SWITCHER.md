# 语言选择器测试指南

## 🔍 问题诊断

如果你看不到语言选择器，可能的原因：

### 1. **组件未渲染**
检查浏览器开发者工具（F12）：
- 打开 Console 标签
- 查看是否有 "LanguageSwitcher mounted" 日志
- 查看是否有 "Total locales" 日志

### 2. **样式问题**
语言选择器可能被隐藏或样式错误：
- 打开 Elements/检查元素
- 搜索 `language-switcher` 类名
- 检查元素是否存在但不可见

### 3. **构建问题**
静态构建可能有问题：
- 开发模式正常但生产构建失败
- 需要重新构建

---

## 🧪 测试步骤

### 步骤 1：启动开发服务器

```bash
cd nuxt-i18n-widget
npm run dev
```

访问：`http://localhost:3000/widget/fr/`

### 步骤 2：检查页面

你应该看到：
1. **页面顶部**：标题 "Bienvenue" + 语言选择器按钮
2. **语言选择器**：显示当前语言名称（如 "Français"）和下拉箭头 ▼
3. **点击按钮**：显示其他 11 种语言的下拉菜单

### 步骤 3：打开浏览器控制台

按 F12，查看 Console 输出：
```
LanguageSwitcher mounted
Total locales: [Array of 12 locales]
Current locale: fr {code: 'fr', iso: 'fr-FR', name: 'Français', ...}
Available locales: [Array of 11 locales]
```

---

## 🎨 语言选择器外观

### 正常显示应该是：

```
┌─────────────────────────────────────────┐
│  Bienvenue          [Français ▼]        │
└─────────────────────────────────────────┘
```

点击后：
```
┌─────────────────────────────────────────┐
│  Bienvenue          [Français ▲]        │
│                     ┌──────────────┐    │
│                     │ Deutsch      │    │
│                     │ Español      │    │
│                     │ 日本語       │    │
│                     │ 한국어       │    │
│                     │ Italiano     │    │
│                     │ ...          │    │
│                     └──────────────┘    │
└─────────────────────────────────────────┘
```

---

## 🐛 如果看不到语言选择器

### 方案 A：检查 HTML 结构

在浏览器中右键 → 检查元素，查找：
```html
<div class="demo-header">
  <h1>...</h1>
  <div class="language-switcher">
    <button class="lang-button">
      <span class="current-lang">Français</span>
      <span class="arrow">▼</span>
    </button>
  </div>
</div>
```

如果找不到 `.language-switcher`，说明组件未渲染。

### 方案 B：检查 CSS

在 Elements 标签中，选中语言选择器元素，查看 Styles 面板：
- 检查是否有 `display: none`
- 检查是否有 `visibility: hidden`
- 检查 `z-index` 是否被其他元素覆盖

### 方案 C：强制显示

临时添加内联样式测试：
```vue
<div class="language-switcher" style="background: red; padding: 20px;">
```

如果看到红色背景，说明组件存在但样式有问题。

---

## 🔧 快速修复

### 修复 1：增加语言选择器可见性

编辑 `app/components/LanguageSwitcher.vue`，修改样式：

```css
.language-switcher {
  position: relative;
  z-index: 10000; /* 增加 z-index */
}

.lang-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px; /* 增大按钮 */
  background: rgba(42, 163, 255, 0.3); /* 增加背景透明度 */
  border: 2px solid rgba(42, 163, 255, 0.8); /* 加粗边框 */
  border-radius: 8px;
  color: #fff;
  font-size: 16px; /* 增大字体 */
  font-weight: 600; /* 加粗字体 */
  cursor: pointer;
  transition: all 0.2s ease;
}
```

### 修复 2：添加调试边框

临时添加明显的边框：

```css
.language-switcher {
  border: 3px solid red !important;
  padding: 10px !important;
  background: yellow !important;
}
```

---

## 📸 截图对比

### 预期效果：
- 右上角有一个蓝色半透明按钮
- 按钮上显示当前语言名称
- 按钮右侧有下拉箭头 ▼
- 鼠标悬停时背景变亮
- 点击后显示下拉菜单

### 如果看不到：
1. 拍摄当前页面截图
2. 打开浏览器开发者工具截图
3. 检查 Console 和 Elements 标签

---

## 🆘 联系支持

如果以上方法都无效，请提供：
1. 浏览器类型和版本
2. Console 控制台的完整输出
3. Elements 标签中的 HTML 结构截图
4. 访问的完整 URL
