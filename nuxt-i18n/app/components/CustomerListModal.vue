<template>
  <Teleport to="body">
    <!-- 遮罩层 -->
    <Transition name="fade">
      <div
        v-if="isCustomerListOpen"
        class="fixed inset-0 bg-black z-[9998] flex items-center justify-center p-4"
        @click.self="closeCustomerList"
      >
        <!-- 客户列表弹窗 -->
        <Transition name="scale">
          <div
            v-if="isCustomerListOpen"
            class="bg-white rounded-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden shadow-2xl"
          >
            <!-- 头部 -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
              <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                </svg>
                <h2 class="text-xl font-bold text-gray-900">
                  客户消息
                  <span v-if="totalUnreadCount > 0" class="ml-2 px-2 py-0.5 bg-red-500 text-white text-xs rounded-full">
                    {{ totalUnreadCount }}
                  </span>
                </h2>
              </div>
              <button
                @click="closeCustomerList"
                class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/50 transition-colors"
                aria-label="关闭"
              >
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- 搜索栏 -->
            <div class="px-6 py-3 border-b border-gray-200">
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="搜索客户..."
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <!-- 客户列表 -->
            <div class="overflow-y-auto max-h-[calc(80vh-180px)]">
              <div v-if="filteredConversations.length === 0" class="flex flex-col items-center justify-center py-12">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <p class="text-gray-500 text-lg font-medium">暂无客户消息</p>
              </div>

              <div
                v-for="conversation in filteredConversations"
                :key="conversation.id"
                @click="handleOpenChat(conversation)"
                class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
              >
                <!-- 头像 -->
                <div class="relative flex-shrink-0">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center text-white font-semibold text-lg">
                    {{ getInitials(conversation.customer_name) }}
                  </div>
                  <div
                    v-if="conversation.unread_count > 0"
                    class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold"
                  >
                    {{ conversation.unread_count > 9 ? '9+' : conversation.unread_count }}
                  </div>
                </div>

                <!-- 内容 -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between mb-1">
                    <h3 class="font-semibold text-gray-900 truncate">
                      {{ conversation.customer_name }}
                    </h3>
                    <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                      {{ formatTime(conversation.last_message_time) }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 truncate">
                    {{ conversation.last_message || '暂无消息' }}
                  </p>
                  <div class="flex items-center gap-2 mt-1">
                    <span
                      class="text-xs px-2 py-0.5 rounded-full"
                      :class="{
                        'bg-green-100 text-green-700': conversation.status === 'active',
                        'bg-gray-100 text-gray-700': conversation.status === 'closed',
                        'bg-yellow-100 text-yellow-700': conversation.status === 'pending'
                      }"
                    >
                      {{ getStatusText(conversation.status) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const {
  conversations,
  isCustomerListOpen,
  totalUnreadCount,
  closeCustomerList,
  openChat,
} = useChat()

const searchQuery = ref('')

// 过滤客户列表
const filteredConversations = computed(() => {
  if (!searchQuery.value) {
    return conversations.value
  }
  const query = searchQuery.value.toLowerCase()
  return conversations.value.filter(conv =>
    conv.customer_name.toLowerCase().includes(query) ||
    conv.last_message?.toLowerCase().includes(query)
  )
})

// 获取首字母
const getInitials = (name: string) => {
  if (!name) return '?'
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

// 格式化时间
const formatTime = (time?: string) => {
  if (!time) return ''
  
  const date = new Date(time)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  
  if (minutes < 1) return '刚刚'
  if (minutes < 60) return `${minutes}分钟前`
  if (hours < 24) return `${hours}小时前`
  if (days < 7) return `${days}天前`
  
  return date.toLocaleDateString('zh-CN', { month: 'short', day: 'numeric' })
}

// 获取状态文本
const getStatusText = (status: string) => {
  const statusMap: Record<string, string> = {
    active: '进行中',
    closed: '已关闭',
    pending: '待处理'
  }
  return statusMap[status] || status
}

// 打开聊天窗口
const handleOpenChat = (conversation: any) => {
  openChat(conversation)
}
</script>

<style scoped>
/* 淡入淡出动画 */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* 缩放动画 */
.scale-enter-active,
.scale-leave-active {
  transition: all 0.3s ease;
}

.scale-enter-from,
.scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* 自定义滚动条 */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
