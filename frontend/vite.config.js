import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  
  // build: {
  //   outDir: '../backend/public/frontend', // Собираем в Laravel public
  //   emptyOutDir: true,
  //   rollupOptions: {
  //     input: {
  //       main: path.resolve(__dirname, 'index.html')
  //     }
  //   }
  // },

//
  build: {
    outDir: '../backend/public/frontend',
    rollupOptions: {
      output: {
        // Убираем хеши из имен
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]'
      }
    }
  },


  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  }
})