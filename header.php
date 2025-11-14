<?php
/**
 * 头部模板文件
 * Vue.js + WordPress混合架构主题
 *
 * @package MyThemeVue
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
    <!-- Legacy数据注入已由 Nuxt 接管 -->
</head>
<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<!-- 跳过链接 -->
<a class="skip-link screen-reader-text" href="#main-content">
    <?php _e('跳转到主内容', 'mytheme-vue'); ?>
</a>

