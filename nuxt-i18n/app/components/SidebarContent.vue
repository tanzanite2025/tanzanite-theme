<template>
  <div class="flex gap-4 p-0 h-full justify-center items-stretch overflow-hidden max-md:flex-col max-md:gap-2 max-md:items-center bg-black">
    <div class="flex-1 h-full flex flex-col border-r border-white/[0.08] max-md:w-full max-md:border-r-0"></div>
    <div class="flex-1 h-full flex flex-col items-center justify-center gap-3 max-md:w-full max-md:gap-2">
      <!-- 快速搜索 -->
      <div class="w-[90%]">
        <div class="flex flex-col gap-1.5 w-full">
          <input 
            class="w-full h-9 px-3 py-2 border border-white/20 rounded-lg bg-white/[0.05] text-white text-[13px] box-border transition-all duration-200 placeholder:text-white/50 focus:outline-none focus:border-blue-500 focus:bg-white/[0.08]" 
            type="text" 
            :placeholder="$t('search.placeholder')" 
            v-model="searchQuery"
            @keyup.enter="doSearch"
          >
          <button 
            class="w-full h-9 px-4 py-2 border-none rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 text-white text-[13px] font-medium cursor-pointer box-border transition-all duration-200 whitespace-nowrap hover:bg-gradient-to-br hover:from-blue-600 hover:to-purple-700 hover:-translate-y-0.5" 
            type="button" 
            @click="doSearch"
          >
            {{ $t('search.button') }}
          </button>
        </div>
      </div>

      <!-- 会员中心按钮 -->
      <div class="w-[90%]">
        <button 
          class="w-full h-9 px-4 py-2 border-none rounded-xl bg-gradient-to-r from-cyan-400 via-[#6e6ee9] to-[#ac69ee] text-white text-sm font-semibold cursor-pointer box-border transition-all duration-200 flex items-center justify-center gap-2 pointer-events-auto hover:-translate-y-0.5" 
          @click="openMember()"
        >
          <span class="text-xl">👤</span>
          <span>{{ $t('promo.memberCta', 'Up to 50% Off for Members') }}</span>
        </button>
      </div>

      <!-- 快捷链接 -->
      <div class="flex flex-col gap-1.5 w-[90%]">
        <NuxtLink 
          to="/" 
          class="flex items-center gap-2.5 px-3.5 py-2 h-9 rounded-lg bg-white/[0.05] text-white no-underline box-border transition-all duration-200 hover:bg-white/10 hover:translate-x-1"
        >
          <span class="text-lg w-6 text-center">🏠</span>
          <span class="text-sm font-medium">{{ $t('nav.home') }}</span>
        </NuxtLink>
        <NuxtLink 
          to="/shop" 
          class="flex items-center gap-2.5 px-3.5 py-2 h-9 rounded-lg bg-white/[0.05] text-white no-underline box-border transition-all duration-200 hover:bg-white/10 hover:translate-x-1"
        >
          <span class="text-lg w-6 text-center">🛍️</span>
          <span class="text-sm font-medium">{{ $t('nav.shop') }}</span>
        </NuxtLink>
        <NuxtLink 
          to="/about" 
          class="flex items-center gap-2.5 px-3.5 py-2 h-9 rounded-lg bg-white/[0.05] text-white no-underline box-border transition-all duration-200 hover:bg-white/10 hover:translate-x-1"
        >
          <span class="text-lg w-6 text-center">ℹ️</span>
          <span class="text-sm font-medium">{{ $t('nav.about') }}</span>
        </NuxtLink>
        <NuxtLink 
          to="/contact" 
          class="flex items-center gap-2.5 px-3.5 py-2 h-9 rounded-lg bg-white/[0.05] text-white no-underline box-border transition-all duration-200 hover:bg-white/10 hover:translate-x-1"
        >
          <span class="text-lg w-6 text-center">📧</span>
          <span class="text-sm font-medium">{{ $t('nav.contact') }}</span>
        </NuxtLink>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, inject } from 'vue'

const searchQuery = ref('')
const router = useRouter()

// 获取父组件 SidePanel 暴露的方法
const sidePanel = inject('sidePanel', null)

const doSearch = async () => {
  const value = searchQuery.value.trim()
  if (!value) {
    return
  }

  await router.push({
    path: '/search',
    query: { q: value }
  })
}

// 打开会员中心（右侧面板）
const opening = ref(false)
const openMember = async () => {
  if (opening.value) return
  opening.value = true
  console.debug('sidebar: member CTA clicked')
  
  // 调用父组件的方法打开右侧面板
  if (sidePanel && typeof sidePanel.openRight === 'function') {
    sidePanel.openRight()
  }
  
  setTimeout(() => { opening.value = false }, 360)
}
</script>
