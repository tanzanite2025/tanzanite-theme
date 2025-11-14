<?php
/**
 * 主模板文件
 * Vue.js + WordPress混合架构主题
 *
 * @package MyThemeVue
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<!-- 主容器 - Vue 将接管此元素 -->
<div id="app" 
     data-site-title="<?php bloginfo('name'); ?>" 
     data-title-tag="<?php echo (is_front_page() && is_home()) ? 'h1' : 'p'; ?>">
    
    
    <?php /* Header Menu 和 Sidebar 由 Nuxt 接管；以下为无脚本回退内容 */ ?>
    <noscript>
      <main class="min2">
          <div class="content-wrapper">
              <?php if (have_posts()) : ?>
                  <?php while (have_posts()) : the_post(); ?>
                      <article id="post-<?php the_ID(); ?>" <?php post_class('content-item'); ?>>
                          <div class="content-inner">
                              <?php the_content(); ?>
                          </div>
                      </article>
                  <?php endwhile; ?>
              <?php else : ?>
                  <div class="no-content">
                      <p><?php _e('暂无内容', 'mytheme-vue'); ?></p>
                  </div>
              <?php endif; ?>
          </div>
      </main>
    </noscript>

    <!-- Footer 统一由 footer.php 输出 -->
</div>

<?php get_footer(); ?>
