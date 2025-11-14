# 功能总览（Tanzanite 主题 + 插件）



DirectoryIndex index.html index.php

<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # 已存在的文件或目录直接返回
  RewriteCond %{REQUEST_FILENAME} -f [OR]
  RewriteCond %{REQUEST_FILENAME} -d
  RewriteRule ^ - [L]

  # WordPress 相关路径交给 index.php（REST、后台、PHP 等）
  RewriteCond %{REQUEST_URI} ^/wp-json [NC,OR]
  RewriteCond %{REQUEST_URI} ^/wp-admin [NC,OR]
  RewriteCond %{REQUEST_URI} ^/wp-includes [NC,OR]
  RewriteCond %{REQUEST_URI} ^/wp-content [NC,OR]
  RewriteCond %{REQUEST_URI} \.php$ [NC]
  RewriteRule ^ index.php [L]

  # 其余请求落到 Nuxt 的 index.html
  RewriteRule ^ index.html [L]
</IfModule>
# BEGIN WordPress
# The directives (lines) between "BEGIN WordPress" and "END WordPress" are
# dynamically generated, and should only be modified via WordPress filters.
# Any changes to the directives between these markers will be overwritten.
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>

# END WordPress
- **主题（tanzanite-theme）**
  - **架构**：Nuxt 3 静态站点渲染，多语言 i18n，WP 仅充当 Headless CMS 与 REST API。
  - **页面与组件**：首页、通配路由、Header 菜单页；内置登录/注册/会员侧边栏、快捷购买、WhatsApp 浮动按钮等组件。
  - **部署**：`nuxt-i18n/.output/public` 静态产物上传到站点根；.htaccess 指定 `DirectoryIndex index.html index.php`。
  - **站点设置同步**：通过 `GET /wp-json/mytheme/v1/settings` 同步标题/描述/LOGO/社交链接到前端布局。

- **插件一：MyTheme Member Profiles**
  - **定位**：会员资料与忠诚度（积分/等级）配置中心，提供前端可消费的 REST 数据。
  - **后台**：`Member Profiles → Loyalty Settings`，配置保存在 `mytheme_member_loyalty_config`。
  - **能力**：定义分层会员等级、消费积分换算、折扣策略；为 Nuxt 端与主题函数提供统一读取接口。

- **插件二：MyTheme SEO**
  - **多语言管理**：维护语言列表；公共设置可通过 `GET /wp-json/mytheme/v1/seo/settings/public` 读取。
  - **SEO 基础**：
    - 首页 SEO（标题/描述/关键字等）。
    - 分类与产品分类 SEO 扩展字段（含多语言）。
    - 内容 SEO 编辑器与批量编辑工具（按字段/操作/范围）。
  - **Product JSON-LD（结构化数据）**：
    - 字段映射面板（`name/description/image/brand/sku/gtin/mpn`），来源支持 Core / Meta / WooCommerce / 常量，多语言常量与多种 transform（`strip_tags/trim/to_number/id_to_url/first_gallery_to_url`）。
    - 优先级：`单商品覆盖 > 映射规则 > 默认`；提供“填充推荐映射”一键模板与预览（含来源标注）。
    - 单商品覆盖：品牌与价格来源（促销优先/仅常规）可独立设置。
  - **日志与工具**：
    - 404 监测：自动采集 404 路径、次数、首末时间、来源；支持清空/删除/标记已解决。
    - IndexNow 日志查看与提交记录（如有启用）。
  - **REST 路由（节选）**：
    - 设置/语言/首页/分类等读写：`/mytheme/v1/seo/*`
    - Product Schema 与基础信息：`/mytheme/v1/seo/schema/product/{id|by-slug}`、`/seo/product/basic/*`
    - 单商品覆盖：`/mytheme/v1/seo/product/overrides/{id}`
    - 404 日志：`/mytheme/v1/seo/404-logs`

> 本文档后续章节保留原有目录、部署与开发细节说明。

## 系统结构示意图

```mermaid
graph TD
  subgraph Client[Browser / Nuxt 3 Static Site]
    A[Nuxt 3 App \n i18n / Components / Pages]
  end

  subgraph WP[WordPress (Headless CMS)]
    T[Theme: tanzanite-theme\nHeadless, minimal templates]
    subgraph SEO[Plugin: MyTheme SEO]
      S1[/GET/POST \n /mytheme/v1/seo/settings/\* /]
      S2[/GET/POST \n /mytheme/v1/seo/languages/\* /]
      S3[/GET/POST \n /mytheme/v1/seo/homepage /]
      S4[/GET/POST \n /mytheme/v1/seo/category/{id} /]
      S5[/GET \n /mytheme/v1/seo/schema/product/{id|by-slug} /]
      S6[/GET \n /mytheme/v1/seo/product/basic/{id|by-slug} /]
      S7[/GET/POST \n /mytheme/v1/seo/product/overrides/{id} /]
      S8[/GET/POST \n /mytheme/v1/seo/404-logs /]
    end
    subgraph MP[Plugin: MyTheme Member Profiles]
      M1[/POST auth/register\nPOST auth/login\nPOST auth/logout/]
      M2[/GET auth/me\nGET user/me\nPUT auth/profile/]
      M3[/GET/POST loyalty/points/]
    end
  end

  A -- REST API --> S1
  A -- REST API --> S2
  A -- REST API --> S3
  A -- REST API --> S4
  A -- REST API --> S5
  A -- REST API --> S6
  A -- REST API --> S7
  A -- REST API --> S8
  A -- REST API (Auth & Loyalty) --> M1
  A -- REST API (Auth & Loyalty) --> M2
  A -- REST API (Auth & Loyalty) --> M3
```

> 说明：Nuxt 作为前端渲染与交互层，通过 REST 与 WordPress 通信；WordPress 仅负责内容与接口（主题最小化、功能由插件提供）。

### 服务器端 Tanzanite 主题目录清单

特别注意：在.htaccess中头部添加DirectoryIndex index.html index.php  才能正常读取构建文件的index.html
/public_html
├── _nuxt/整个文件夹
├── about整个文件夹
├── ar等全部语言目录
├── contact整个文件夹
├── header-menu整个文件夹
├── .htaccess【覆盖原有的】
├── .payload.json
├── 200.html
├── 404.html
├── favicon.ico
├── i18n-languages.json
├── robots.txt
├── sitemap.xml
```
/public_html/wp-content/themes/tanzanite-theme/
├── assets/
│   └── js/
├── footer.php
├── functions.php
├── header.php
├── index.php
├── page.php
├── single.php
├── style.css
```

> 若新增字体或静态资源，请保持上述结构并整体同步到服务器。

#### `nuxt-i18n` 静态站点部署

- 运行 `npm run generate` 后生成的 `nuxt-i18n/.output/public/` 需要上传到静态托管目录。
- Hostinger 主域部署示例：将 `nuxt-i18n/.output/public/` 内的所有文件和子目录整体复制到 `/home/<账户名>/domains/tanzanite.site/public_html/`（即 `public_html/` 根目录）。
- WordPress 服务器需要读取语言列表时，可使用主题中的 `nuxt-i18n/public/i18n-languages.json`，或从部署后的静态站点获取。

#### WordPress 自定义器同步

- 新增 REST 端点：`GET /wp-json/mytheme/v1/settings`（等价 `GET /wp-json/tanzanite/v1/settings`），返回站点标题、简介、LOGO 以及社交图标设置。
- Nuxt `default` 布局通过该端点自动同步站点标题/描述/LOGO/社交链接；自定义器修改后只需刷新 Nuxt 页面，无需重新构建。
- 若 REST 端点无法访问，将回退到环境变量 `NUXT_SITE_TITLE`、`NUXT_SOCIAL_LINKS` 等运行时配置。

#### 会员积分配置迁移

- “会员积分与等级” 已从主题自定义器迁移到插件 **MyTheme Member Profiles**。
- WordPress 后台路径：`Member Profiles → Loyalty Settings`。
- 配置保存在 `mytheme_member_loyalty_config` 选项，并被前端与主题函数 `mytheme_vue_get_loyalty_config()` 读取。
- 示例 JSON：

```json
{
  "tiers": [
    { "name": "Bronze", "min": 0, "max": 499, "discount": 0 },
    { "name": "Silver", "min": 500, "max": 1999, "discount": 5 },
    { "name": "Gold", "min": 2000, "max": 4999, "discount": 10 },
    { "name": "Platinum", "min": 5000, "max": 9999, "discount": 15 },
    { "name": "Diamond", "min": 10000, "max": -1, "discount": 20 }
  ],
  "points_per_unit": 1,
  "enabled": true,
  "apply_cart_discount": true
}
```

## MyTheme SEO Bridge（结构化数据 / Robots / Sitemaps）

- 后台入口：`MyTheme SEO`
- 主要功能：
  - Product JSON-LD（产品结构化数据，支持星级聚合评分）
  - 多语言品牌与单商品覆盖（Brand、价格来源）
  - 预览 JSON-LD（按 ID/slug + 语言）
  - Robots 控制（路由与组件 noindex 列表）
  - Sitemaps（可按语言拆分，支持重建与 Ping）

### 一、Product JSON-LD
- 后台面板：`MyTheme SEO → Product Schema`
  - 开关：启用/禁用 JSON-LD
  - 默认 Brand
  - 价格来源：促销价优先 / 仅用常规价
  - 多语言品牌：按语言配置 `brand_i18n`（语言来源与顺序对齐 Nuxt i18n 配置）
  - 单商品覆盖：按 Product ID 设置覆盖 `brand`、`priceSource`
  - 预览：输入 ID 或 slug，选择语言，实时查看 JSON-LD

- 后端端点（均支持 `?locale=xx` 返回指定语言品牌）：
  - `GET /wp-json/mytheme/v1/seo/schema/product/{id}`
  - `GET /wp-json/mytheme/v1/seo/schema/product/by-slug/{slug}`
  - `GET /wp-json/mytheme/v1/seo/schema/product/resolve?id=123|slug=abc`
  - 单商品覆盖：
    - `GET /wp-json/mytheme/v1/seo/product/overrides/{id}`
    - `POST /wp-json/mytheme/v1/seo/product/overrides/{id}` body: `{ overrides: { brand?: string, priceSource?: 'sale_or_regular'|'regular_only' } }`

- 前端接入：
  - 产品详情页：`nuxt-i18n/app/pages/product/[slug].vue`
    - 极简渲染：主图、价格、简介、购买按钮（基础信息端点：`/seo/product/basic/by-slug/{slug}`）
    - JSON-LD 注入：按 URL 推断语言附带 `?locale=` 获取 schema，并注入 `<script type="application/ld+json">`
    - 评分文本：标题下“评分 X.Y（N）”，开关 `runtimeConfig.public.showProductRatingText`
    - 规范化：若 slug 非 canonical，自动跳转 `/product/{canonical}`

### 二、基础产品信息（无 WC Key 渲染）
- 端点（公开只读）：
  - `GET /wp-json/mytheme/v1/seo/product/basic/by-slug/{slug}`
  - `GET /wp-json/mytheme/v1/seo/product/basic/{id}`
- 字段：`id, slug, name, image, price, currency, in_stock, short_desc, permalink`
- 用途：供前端渲染极简产品详情，无需 WooCommerce REST Key

### 三、Robots 控制
- 面板：`MyTheme SEO → Robots 控制`
- 存储：`mytheme_seo_settings.robots`
- 典型用法：
  - `noindex_routes`：按路由添加 `noindex`
  - `noindex_components`：组件级 noindex 控制

### 四、Sitemaps
- 面板：`MyTheme SEO → Sitemaps`
- 支持按语言拆分、包含图片/视频、重建与 Ping
- 端点：
  - `POST /wp-json/mytheme/v1/seo/sitemaps/rebuild`
  - `POST /wp-json/mytheme/v1/seo/sitemaps/ping`

### 五、语言同步
- 语言来源文件：`nuxt-i18n/public/i18n-languages.json`（已对齐 Nuxt i18n 配置）
- 可在 SEO 后台“语言管理/导入”再次导入，保证顺序与标签一致

### 六、字段映射（Product Schema）
- 入口：`MyTheme SEO → 字段映射`
- 作用：为 Product JSON-LD 的关键字段配置来源与转换，满足深度落地与自定义。
- 字段（首批）：`name`、`description`、`image`、`brand`、`sku`、`gtin`、`mpn`
- 产出优先级：`单商品覆盖 > 映射规则 > 默认`

#### 来源类型
- Core：`post_title`、`post_excerpt`、`post_content`、`post_author_display_name`
- Meta：任意 `post_meta`（含 ACF，填写 meta key）
- WooCommerce：`name`、`sku`、`price`、`regular_price`、`sale_price`、`stock_status`、`image_id`、`gallery_ids`
- Constant：常量文本，支持多语言常量 `source.i18n[locale]` 与回退 `source.value`

#### 转换器（Transforms）
- `strip_tags`：移除 HTML
- `trim`：去除首尾空白
- `to_number`：转数值（浮点）
- `id_to_url`：附件 ID → URL
- `first_gallery_to_url`：图库首图 ID → URL

#### 推荐映射模板（按钮：填充推荐映射）
- `name` ← WC.name（trim）
- `description` ← Core.post_excerpt（strip_tags, trim）
- `image` ← WC.image_id（id_to_url），图库作为备选（first_gallery_to_url）
- `brand`：不主动覆盖，仍按“映射>brand_i18n>覆盖>默认”的既有优先级
- `sku` ← WC.sku（trim）
- `gtin` ← Meta._gtin（trim）
- `mpn` ← Meta._mpn（trim）

#### 预览与来源标注
- 在面板底部“预览（含来源标注）”：输入 Product ID 或 slug + 选择语言 → 预览。
- 表格展示：字段当前值 + 来源标签（覆盖/映射/默认）。


### Nuxt 组件目录概览

| 目录 | 文件 | 说明 |
| --- | --- | --- |
| `nuxt-i18n/app/` | `app.vue` | Nuxt 应用根组件，注册默认布局。 |
| `nuxt-i18n/app/components/` | `AnimatedCircularProgressBar.vue`<br>`AppFooter.vue`<br>`BadgeAvatar.vue`<br>`GradientDockMenu.vue`<br>`HeaderMenu.vue`<br>`HeaderMenuDockebar.vue`<br>`LanguageSwitcher.vue`<br>`LoginModal.vue`<br>`MemberModal.vue`<br>`OrderCard.vue`<br>`ProductCard.vue`<br>`QuickBuyModal.vue`<br>`RegisterModal.vue`<br>`Sidebar.vue`<br>`SidebarContent.vue`<br>`UserSidebar.vue`<br>`WhatsAppButton.vue`<br>`WhatsAppModal.vue` | 所有可复用组件，涵盖导航、弹窗、卡片、浮动按钮等。 |
| `nuxt-i18n/app/layouts/` | `default.vue` | 全局布局：挂载 Header、Footer、浮动模块与默认 `useHead` 设置。 |
| `nuxt-i18n/app/pages/` | `index.vue`<br>`[...slug].vue`<br>`header-menu.vue` | 页面路由：分别对应首页、WordPress 动态内容、菜单示例。 |
| `nuxt-i18n/app/assets/css/` | `base.css`<br>`components.css`<br>`z-index.css` | 全局样式、组件样式与层级变量。 |
| `nuxt-i18n/app/composables/` | `useAuth.ts` | 认证状态与 REST 请求封装。 |
| `nuxt-i18n/app/locales/` | `*.json` | 34 种语言翻译 JSON。 |

### WordPress 插件结构概览

| 目录 | 关键文件 | 说明 |
| --- | --- | --- |
| `wp-plugin/mytheme-member-profiles/` | `mytheme-member-profiles.php` | 提供会员资料、快捷购买、客服支持 REST 端点。 |
|  | `includes/` | 具体 REST 控制器、模型、工具函数。 |
| `wp-plugin/mytheme-seo/` | `mytheme-seo.php` | SEO 主插件文件，注册后台页面、语言/SEO REST 接口。 |
|  | `assets/admin.js` | 后台 SPA（语言管理、SEO 编辑器）。 |
|  | `assets/admin.css` (预留) | 可在需要时扩展后台样式。 |
|  | `README.md` (若存在) | 插件说明文档。 |

## 运行与构建

在 `nuxt-i18n/` 目录执行：

```bash
npm install          # 安装依赖
npm run dev          # 本地开发，访问 http://localhost:3000
npm run generate     # 生成静态页面至 .output/public
```

部署时将 `.output/public` 同步至静态主机或 CDN，配置落地页回退（如 Nginx `try_files $uri $uri/ /index.html;`）。

## 运行时配置（.env）

| 变量名              | 说明 | 示例 |
| ------------------- | ---- | ---- |
| `WP_API_BASE`       | WordPress REST 根路径 | `https://tanzanite.site/wp-json` |
| `NUXT_SITE_URL`     | 站点主 URL            | `https://tanzanite.site` |
| `NUXT_SITE_TITLE`   | 默认站点标题          | `Tanzanite` |
| `NUXT_SOCIAL_LINKS` | 可选，社交链接 JSON   | `["network":"facebook", ...]` |

`nuxt.config.ts` 已设置 `runtimeConfig.public`，如需修改默认值可编辑该文件或在部署环境中覆盖变量。

## Nuxt 关键模块

- **布局 (`app/layouts/default.vue`)**：
  - 渲染 Header（社交链接 + 语言切换）、主内容插槽与 Footer。
  - 挂载浮动模块 `<WhatsAppButton />`、`<GradientDockMenu />`（Quick Buy 从 Dock 的最后一个圆形按钮打开）。
  - 使用 `useAsyncData` 调用 WordPress REST：
    - `/mytheme/v1/settings/quick-buy`
    - `/mytheme/v1/settings/support`

- **动态内容 (`app/pages/[...slug].vue`)**：
  - 根据 URL slug 依次请求 `/wp/v2/pages`、`/wp/v2/posts`。
  - 无需 WordPress 模板即可呈现页面/文章内容，并设定 SEO meta。

- **浮动模块**：
  - `WhatsAppButton.vue` / `WhatsAppModal.vue`（左下角）。
  - `GradientDockMenu.vue`（底部居中 Dock，包含购物摘要“数量/重量/价格”、圆形功能按钮与可展开子菜单，且负责打开 `QuickBuyModal`）。
  - `QuickBuyButton.vue`（已废弃，功能合并到 Dock；如仍存在，可手动删除文件）。
  - 均依赖 REST 数据 + Nuxt Teleport 实现弹窗。

- **翻译 (`app/locales/*.json`)**：
  - `langDir` 指向 `app/locales`，主题根目录的旧 `locales/` 已废弃。

### 样式管理

- 全局/基础样式：`app/assets/css/base.css`（布局、加载指示器、iframe 尺寸等）
- 层级变量：`app/assets/css/z-index.css`
- 组件集合样式：`app/assets/css/components.css`
- （已移除）原 Header Dock & 弹窗全局样式：`app/assets/css/headermenudockbar.css`（由组件内 `<style scoped>` 取代）
- 其它组件若有专属样式，优先在各自 `.vue` 文件内使用 `<style scoped>` 保持隔离。

#### 组件定位与全局样式约定（开发阶段）

- 开发阶段，所有“固定定位/层级”的样式一律在各自组件内用 `<style scoped>` 管理，避免全局固定样式牵连其它模块。
- 暂不在 `app.vue` 放置任何全局的固定定位样式；等布局稳定后，再评估是否上升为全局样式。
- 当前约定：
  - HeaderBar：组件内固定，顶部 6px，水平居中，`z-index: 1100`。
  - Share 弹层（GradientDockMenu 内）：`z-index: 1200`。
  - Dockbar：`z-index: 2000`。
- pointer-events 模式：
  - 浮动容器设为 `pointer-events: none`，仅对可点击的子元素开启 `pointer-events: auto`，防止“透明覆盖层”吞掉页面点击。
- 如果需要把样式上升为全局：先在组件内验证不影响其他模块，再迁移到全局样式文件并补充文档说明。

### 响应式适配规范（移动 / 平板）

- **断点定义**
  - 移动端：`max-width: 768px`
  - 平板端：`min-width: 769px and max-width: 1024px`
  - 移动端子断点：
    - 移动S：`≤ 360px`
    - 移动M：`361–400px`
    - 移动L：`401–768px`

- **底部停靠与宽度**
  - 在移动端与平板端，底部停靠类组件统一采用：
    - 固定定位、底部居中（不随页面滚动）。
    - 宽度为视口的 `95vw`，水平居中。
    - 内容采用等分布局（按元素数量 `repeat(N, 1fr)`）。

- **落地实现示例**
  - 语言选择器（`LanguageSwitcher.vue`）
    - 下拉框：固定定位、水平居中、宽度 `90vw`，自适应多列（`repeat(auto-fit, minmax(160px, 1fr))`），选项居中。
    - 无障碍：按钮 `aria-haspopup="listbox"`、`aria-expanded`，列表 `role="listbox"`，选项 `role="option"`，支持键盘导航（↑/↓/Home/End/Enter/Esc）。
    - 遮罩：半透明背景，点击关闭。
  - 渐变菜单（`GradientDockMenu.vue`）
    - 移动端和平板端：`width: 95vw`、底部固定、`grid-template-columns: repeat(5, 1fr)` 等分 5 项。
    - 桌面端：底部中部固定，采用自由/间隔布局。

- **建议**
  - 所有新增底部浮动/弹出式组件，应复用以上断点与布局规则，确保跨设备一致性与可达性。

### 语义化 HTML 规范

- 页面主体应使用 `<main>` 承载内容，配合 `<header>`、`<footer>`、`<section>`、`<article>`、`<aside>` 划分结构。
- 导航区域使用 `<nav>` 并添加 `aria-label` 描述用途（例如语言切换、社交链接）。
- 表单、搜索等交互块使用 `<form>` 并提供可访问的 `<label>`（必要时配合 `.sr-only` 隐藏文本）。
- 列表、面包屑、过滤器等集合类内容使用 `<ul>/<ol>` 或 `<dl>`，避免无语义 `<div>` 堆叠。
- 浮动按钮、弹窗触发等独立功能模块使用 `<aside>` 包裹并设置 `aria-label`/`aria-expanded`。
- 所有新增组件应优先考虑语义标签和 aria 属性，再配合 CSS 做视觉层级。

### Meta 标签与 Schema.org JSON-LD

- **Meta 标签**：写在 `<head>` 中，用来提供页面的基本元信息（标题、描述、作者、OG 社交分享、语言等）。搜索引擎会读取这些信息理解页面主题并在搜索结果中展示摘要。
- **Schema.org JSON-LD**：是一段放在 `<script type="application/ld+json">` 内的结构化数据，使用 Schema.org 的词汇描述页面实体（如 `Product`、`Article`、`FAQPage`）。它不会影响页面视觉，但能让搜索/AI 系统直接获取字段（价格、库存、作者、FAQ 等），提升富结果或生成式摘要质量。
- 两者互补：meta 负责“页面层级”的基本属性，JSON-LD 负责“实体级别”的结构化描述，建议在 Nuxt 组件中同时输出。
对于沿用同一数据结构的页面，后续新增内容会自动套用。
新增不同类型的元素/页面时，需要再补充相应的 meta/JSON-LD 定义（或扩展现有组件的逻辑），以保持准确的结构化数据输出

### MyTheme SEO 插件

- **位置**：`wp-plugin/mytheme-seo/`。启用后将在后台侧边栏显示 “MyTheme SEO”。
- **用途**：集中管理 34 种语言的 SEO 元数据（标题、描述、OpenGraph、Twitter、JSON-LD），并通过 REST 输出给 Nuxt。
- **语言同步**：
  - Nuxt 会在 `nuxt-i18n/public/i18n-languages.json` 暴露语言列表。
  - 插件提供 `POST /wp-json/mytheme/v1/seo/import-languages` 接口，优先读取主题目录下的 JSON；也可传递 `source` URL 从线上站点获取。
  - 在后台语言管理界面触发导入，即可更新 `mytheme_seo_languages` 选项。
- **SEO 编辑流程**：
  1. 通过语言面板导入或手动维护语言代码。
  2. 在 “Edit SEO payload” 卡片中输入 WordPress 页面/文章 ID，抓取现有数据。
  3. 针对每个语言 Tab 填写标题、描述、OG/Twitter、JSON-LD。
  4. 保存后写入 post meta `_mytheme_seo_payload`，Nuxt `[...slug].vue` 会自动读取 `mytheme_seo[currentLocale]` 并注入 `<head>`。
- **REST 接口**：
  - `GET /wp-json/mytheme/v1/seo/languages` 查看当前语言。
  - `POST /wp-json/mytheme/v1/seo/languages` 手动更新语言数组。
  - `POST /wp-json/mytheme/v1/seo/import-languages` 从 JSON 源导入语言（可选参数 `source`）。
  - `GET/POST /wp-json/mytheme/v1/seo/{postId}` 读取/写入具体页面的 SEO payload。
- **注意事项**：确保生成静态页面时 WordPress REST 可访问，以便 Nuxt 拉取最新的 SEO 数据；部署环境更新语言列表后记得重新生成站点。

### WordPress 单源 + Nuxt i18n 翻译策略

- **内容来源**：WordPress 仅维护一份基准语言（推荐英语）的正文，可通过自定义字段拆分为结构化数据（区块、列表、FAQ 等）。
- **Nuxt 文案翻译**：所有可翻译的标题、段落、按钮文案存放在 `app/locales/*.json`，在组件内使用 `useI18n().t()` 渲染，确保 34 种语言对齐。
- **路由映射**：在 `nuxt.config.ts` 的 `i18n.pages` 中为每个页面声明本地化路径，指向相同的 WordPress slug。例如 `about` 页面在各语言映射成 `/about`、`/zh/关于`、`/fr/a-propos`，但最终都请求 `/wp/v2/pages?slug=about`。
- **动态数据**：若 WordPress 中存在可重复的结构（如“亮点列表”），可将其存成数组，由 Nuxt 读取后结合本地化文案渲染。
- **SEO 元数据**：通过 `wp-plugin/mytheme-seo` 插件在后台维护 34 语言的标题/描述/OG/JSON-LD，Nuxt `[...slug].vue` 会自动读取 `mytheme_seo[currentLocale]` 并注入 `<head>`。
- **新增语言**：在插件“语言管理”面板与 Nuxt i18n 配置中同步添加语言代码，即可扩展到新的本地化版本。
- **预期流程**：
  1. WordPress 编辑基准内容 + SEO 字段。
  2. Nuxt 构建/运行时通过 REST 获取内容与 `mytheme_seo`。
  3. 页面组件用 i18n JSON 控制显示文案，保持结构一致；SEO 自动随语言切换。

## WordPress 集成要点

- `functions.php`：
  - 仅保留 `style.css` 空壳（主题信息），前端样式由 Nuxt 管理。


- `index.php` / `page.php` / `single.php`：
  - 仅输出 `<div id="app">` 和 `<noscript>` 回退内容，实际渲染由 Nuxt 完成。

- `wp-plugin/mytheme-member-profiles/`：
  - 提供 Quick Buy、Support、Auth、Profile 等 REST 端点。
  - 确保启用该插件以满足前端数据需求。

## 开发与调试流程

1. 在本地同时运行 WordPress 后台和 `npm run dev`。
2. 通过浏览器访问 Nuxt 前端，检查语言切换与 REST 请求。常用端点：
   - `GET /mytheme/v1/settings/quick-buy`
   - `GET /mytheme/v1/settings/support`
   - `GET /wp/v2/pages?slug=...`
3. 更新翻译时编辑 `nuxt-i18n/app/locales/*.json`。
4. 若需新页面，创建对应 `.vue` 文件或扩展 `[...slug].vue` 的渲染逻辑。

## 部署 Checklist

- [ ] `.env` 中的 `WP_API_BASE` 指向生产地址。
- [ ] `npm run generate` 成功产出 `.output/public`。
- [ ] 静态资源已上传并配置回退规则。
- [ ] WordPress 插件已启用，REST 端点可访问。
- [ ] 浮动模块在生产环境下正常显示、可拉取数据。

## 忠诚度（Loyalty）功能说明与对接

### 邀请注册链接与设置（重要）

- 注册页路径：`/register`
- 邀请链接格式：`/register?ref=TOKEN`
- 生成邀请链接（需登录）：`POST /wp-json/mytheme/v1/referral/token`
  - 返回：`{ url, token, expires_at, remaining }`
- 邀请奖励在“注册成功”后自动发放：邀请人默认 +50，被邀请人默认 +30（可后台配置）。
- Token 默认有效期 7 天、最大使用 50 次（可后台配置）。
- 后台配置位置：`Member Profiles → Loyalty Settings → 邀请注册设置`

前端对接：
- 侧边栏邀请按钮：`nuxt-i18n/app/components/sidebar.vue`（底部居中）。
  - 点击调用 `/referral/token` 并复制链接。
- 访问带 `?ref=TOKEN` 的落地页时写入 Cookie：
  - 中间件：`nuxt-i18n/app/middleware/ref.ts`（将 `mytheme_ref` 写入 7 天）。

测试流程：
1. 登录账号 → 点击侧边栏“邀请好友”→ 链接复制成功。
2. 在隐私窗口打开邀请链接并注册新账号。
3. 验证：邀请人 +50，被邀请人 +30；Token 用尽或过期不再发放。

### 每日签到（登录自动发放）

- 后台配置：`Member Profiles → Loyalty Settings` 中“每日签到一次积多少积分”。
- 行为：用户每日首次登录自动发放；仅签到积分在 30 天后自动清零；购物积分不受影响。
- 手动接口（可选）：`POST /wp-json/mytheme/v1/loyalty/checkin`。

### 购物车积分抵扣（与等级折扣叠加）

- 每等级设置：启用、上限百分比（默认 5%，基于折扣前总额/匹配小计）、每积分面值（基准货币）、最低积分等。
- 行为：购物车阶段自动抵到上限；订单到 `processing/complete` 时扣减已用积分。
- 多货币：按订单货币转换（若无多货币插件则暂按 1:1，可后续接入插件汇率）。

## 维护注意事项
- 若 Nuxt 新增页面，请同步更新 sitemap、SEO 配置及相应翻译。
- 建议定期检查插件端点与 WooCommerce 配置是否与前端需求一致。
### chat-for-theme - 客服聊天 APP

**项目路径**：`../chat-for-theme/`（与主题同级目录）

**功能定位**：
- 🎯 承载 WhatsApp 客服沟通的手机端 APP
- 🎯 与 WordPress 主题的聊天功能对接
- 🎯 客服端移动应用（访客端在 Web Sidebar）
- ⚠️ **当前状态**：基础框架搭建中，尚未完全对接

**技术栈**：
- **框架**：Expo 51.0 + React Native 0.74.1
- **语言**：TypeScript 5.4.5
- **导航**：React Navigation 6.x（Native Stack）
- **UI**：React Native 原生组件
- **通知**：expo-notifications
- **媒体**：expo-image-picker, expo-document-picker

**核心功能**（已实现）：
- ✅ 会话列表页（ChatList.tsx）- 展示最近会话
- ✅ 会话详情页（Chat.tsx）- 消息气泡与输入框
- ✅ 消息组件（MessageBubble.tsx）- 支持发送/接收样式
- ✅ 本地消息发送演示
- ⚠️ 后端对接（待实施）

**目录结构**：
```
chat-for-theme/
├── App.tsx                    # 入口文件
├── app.json                   # Expo 配置
├── package.json               # 依赖管理
├── tsconfig.json              # TypeScript 配置
├── babel.config.js            # Babel 配置
└── src/
    ├── screens/               # 页面组件
    │   ├── ChatList.tsx       # 会话列表
    │   └── Chat.tsx           # 会话详情
    ├── components/            # 通用组件
    │   └── MessageBubble.tsx  # 消息气泡
    ├── services/              # API 服务（待实施）
    └── theme/                 # 主题配置
        └── colors.ts          # 颜色定义
```

**与 WordPress 主题的对接方案**：

1. **身份认证**：
   - 使用 WordPress REST API 的 JWT 认证
   - 复用主题中的 `/wp-json/mytheme/v1/auth/login` 端点
   - 存储 token 到 AsyncStorage

2. **消息通道**：
   - **方案 A**：WebSocket 实时通信（推荐）
     - 后端：Node.js + Socket.io 或 PHP Ratchet
     - 前端：react-native-socket.io-client
   - **方案 B**：轮询 REST API
     - 使用主题现有的 REST API 架构
     - 定时拉取新消息（5-10 秒间隔）

3. **数据存储**：
   - WordPress 自定义表或 Custom Post Type
   - 消息字段：sender_id, receiver_id, content, timestamp, read_status
   - 与 Sidebar 聊天功能共享数据库

4. **推送通知**：
   - 使用 expo-notifications
   - 后端触发推送：Expo Push Notification Service
   - 需要配置 Expo 项目的推送凭证

**开发命令**：
```bash
# 进入 APP 目录
cd ../chat-for-theme

# 安装依赖
npm install

# 启动开发服务器
npm start

# iOS 模拟器（需要 macOS）
npm run ios

# Android 模拟器
npm run android

# Web 预览
npm run web

# 类型检查
npm run typecheck
```

**待实施功能**：
- [ ] 与 WordPress REST API 对接
- [ ] JWT 身份认证集成
- [ ] WebSocket 实时消息通道
- [ ] 消息历史记录加载
- [ ] 图片/文件发送功能
- [ ] 推送通知配置
- [ ] 离线消息缓存
- [ ] 会话未读数统计
- [ ] 客服在线状态显示

**兼容性注意事项**：

⚠️ **API 端点统一**：
- APP 和 Web Sidebar 应使用相同的 REST API 端点
- 确保 Nonce 验证在移动端也能正常工作（可能需要调整为 JWT）

⚠️ **消息格式统一**：
- 定义统一的消息数据结构（JSON Schema）
- 确保 APP 和 Web 端能正确解析彼此的消息

⚠️ **用户角色区分**：
- 客服端（APP）：`role: 'agent'`
- 访客端（Web Sidebar）：`role: 'customer'`
- 后端需要根据角色返回不同的会话列表

⚠️ **实时性要求**：
- 如果使用轮询，注意服务器负载
- 推荐使用 WebSocket 以减少请求次数
- 考虑使用 Redis 缓存在线用户状态

**部署注意事项**：
- Expo 项目需要配置 `app.json` 中的 `slug` 和 `owner`
- 生产环境需要构建 APK/IPA：`eas build`
- 推送通知需要配置 FCM（Android）和 APNs（iOS）

---

## 📡 聊天功能 REST API 规范

### API 端点列表

已在 `functions.php`（第 2599-3069 行）中实现完整的聊天 REST API，供移动端 APP 和 Web Sidebar 使用。

#### 1. 获取会话列表

**端点**：`GET /wp-json/mytheme/v1/chat/conversations`

**参数**：
- `role`（可选）：用户角色，`customer`（访客）或 `agent`（客服），默认 `customer`
- `page`（可选）：页码，默认 `1`
- `per_page`（可选）：每页数量，默认 `20`，最大 `100`

**请求头**：
- `X-WP-Nonce`: WordPress REST API Nonce（必需）
- `Authorization`: Bearer token（可选，如果使用 JWT）

**待实施功能**：
- [ ] 与 WordPress REST API 对接
- [ ] JWT 身份认证集成
- [ ] WebSocket 实时消息通道
- [ ] 消息历史记录加载
- [ ] 图片/文件发送功能
- [ ] 推送通知配置
- [ ] 离线消息缓存
- [ ] 会话未读数统计
- [ ] 客服在线状态显示

**兼容性注意事项**：

⚠️ **API 端点统一**：
- APP 和 Web Sidebar 应使用相同的 REST API 端点
- 确保 Nonce 验证在移动端也能正常工作（可能需要调整为 JWT）

⚠️ **消息格式统一**：
- 定义统一的消息数据结构（JSON Schema）
- 确保 APP 和 Web 端能正确解析彼此的消息

⚠️ **用户角色区分**：
- 客服端（APP）：`role: 'agent'`
- 访客端（Web Sidebar）：`role: 'customer'`
- 后端需要根据角色返回不同的会话列表

⚠️ **实时性要求**：
- 如果使用轮询，注意服务器负载
- 推荐使用 WebSocket 以减少请求次数
- 考虑使用 Redis 缓存在线用户状态

**部署注意事项**：
- Expo 项目需要配置 `app.json` 中的 `slug` 和 `owner`
- 生产环境需要构建 APK/IPA：`eas build`
- 推送通知需要配置 FCM（Android）和 APNs（iOS）

---

## 📡 聊天功能 REST API 规范

### API 端点列表

已在 `functions.php`（第 2599-3069 行）中实现完整的聊天 REST API，供移动端 APP 和 Web Sidebar 使用。

#### 1. 获取会话列表

**端点**：`GET /wp-json/mytheme/v1/chat/conversations`

**参数**：
- `role`（可选）：用户角色，`customer`（访客）或 `agent`（客服），默认 `customer`
- `page`（可选）：页码，默认 `1`
- `per_page`（可选）：每页数量，默认 `20`，最大 `100`

**请求头**：
- `X-WP-Nonce`: WordPress REST API Nonce（必需）
- `Authorization`: Bearer token（可选，如果使用 JWT）

**响应示例**：
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "conversation_id": "conv_123_456_1698765432",
      "customer_id": 123,
      "agent_id": 456,
      "status": "active",
      "last_message_at": "2025-10-26 04:58:00",
      "unread_count": 3,
      "last_message": {
        "content": "你好，有什么可以帮助你的？",
        "message_type": "text",
        "created_at": "2025-10-26 04:58:00",
        "sender_id": 456
      },
      "other_user": {
        "id": 456,
        "name": "客服小王",
        "avatar": "https://example.com/avatar.jpg"
      }
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

#### 2. 获取会话消息列表

**端点**：`GET /wp-json/mytheme/v1/chat/messages/{conversation_id}`

**参数**：
- `conversation_id`（必需）：会话 ID
- `page`（可选）：页码，默认 `1`
- `per_page`（可选）：每页数量，默认 `50`

**响应示例**：
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "conversation_id": "conv_123_456_1698765432",
      "sender_id": 123,
      "receiver_id": 456,
      "sender_role": "customer",
      "content": "你好",
      "message_type": "text",
      "attachment_url": null,
      "read_status": 1,
      "created_at": "2025-10-26 04:55:00",
      "sender": {
        "id": 123,
        "name": "张三",
        "avatar": "https://example.com/avatar.jpg",
        "role": "customer"
      }
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 10
  }
}
```

#### 3. 发送消息

**端点**：`POST /wp-json/mytheme/v1/chat/send`

**请求体**：
```json
{
  "conversation_id": "conv_123_456_1698765432",
  "receiver_id": 456,
  "content": "你好，我需要帮助",
  "message_type": "text",
  "attachment_url": ""
}
```

**参数说明**：
- `conversation_id`（可选）：会话 ID，如果为空则创建新会话
- `receiver_id`（必需）：接收者用户 ID
- `content`（必需）：消息内容
- `message_type`（可选）：消息类型，`text`、`image`、`file`，默认 `text`
- `attachment_url`（可选）：附件 URL（图片或文件）

**响应示例**：
```json
{
  "success": true,
  "data": {
    "id": 10,
    "conversation_id": "conv_123_456_1698765432",
    "sender_id": 123,
    "receiver_id": 456,
    "sender_role": "customer",
    "content": "你好，我需要帮助",
    "message_type": "text",
    "attachment_url": null,
    "read_status": 0,
    "created_at": "2025-10-26 04:58:00",
    "sender": {
      "id": 123,
      "name": "张三",
      "avatar": "https://example.com/avatar.jpg",
      "role": "customer"
    }
  }
}
```

#### 4. 标记消息为已读

**端点**：`POST /wp-json/mytheme/v1/chat/mark-read/{conversation_id}`

**参数**：
- `conversation_id`（必需）：会话 ID

**响应示例**：
```json
{
  "success": true,
  "message": "已标记为已读"
}
```

#### 5. 获取未读消息数

**端点**：`GET /wp-json/mytheme/v1/chat/unread-count`

**响应示例**：
```json
{
  "success": true,
  "data": {
    "total_unread": 5
  }
}
```

### 数据库表结构

#### 消息表：`wp_mytheme_chat_messages`

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | BIGINT | 主键 |
| `conversation_id` | VARCHAR(100) | 会话 ID |
| `sender_id` | BIGINT | 发送者用户 ID |
| `receiver_id` | BIGINT | 接收者用户 ID |
| `sender_role` | VARCHAR(20) | 发送者角色（customer/agent） |
| `content` | TEXT | 消息内容 |
| `message_type` | VARCHAR(20) | 消息类型（text/image/file） |
| `attachment_url` | VARCHAR(500) | 附件 URL |
| `read_status` | TINYINT | 已读状态（0=未读，1=已读） |
| `created_at` | DATETIME | 创建时间 |
| `updated_at` | DATETIME | 更新时间 |

#### 会话表：`wp_mytheme_chat_conversations`

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | BIGINT | 主键 |
| `conversation_id` | VARCHAR(100) | 会话 ID（唯一） |
| `customer_id` | BIGINT | 访客用户 ID |
| `agent_id` | BIGINT | 客服用户 ID |
| `status` | VARCHAR(20) | 会话状态（active/closed） |
| `last_message_at` | DATETIME | 最后消息时间 |
| `unread_count_customer` | INT | 访客未读数 |
| `unread_count_agent` | INT | 客服未读数 |
| `created_at` | DATETIME | 创建时间 |
| `updated_at` | DATETIME | 更新时间 |

### 移动端开发注意事项

#### 1. 认证方式

**当前实现**：使用 Nonce 验证（适合 Web）

**移动端建议**：
- ✅ **推荐**：使用 JWT（JSON Web Token）认证
- ✅ 登录后获取 token，存储到 AsyncStorage
- ✅ 每次请求在 Header 中携带：`Authorization: Bearer {token}`
- ⚠️ **注意**：需要在 WordPress 中安装 JWT 插件（如 JWT Authentication for WP REST API）

**示例代码**（React Native）：
```typescript
// 登录获取 token
const login = async (username: string, password: string) => {
  const response = await fetch('https://your-domain.com/wp-json/jwt-auth/v1/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password })
  });
  const data = await response.json();
  await AsyncStorage.setItem('auth_token', data.token);
};

// 发送消息（携带 token）
const sendMessage = async (conversationId: string, content: string) => {
  const token = await AsyncStorage.getItem('auth_token');
  const response = await fetch('https://your-domain.com/wp-json/mytheme/v1/chat/send', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      conversation_id: conversationId,
      receiver_id: 456,
      content: content
    })
  });
  return await response.json();
};
```

#### 2. 用户角色设置

**客服端（APP）**：
- 在用户 meta 中设置 `chat_role` 为 `agent`
- 使用 WordPress 后台或 API 设置：
  ```php
  update_user_meta($user_id, 'chat_role', 'agent');
  ```

**访客端（Web Sidebar）**：
- 默认角色为 `customer`
- 无需特殊设置

#### 3. 消息格式规范

**统一的消息数据结构**：
```typescript
interface Message {
  id: number;
  conversation_id: string;
  sender_id: number;
  receiver_id: number;
  sender_role: 'customer' | 'agent';
  content: string;
  message_type: 'text' | 'image' | 'file';
  attachment_url?: string;
  read_status: 0 | 1;
  created_at: string; // ISO 8601 格式
  sender?: {
    id: number;
    name: string;
    avatar: string;
    role: 'customer' | 'agent';
  };
}

interface Conversation {
  id: number;
  conversation_id: string;
  customer_id: number;
  agent_id: number;
  status: 'active' | 'closed';
  last_message_at: string;
  unread_count: number;
  last_message?: {
    content: string;
    message_type: string;
    created_at: string;
    sender_id: number;
  };
  other_user?: {
    id: number;
    name: string;
    avatar: string;
  };
}
```

#### 4. 实时消息更新

**方案 A：轮询（简单，适合初期）**
```typescript
// 每 5 秒轮询一次新消息
useEffect(() => {
  const interval = setInterval(async () => {
    const unreadCount = await fetchUnreadCount();
    if (unreadCount > 0) {
      // 刷新会话列表或消息列表
      await refreshMessages();
    }
  }, 5000);
  
  return () => clearInterval(interval);
}, []);
```

**方案 B：WebSocket（推荐，实时性好）**
```typescript
import io from 'socket.io-client';

const socket = io('https://your-domain.com', {
  auth: { token: await AsyncStorage.getItem('auth_token') }
});

socket.on('new_message', (message: Message) => {
  // 收到新消息，更新 UI
  addMessageToList(message);
});

socket.on('message_read', (data: { conversation_id: string }) => {
  // 消息已读，更新状态
  markConversationAsRead(data.conversation_id);
});
```

#### 5. 图片/文件上传

**步骤**：
1. 使用 `expo-image-picker` 或 `expo-document-picker` 选择文件
2. 上传到 WordPress 媒体库（使用 WordPress REST API）
3. 获取文件 URL
4. 发送消息时携带 `attachment_url`

**示例代码**：
```typescript
import * as ImagePicker from 'expo-image-picker';

const pickImage = async () => {
  const result = await ImagePicker.launchImageLibraryAsync({
    mediaTypes: ImagePicker.MediaTypeOptions.Images,
    quality: 0.8,
  });

  if (!result.canceled) {
    // 上传到 WordPress
    const formData = new FormData();
    formData.append('file', {
      uri: result.assets[0].uri,
      type: 'image/jpeg',
      name: 'photo.jpg',
    } as any);

    const token = await AsyncStorage.getItem('auth_token');
    const uploadResponse = await fetch('https://your-domain.com/wp-json/wp/v2/media', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
      body: formData,
    });

    const media = await uploadResponse.json();
    
    // 发送消息
    await sendMessage(conversationId, '发送了一张图片', 'image', media.source_url);
  }
};
```

#### 6. 推送通知

**配置步骤**：
1. 在 Expo 项目中配置推送通知
2. 获取用户的 Expo Push Token
3. 将 token 存储到 WordPress 用户 meta
4. 当有新消息时，后端调用 Expo Push API 发送通知

**示例代码**（获取 Push Token）：
```typescript
import * as Notifications from 'expo-notifications';

const registerForPushNotifications = async () => {
  const { status } = await Notifications.requestPermissionsAsync();
  if (status !== 'granted') {
    alert('需要通知权限');
    return;
  }

  const token = (await Notifications.getExpoPushTokenAsync()).data;
  
  // 保存到 WordPress
  const authToken = await AsyncStorage.getItem('auth_token');
  await fetch('https://your-domain.com/wp-json/mytheme/v1/user/push-token', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${authToken}`
    },
    body: JSON.stringify({ push_token: token })
  });
};
```

#### 7. 错误处理

**统一的错误处理**：
```typescript
const apiCall = async (url: string, options: RequestInit) => {
  try {
    const response = await fetch(url, options);
    const data = await response.json();
    
    if (!response.ok) {
      // HTTP 错误
      throw new Error(data.message || '请求失败');
    }
    
    if (!data.success) {
      // API 返回错误
      throw new Error(data.message || '操作失败');
    }
    
    return data;
  } catch (error) {
    // 网络错误或其他错误
    console.error('API Error:', error);
    Alert.alert('错误', error.message || '网络连接失败');
    throw error;
  }
};
```

#### 8. 性能优化

**建议**：
- ✅ 使用 FlatList 渲染消息列表（虚拟滚动）
- ✅ 实现分页加载（上拉加载更多历史消息）
- ✅ 缓存会话列表到本地（AsyncStorage）
- ✅ 图片使用缩略图，点击后加载原图
- ✅ 离线消息队列（网络恢复后自动发送）

**示例代码**（FlatList）：
```typescript
<FlatList
  data={messages}
  renderItem={({ item }) => <MessageBubble message={item} />}
  keyExtractor={(item) => item.id.toString()}
  inverted // 消息从底部开始
  onEndReached={loadMoreMessages} // 加载更多
  onEndReachedThreshold={0.5}
/>
```

### 接口测试

**使用 Postman 或 curl 测试**：

```bash
# 1. 获取会话列表
curl -X GET "https://your-domain.com/wp-json/mytheme/v1/chat/conversations?role=agent" \
  -H "X-WP-Nonce: your-nonce-here"

# 2. 发送消息
curl -X POST "https://your-domain.com/wp-json/mytheme/v1/chat/send" \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: your-nonce-here" \
  -d '{
    "receiver_id": 456,
    "content": "你好",
    "message_type": "text"
  }'

# 3. 获取未读数
curl -X GET "https://your-domain.com/wp-json/mytheme/v1/chat/unread-count" \
  -H "X-WP-Nonce: your-nonce-here"
```

## MyTheme SEO 插件（后台三栏）功能与待办

### 已实现（2025-10-30）
- 左栏：语言列表（sticky，34 种，点击切换当前语言）。
- 中栏：功能选择
  - 内容 SEO（Post/Page/Product）：
    - 对象类型切换（Post/Page/Product）。
    - 标题搜索与结果列表，点击载入对应对象的 SEO payload。
    - 多语言表单字段：title、description、focus_keyword、og、twitter、jsonld、images、video。
    - 保存接口：
      - Post/Page：`GET/POST /wp-json/mytheme/v1/seo/{id}`
      - Product：`GET/POST /wp-json/mytheme/v1/seo/product/{id}`
  - 分类 SEO（category）：
    - 搜索/选择 term，载入/保存多语言 SEO。
    - 接口：`GET/POST /wp-json/mytheme/v1/seo/taxonomy/category/{termId}`
  - 产品分类 SEO（product_cat）：
    - 搜索/选择 term，载入/保存多语言 SEO。
    - 接口：`GET/POST /wp-json/mytheme/v1/seo/taxonomy/product_cat/{termId}`
  - Robots 控制：
    - `noindex_routes`、`noindex_components` 编辑与保存。
    - 一键填充购物流程 noindex（/cart、/checkout/*、/my-account/*、/order-received、/thank-you）。
    - 接口：`GET/POST /wp-json/mytheme/v1/seo/settings`（字段：`settings.robots`）。

### 重要说明：Product 搜索兼容方案
- 为避免需要 WooCommerce REST 凭证，后台“内容 SEO”在对象类型为 Product 时，采用通用搜索端点：
  - `GET /wp-json/wp/v2/search?subtype=product&search=...&per_page=20`
- Post/Page 仍使用：
  - `GET /wp-json/wp/v2/posts`、`GET /wp-json/wp/v2/pages`（含 search 参数）
- 若后续提供 WooCommerce REST Key，可切换为更精确的 `GET /wc/v3/products` 搜索。

### 待接入/迭代（按优先级）
- 高：
  - 首页 SEO：
    - 多语言表单（与内容 SEO 字段一致）。
    - 接口：`GET/POST /wp-json/mytheme/v1/seo/homepage`。
  - 404 监测：
    - 列表展示、标记已/未解决、删除、清空。
    - 接口：`GET/POST /wp-json/mytheme/v1/seo/404-logs`（`action` + `path`）。
- 中：
  - 内容 SEO 的“对象列表子栏”：搜索/筛选（分类、状态、日期）、虚拟滚动、最近编辑/收藏快捷组。
  - 批量编辑器（标题/描述模板、批量 noindex）。
- 低（可选增强）：
  - SERP 预览、Readability 与 Keyphrase 分析。
  - Schema 默认（站点级类型与字段模板）。

### Nuxt 前端配合
- 在应用启动流程请求：`GET /wp-json/mytheme/v1/seo/settings/public` 并合并到站点配置。
- 渲染时按 `noindex_routes` 输出 `<meta name="robots" content="noindex">`，按 `noindex_components` 跳过或加 `data-noindex`。

### robots.txt 生成与服务器层 UA 阻断（可选增强）

#### 1) robots.txt 生成
- 数据来源：`settings.robots.noindex_routes`、`settings.robots.blocked_user_agents`。
- 生成策略：
  - 对于每个 `blocked_user_agents` 输出：
    ```
    User-agent: <UA>
    Disallow: /
    ```
  - 对于普通抓取器：
    ```
    User-agent: *
    Disallow: <每条 noindex_routes>
    ```
- 位置：`/robots.txt`（静态站有文件，Nuxt 可在构建时写入或由后端动态输出）。

示例：
```
User-agent: AhrefsBot
Disallow: /

User-agent: MJ12bot
Disallow: /

User-agent: *
Disallow: /cart
Disallow: /checkout/
Disallow: /my-account/
```

#### 2) 服务器层 UA 阻断（更强）
- Nginx 示例：
```
map $http_user_agent $badbot {
    default 0;
    ~*(AhrefsBot|MJ12bot|SemrushBot|PetalBot) 1;
}

server {
    # ...
    if ($badbot) { return 403; }
}
```

- Apache 示例（.htaccess）：
```
SetEnvIfNoCase User-Agent "AhrefsBot|MJ12bot|SemrushBot|PetalBot" bad_bot
Order Allow,Deny
Allow from all
Deny from env=bad_bot
```

注意：服务器层阻断优先级高于 meta/noindex，适用于强制拦截恶意爬虫。建议与 `blocked_user_agents` 同步维护。

## IndexNow 集成（后端端点契约）

### 概览
- 默认采用 `POST /wp-json/mytheme/v1/seo/indexnow/push-ids` 在“保存即推”时由后端解析生成 URL 并推送到 `https://api.indexnow.org/indexnow`。
- 保留 `POST /wp-json/mytheme/v1/seo/indexnow/push` 仅用于“直接推 URL”的测试场景。

### 1) 预览生成 URL（不推送）
- 端点：`POST /wp-json/mytheme/v1/seo/indexnow/preview-ids`
- 请求体：
```json
{
  "type": "post|page|product|taxonomy|homepage",
  "id": 123,
  "taxonomy": "category|product_cat",
  "locales": ["en", "zh"]
}
```
- 说明：`homepage` 时 `id` 可为 0；`locales` 留空时后端按 `settings.indexnow.pushAllLocales` 与默认语言策略决定。
- 响应：
```json
{
  "urls": [
    "https://example.com/en/product/sku-123",
    "https://example.com/zh/product/sku-123"
  ]
}
```

### 2) 保存即推（按对象标识）
- 端点：`POST /wp-json/mytheme/v1/seo/indexnow/push-ids`
- 请求体同上；响应：
```json
{
  "success": true,
  "pushed": 2,
  "urls": [
    "https://example.com/en/product/sku-123",
    "https://example.com/zh/product/sku-123"
  ]
}
```

### URL 生成规则（由后端解析）
- 数据来源：`settings.indexnow`。
- 模板（默认）：
  - Page: `/{locale}/{slug}`
  - Post: `/{locale}/{slug}`（可改 `/{locale}/blog/{slug}`）
  - Product: `/{locale}/product/{slug}`
  - Category: `/{locale}/category/{slug}`
  - Product Category: `/{locale}/product-category/{slug}`
- `defaultNoPrefix=true`：默认语言移除 `/{locale}` 前缀。
- 若某语言 payload 存在 `canonical`，优先使用该 URL。
- 可选兜底：无法解析时回退 WP permalink（谨慎）。

### 推送实现建议
- 目标端点：`https://api.indexnow.org/indexnow`
- 批量 JSON：
```json
{
  "host": "example.com",
  "key": "<KEY>",
  "keyLocation": "https://example.com/<KEY>.txt",
  "urlList": ["https://example.com/en/...", "https://example.com/zh/..."]
}
```
- 幂等与去重：按“对象类型+ID+locale”合并去重。
- 限流与重试：指数退避；建议使用 Action Scheduler/WP-Cron 队列。
- 日志：记录对象、locales、生成 URL、状态码、错误、重试次数、时间。

### 管理端交互（已实现）
- IndexNow 卡片：
  - Key 生成/校验、启用开关。
  - URL 模板：Page/Post/Product/Category/ProductCategory。
  - `pushAllLocales`、`defaultNoPrefix` 开关。
  - 测试直推：`POST /seo/indexnow/push`。
  - 预览 URL：`POST /seo/indexnow/preview-ids`，面板内展示列表。
- 保存即推：
  - 内容 SEO、分类/产品分类、首页 SEO 保存成功后调用 `push-ids`。
  - 编辑器提供“Push current locale”与“Preview URLs”按钮。

### 推送日志端点契约（后端）

#### 1) 获取推送日志
- 端点：`GET /wp-json/mytheme/v1/seo/indexnow/logs`
- 查询参数（全部可选）：
  - `from`: `YYYY-MM-DD` 起始日期
  - `to`: `YYYY-MM-DD` 结束日期
  - `type`: `post|page|product|taxonomy|homepage|any`
  - `status`: `success|error|any`
- 响应示例：
```json
{
  "items": [
    {
      "id": 101,
      "type": "product",
      "status": "success",
      "urls": ["https://example.com/en/product/sku-123"],
      "locales": ["en"],
      "object_id": 123,
      "taxonomy": null,
      "created_at": "2025-10-31T01:40:00Z",
      "error": null,
      "attempts": 1
    },
    {
      "id": 102,
      "type": "taxonomy",
      "status": "error",
      "urls": ["https://example.com/zh/product-category/shoes"],
      "locales": ["zh"],
      "object_id": 88,
      "taxonomy": "product_cat",
      "created_at": "2025-10-31T01:41:00Z",
      "error": "429 Too Many Requests",
      "attempts": 3
    }
  ]
}
```

#### 2) 失败记录重试
- 端点：`POST /wp-json/mytheme/v1/seo/indexnow/retry/{id}`
- 行为：将日志记录 `id` 对应的失败任务重新放入队列（Action Scheduler/WP-Cron），并按当前 `settings.indexnow` 解析 URL 后重试推送。
- 响应示例：
```json
{ "success": true }
```

实现建议：
- `logs` 数据表或选项存储应包含：id、type、object_id、taxonomy、locales、urls、status、error、attempts、created_at、updated_at。
- `retry/{id}` 可将 attempts+1 并记录下一次计划执行时间；重试成功后更新为 success 并清空 error。
