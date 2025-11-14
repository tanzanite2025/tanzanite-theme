<template>
  <SiteHeader />
  <NuxtLayout>
    <SidePanel>
      <template #left>
        <SidebarContent />
      </template>
      <template #right>
        <!-- 会员中心内容将在这里 -->
      </template>
    </SidePanel>
  </NuxtLayout>
  
  <!-- 购物车和结账弹窗 -->
  <CartDrawer />
  <CheckoutModal />
</template>

<script setup>
import SidePanel from './components/SidePanel.vue'
import SidebarContent from './components/SidebarContent.vue'
import SiteHeader from '~/components/SiteHeader.vue'
</script>
