# Nuxt I18N Widget - 多语言国际化组件

## 📋 项目概述

这是一个独立的 Nuxt 3 项目，支持 10 种语言的静态生成，专为 SEO 优化设计。

## 🌍 支持的语言

1. **英语** (en) - 默认语言
2. **法语** (fr)
3. **德语** (de)
4. **西班牙语** (es)
5. **日语** (ja)
6. **韩语** (ko)
7. **意大利语** (it)
8. **葡萄牙语** (pt)
9. **俄语** (ru)
10. **阿拉伯语** (ar) - RTL 支持

## 🚀 功能特性

### ✅ SEO 优化
- 每种语言独立 URL 路径
- 自动生成 `hreflang` 标签
- 静态页面生成 (SSG)
- 每个页面独立的 meta 标签

### ✅ 路由结构
```
/widget/              ← 英语（默认）
/widget/fr/           ← 法语
/widget/de/           ← 德语
/widget/es/           ← 西班牙语
/widget/ja/           ← 日语
/widget/ko/           ← 韩语
/widget/it/           ← 意大利语
/widget/pt/           ← 葡萄牙语
/widget/ru/           ← 俄语
/widget/ar/           ← 阿拉伯语
```

### ✅ 语言切换器
- 下拉菜单选择语言
- 显示当前语言名称
- 自动保存用户语言偏好（Cookie）

### ✅ 懒加载
- 翻译文件按需加载
- 优化首屏加载速度

## 📁 项目结构

```
nuxt-i18n-widget/
├── locales/              ← 翻译文件
│   ├── en.json          ← 英语
│   ├── fr.json          ← 法语
│   ├── de.json          ← 德语
│   ├── es.json          ← 西班牙语
│   ├── ja.json          ← 日语
│   ├── ko.json          ← 韩语
│   ├── it.json          ← 意大利语
│   ├── pt.json          ← 葡萄牙语
│   ├── ru.json          ← 俄语
│   └── ar.json          ← 阿拉伯语
├── app/
│   ├── components/
│   │   └── LanguageSwitcher.vue  ← 语言切换器
│   ├── pages/
│   │   └── index.vue    ← 主页面
│   └── app.vue          ← 根组件
├── nuxt.config.ts       ← Nuxt 配置
└── package.json
```

## 🛠️ 开发命令

### 安装依赖
```bash
npm install
```

### 开发模式
```bash
npm run dev
```
访问: `http://localhost:3000/widget/`

### 构建生产版本
```bash
npm run generate
```

生成的静态文件在 `.output/public/` 目录

## 📦 部署到 WordPress

### 1. 构建静态文件
```bash
cd nuxt-i18n-widget
npm run generate
```

### 2. 复制到 WordPress
将 `.output/public/` 目录的内容复制到：
```
WordPress根目录/widget/
```

### 3. 最终结构
```
domain.com/
├── wp-content/
├── wp-includes/
└── widget/              ← Nuxt 构建产物
    ├── _nuxt/          ← 资源文件
    ├── index.html      ← 英语版本
    ├── fr/
    │   └── index.html  ← 法语版本
    ├── de/
    │   └── index.html  ← 德语版本
    └── ...
```

### 4. 访问 URL
```
domain.com/widget/      ← 英语
domain.com/widget/fr/   ← 法语
domain.com/widget/de/   ← 德语
...
```

## 🔧 添加新翻译

### 1. 编辑翻译文件
在 `locales/` 目录下编辑对应语言的 JSON 文件：

```json
{
  "welcome": "欢迎",
  "home": "首页",
  "search": {
    "placeholder": "搜索...",
    "button": "搜索"
  }
}
```

### 2. 在组件中使用
```vue
<template>
  <div>
    <h1>{{ $t('welcome') }}</h1>
    <input :placeholder="$t('search.placeholder')" />
    <button>{{ $t('search.button') }}</button>
  </div>
</template>
```

### 3. 带参数的翻译
```json
{
  "user": {
    "points": "积分: {count}"
  }
}
```

```vue
<span>{{ $t('user.points', { count: 100 }) }}</span>
```

## 🌐 SEO 标签示例

每个页面自动生成：

```html
<html lang="en">
<head>
  <title>Welcome</title>
  <meta name="description" content="Welcome" />
  
  <!-- hreflang 标签 -->
  <link rel="alternate" hreflang="en" href="https://domain.com/widget/" />
  <link rel="alternate" hreflang="fr" href="https://domain.com/widget/fr/" />
  <link rel="alternate" hreflang="de" href="https://domain.com/widget/de/" />
  <!-- ... 其他语言 -->
  
  <!-- Open Graph -->
  <meta property="og:title" content="Welcome" />
  <meta property="og:description" content="Welcome" />
</head>
</html>
```

## 📝 注意事项

1. **翻译文件格式**：必须是有效的 JSON 格式
2. **键名一致性**：所有语言文件的键名必须一致
3. **RTL 支持**：阿拉伯语已配置为 RTL（从右到左）
4. **浏览器检测**：自动检测用户浏览器语言并重定向
5. **Cookie 保存**：用户选择的语言会保存在 Cookie 中

## 🔍 测试

### 本地测试
```bash
npm run dev
```

访问不同语言版本：
- http://localhost:3000/widget/
- http://localhost:3000/widget/fr/
- http://localhost:3000/widget/de/

### 生产测试
```bash
npm run generate
npx serve .output/public
```

## 📚 技术栈

- **Nuxt 3** - Vue.js 框架
- **@nuxtjs/i18n** - 国际化模块
- **Static Site Generation (SSG)** - 静态生成
- **SEO 优化** - hreflang, meta 标签

## 🆘 常见问题

### Q: 如何添加新语言？
A: 
1. 在 `locales/` 创建新的 JSON 文件
2. 在 `nuxt.config.ts` 的 `locales` 数组中添加配置
3. 在 `prerender.routes` 中添加新路由

### Q: 翻译不显示？
A: 检查：
1. JSON 文件格式是否正确
2. 键名是否匹配
3. 是否重新构建了项目

### Q: SEO 标签不生成？
A: 确保 `nuxt.config.ts` 中 `i18n.seo` 设置为 `true`

## 📞 支持

如需帮助，请查看：
- [Nuxt 文档](https://nuxt.com)
- [@nuxtjs/i18n 文档](https://i18n.nuxtjs.org)
