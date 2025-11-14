# 部署配置说明

## ⚠️ 重要：部署前必须修改配置

### 当前配置（本地预览）
```typescript
// nuxt.config.ts
app: {
  baseURL: '/',  // ← 本地预览用
}
```

### 服务器部署配置
```typescript
// nuxt.config.ts
app: {
  baseURL: '/widget/',  // ← 服务器部署用
}
```

---

## 📋 部署流程

### 步骤 1：修改 baseURL
编辑 `nuxt.config.ts`，将：
```typescript
baseURL: '/'
```
改为：
```typescript
baseURL: '/widget/'
```

### 步骤 2：重新构建
```bash
npm run generate
```

### 步骤 3：打包部署
```bash
tar -czf widget-deploy.tar.gz -C .output/public .
```

### 步骤 4：上传到服务器
将 `widget-deploy.tar.gz` 上传到服务器的 `/public_html/widget/` 目录

---

## 🔄 快速切换脚本

### 切换到本地预览模式
```bash
# 在 nuxt.config.ts 中设置
baseURL: '/'
npm run generate
npx serve .output/public
# 访问：http://localhost:3000/fr/
```

### 切换到服务器部署模式
```bash
# 在 nuxt.config.ts 中设置
baseURL: '/widget/'
npm run generate
tar -czf widget-deploy.tar.gz -C .output/public .
# 上传到服务器
```

---

## ✅ 验证清单

- [ ] 本地预览正常（`baseURL: '/'`）
- [ ] 语言选择器显示正常
- [ ] 所有 12 种语言可切换
- [ ] 修改为 `baseURL: '/widget/'`
- [ ] 重新构建
- [ ] 打包上传
- [ ] 服务器访问测试
