<template>
  <footer class="app-footer">
    <div class="footer-content">
      <div class="footer-widgets">
        <slot name="widgets" />
      </div>
      <div class="footer-info">
        <p>
          &copy;
          <span>{{ currentYear }}</span>
          <span class="footer-site">{{ siteTitle }}</span>
          <span>{{ $t('footer.allRightsReserved') }}</span>
        </p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRuntimeConfig } from '#imports'

const config = useRuntimeConfig()

const currentYear = computed(() => new Date().getFullYear())

const siteTitle = computed(() => {
  const title = (config.public as { siteTitle?: string }).siteTitle
  return title && title.trim().length ? title : 'Tanzanite'
})
</script>

<style scoped>
.app-footer {
  padding: 3rem 1.5rem;
  background: rgba(0, 0, 0, 0.85);
  color: #f9fafb;
}

.footer-content {
  max-width: 960px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  text-align: center;
}

.footer-widgets {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: center;
}

.footer-info {
  font-size: 0.875rem;
  color: rgba(249, 250, 251, 0.7);
}

.footer-info p {
  margin: 0;
}

.footer-site {
  margin: 0 0.5rem;
  font-weight: 600;
}

@media (max-width: 768px) {
  .app-footer {
    padding: 2rem 1.25rem;
  }
}
</style>
