<?php
/**
 * 单篇文章模板文件
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
     data-title-tag="p">
    
    
    <!-- Header Menu 和 Sidebar 已迁移到 Nuxt，通过 functions.php 加载 -->
    <?php /* Header Menu 和 Sidebar 由 Nuxt 接管；以下为无脚本回退内容 */ ?>
    <noscript>
      <main class="min2">
          <div class="content-wrapper">
              <?php if (have_posts()) : ?>
                  <?php while (have_posts()) : the_post(); ?>
                      <article id="post-<?php the_ID(); ?>" <?php post_class('content-item'); ?>>
                          <div class="content-inner">
                              <div class="post-content">
                                  <?php the_content(); ?>
                              </div>
                              <div class="post-info">
                                  <div class="post-date"><i class="calendar-icon"></i><?php echo get_the_date(); ?></div>
                                  <div class="post-author"><i class="author-icon"></i><?php the_author(); ?></div>
                                  <div class="post-categories"><i class="category-icon"></i><?php the_category(', '); ?></div>
                              </div>
                              <div class="post-tags"><?php the_tags('', ', ', ''); ?></div>
                              <nav class="post-navigation">
                                  <div class="nav-previous"><?php previous_post_link('%link', '&laquo; %title'); ?></div>
                                  <div class="nav-next"><?php next_post_link('%link', '%title &raquo;'); ?></div>
                              </nav>
                          </div>
                      </article>
                      <div class="comments-area">
                          <?php if (comments_open() || get_comments_number()) : comments_template(); endif; ?>
                      </div>
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
