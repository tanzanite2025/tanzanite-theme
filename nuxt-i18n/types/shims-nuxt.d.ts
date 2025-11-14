/* QuickBuyButton shim removed: component no longer used */

declare module '~/components/QuickBuyModal.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

declare module '~/components/WhatsAppModal.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

declare module '~/components/WhatsAppButton.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}


declare module '~/components/LanguageSwitcher.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

declare module '~/components/AppFooter.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

declare module '~/composables/useAuth' {
  const useAuth: () => any
  export { useAuth }
  export default useAuth
}
