<template>
  <div class="circular-progress-container">
    <svg :width="size" :height="size" class="circular-progress">
      <circle
        :cx="center"
        :cy="center"
        :r="radius"
        :stroke-width="strokeWidth"
        stroke="rgba(255, 255, 255, 0.1)"
        fill="none"
      />
      <circle
        :cx="center"
        :cy="center"
        :r="radius"
        :stroke-width="strokeWidth"
        :stroke="progressColor"
        fill="none"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="dashOffset"
        stroke-linecap="round"
        class="progress-ring"
      />
    </svg>
    <div class="progress-text">
      <span class="value">{{ displayValue }}</span>
      <span class="unit">%</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { PropType } from 'vue'

const props = defineProps({
  value: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  min: { type: Number, default: 0 },
  size: { type: Number, default: 80 },
  strokeWidth: { type: Number, default: 6 },
  color: { type: String as PropType<string>, default: '#6b73ff' }
})

const animatedValue = ref(props.min)

const center = computed(() => props.size / 2)
const radius = computed(() => (props.size - props.strokeWidth) / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)

const progress = computed(() => {
  const range = props.max - props.min
  if (range <= 0) {
    return 0
  }
  const normalized = Math.max(0, Math.min(range, animatedValue.value - props.min))
  return (normalized / range) * 100
})

const dashOffset = computed(() => circumference.value - (progress.value / 100) * circumference.value)

const displayValue = computed(() => Math.round(progress.value))

const progressColor = computed(() => {
  if (progress.value >= 75) return '#40ffaa'
  if (progress.value >= 50) return '#f7d060'
  if (progress.value >= 25) return '#6b73ff'
  return props.color
})

let frameId: number | null = null

const animateValue = (target: number) => {
  if (frameId !== null) {
    cancelAnimationFrame(frameId)
    frameId = null
  }

  const start = animatedValue.value
  const clampedTarget = Math.max(props.min, Math.min(props.max, target))
  const duration = 600
  const startTime = performance.now()

  const tick = (currentTime: number) => {
    const elapsed = currentTime - startTime
    const pct = Math.min(elapsed / duration, 1)
    const easeOutCubic = 1 - Math.pow(1 - pct, 3)
    animatedValue.value = start + (clampedTarget - start) * easeOutCubic

    if (pct < 1) {
      frameId = requestAnimationFrame(tick)
    } else {
      animatedValue.value = clampedTarget
      frameId = null
    }
  }

  frameId = requestAnimationFrame(tick)
}

watch(
  () => props.value,
  (newValue) => {
    animateValue(newValue)
  },
  { immediate: true }
)
</script>

<style scoped>
.circular-progress-container {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.circular-progress {
  transform: rotate(-90deg);
}

.progress-ring {
  transition: stroke-dashoffset 0.6s cubic-bezier(0.4, 0, 0.2, 1),
    stroke 0.3s ease;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: baseline;
  font-weight: 700;
  color: #fff;
  user-select: none;
}

.progress-text .value {
  font-size: 18px;
  line-height: 1;
}

.progress-text .unit {
  font-size: 12px;
  opacity: 0.8;
  margin-left: 2px;
}
</style>
