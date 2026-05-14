import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig(({ mode }) => ({
  plugins: [
    vue(),
    ...(mode === 'production' ? [] : [vueDevTools()]),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    sourcemap: false,
    minify: 'esbuild',
    rollupOptions: {
      output: {
        manualChunks: {
          vue: ['vue'],
          bootstrap: ['bootstrap', '@popperjs/core'],
        },
      },
    },
  },
  esbuild: {
    drop: mode === 'production' ? ['debugger'] : [],
    pure: mode === 'production' ? ['console.log', 'console.debug', 'console.info'] : [],
  },
}))
