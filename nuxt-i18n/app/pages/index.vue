<template>
  <main class="i18n-demo-page">
    <header class="demo-header">
      <h1>{{ $t('welcome') }}</h1>
      <LanguageSwitcher />
    </header>

    <!-- Tailwind Carousel Preview -->
    <TWCarousel />

    <section class="demo-content" aria-labelledby="demo-breadcrumbs">
      <!-- Breadcrumbs -->
      <nav class="breadcrumbs" aria-label="Breadcrumb" id="demo-breadcrumbs">
        <span>{{ $t('home') }}</span>
        <span> > </span>
        <span>{{ $t('category') }}</span>
        <span> > </span>
        <span>{{ $t('subcategory') }}</span>
      </nav>

      <!-- Search -->
      <form class="search-section" role="search">
        <label class="sr-only" for="demo-search">{{ $t('search.placeholder') }}</label>
        <input
          id="demo-search"
          type="text" 
          :placeholder="$t('search.placeholder')" 
          class="search-input"
        />
        <button type="submit" class="search-button">{{ $t('search.button') }}</button>
      </form>

      <!-- Filters -->
      <section class="filters-section" aria-label="Filter options">
        <div class="filter-item">
          <label for="filter-wheel">{{ $t('filters.wheelDiameter') }}</label>
          <select id="filter-wheel">
            <option>{{ $t('filters.all') }}</option>
          </select>
        </div>
        
        <div class="filter-item">
          <label for="filter-price">{{ $t('filters.price') }}</label>
          <select id="filter-price">
            <option>{{ $t('filters.all') }}</option>
          </select>
        </div>
        
        <div class="filter-item">
          <label for="filter-brand">{{ $t('filters.brand') }}</label>
          <select id="filter-brand">
            <option>{{ $t('filters.all') }}</option>
          </select>
        </div>
        
        <div class="filter-item">
          <label for="filter-brake">{{ $t('filters.brakeStyle') }}</label>
          <select id="filter-brake">
            <option>{{ $t('filters.all') }}</option>
          </select>
        </div>
        
        <div class="filter-item">
          <label for="filter-color">{{ $t('filters.color') }}</label>
          <select id="filter-color">
            <option>{{ $t('filters.all') }}</option>
          </select>
        </div>
      </section>

      <!-- User Actions -->
      <aside class="user-actions" aria-label="Account actions">
        <button type="button" class="action-btn">{{ $t('user.register') }}</button>
        <button type="button" class="action-btn">{{ $t('user.login') }}</button>
        <span class="points">{{ $t('user.points', { count: 0 }) }}</span>
      </aside>

      <!-- CTA -->
      <section class="cta-section" aria-live="polite">
        <p>{{ $t('cta.becomeMemb') }}</p>
      </section>
    </section>
  </main>
</template>

<script setup>
import TWCarousel from '~/components/TWCarousel.vue'
const { t } = useI18n()

// SEO Meta Tags
useHead({
  title: t('welcome'),
  meta: [
    { name: 'description', content: t('welcome') },
    { property: 'og:title', content: t('welcome') },
    { property: 'og:description', content: t('welcome') }
  ]
})
</script>

<style scoped>
.i18n-demo-page {
  min-height: 100vh;
  background: #0a0a0a;
  color: #fff;
  padding: 20px;
}

.demo-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.demo-header h1 {
  font-size: 28px;
  margin: 0;
  background: linear-gradient(90deg, #2aa3ff, #40ffaa);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.demo-content {
  max-width: 800px;
  margin: 0 auto;
}

.breadcrumbs {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 20px;
}

.search-section {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.search-input {
  flex: 1;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: #fff;
  font-size: 14px;
}

.search-button {
  padding: 12px 24px;
  background: rgba(42, 163, 255, 0.18);
  border: 1px solid rgba(42, 163, 255, 0.55);
  border-radius: 6px;
  color: #fff;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.search-button:hover {
  background: rgba(42, 163, 255, 0.28);
}

.filters-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-item label {
  font-size: 13px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.9);
}

.filter-item select {
  padding: 10px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: #fff;
  font-size: 14px;
  cursor: pointer;
}

.user-actions {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 20px;
}

.action-btn {
  padding: 10px 20px;
  background: rgba(42, 163, 255, 0.18);
  border: 1px solid rgba(42, 163, 255, 0.55);
  border-radius: 6px;
  color: #fff;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: rgba(42, 163, 255, 0.28);
}

.points {
  padding: 10px 20px;
  background: rgba(42, 163, 255, 0.18);
  border: 1px solid rgba(42, 163, 255, 0.55);
  border-radius: 6px;
  font-size: 14px;
}

.cta-section {
  padding: 20px;
  background: rgba(42, 163, 255, 0.1);
  border: 1px solid rgba(42, 163, 255, 0.3);
  border-radius: 8px;
  text-align: center;
}

.cta-section p {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  background: linear-gradient(90deg, #ffffff 0%, #ffe8a3 45%, #ffd24d 75%, #ff9f1a 100%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
