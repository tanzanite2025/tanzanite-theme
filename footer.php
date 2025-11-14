<?php
/**
 * 页脚模板文件
 * Vue.js + WordPress混合架构主题
 *
 * @package MyThemeVue
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

?>

<!-- Footer output由 Nuxt 接管 -->
<div id="mytheme-footer-placeholder" hidden></div>

<!-- WordPress页脚钩子 -->
<?php wp_footer(); ?>



<!-- 无障碍支持 -->
<div class="screen-reader-text" role="status" aria-live="polite" id="status-message"></div>



</body>
</html>
