# Tanzanite Notes

## 21:9 带鱼屏下语言切换器（LanguageSwitcher）左侧点击失效问题

- 现象
  - 在 16:10、16:9 屏幕下语言切换器正常；在 21:9（带鱼屏）下，下拉菜单左侧几个语言项 hover/点击无响应。

- 根因
  - 21:9 超宽屏未单独适配，导致顶部固定层（Sidebar / MemberModal / Breadcrumbs 等）的高度与位置在该比例下与下拉层产生遮挡关系。
  - 具体为：容器高度设置偏大，固定元素在超宽窗口中纵向占比不合适，叠加层级虽正确，但部分区域实际被覆盖或指针事件被上层布局截获，造成左半侧“点不到”。

- 修复措施（已实施）
  - 新增 21:9 超宽屏媒体查询（仅桌面）：
    - Sidebar：`.sidebar-left { height: 60vh }`
    - MemberModal：`.member-center-box { height: 60vh }`
  - 统一层级管理（仅在 `app/assets/css/z-index.css`）：
    - `--z-lang-dropdown: 1200`
    - `--z-breadcrumbs: 1150`，组件内不写 z-index，避免冲突
  - Breadcrumbs 设为 `pointer-events: none`，不拦截语言下拉的点击

- 复现步骤
  1. 将浏览器窗口调整为约 21:9 比例（或使用带鱼屏设备）
  2. 打开语言切换器下拉
  3. 鼠标移动与点击左列语言项（修复前无响应）

- 验证方法
  - 修复后在 21:9 下左列语言项应正常 hover/点击
  - 16:10、16:9 下行为不受影响

- 维护建议
  - 避免在组件内直接写 `z-index`，统一使用 `z-index.css` 变量
  - 对其它固定定位弹层（Dock、Footer popover 等）如在超宽屏出现覆盖，再加对应的 `min-aspect-ratio: 21/9` 媒体查询进行高度/位置微调
