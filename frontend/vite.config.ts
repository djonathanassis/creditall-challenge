import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const isProd = mode === 'production'

  return {
    plugins: [
      react(),
    ],

    // Path aliases
    resolve: {
      alias: {
        '@': resolve(__dirname, './src'),
        '@components': resolve(__dirname, './src/components'),
        '@pages': resolve(__dirname, './src/pages'),
        '@services': resolve(__dirname, './src/services'),
        '@utils': resolve(__dirname, './src/utils'),
        '@types': resolve(__dirname, './src/types'),
        '@hooks': resolve(__dirname, './src/hooks'),
        '@context': resolve(__dirname, './src/context'),
        '@assets': resolve(__dirname, './src/assets'),
      },
    },

    // Server configuration for Docker development
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true, 
      watch: {
        usePolling: true,
        interval: 1000,
        ignored: ['**/node_modules/**', '**/dist/**'],
      },
      hmr: {
        clientPort: 5173,
        overlay: true,
      },
    },

    // Preview server configuration
    preview: {
      host: '0.0.0.0',
      port: 4173,
      strictPort: true,
    },

    // Build optimizations
    build: {
      target: 'es2022',
      sourcemap: isProd ? 'hidden' : true,
      minify: 'esbuild', 
      reportCompressedSize: true,
      chunkSizeWarningLimit: 500,

      rollupOptions: {
        output: {
          // Strategic code splitting
          manualChunks: (id) => {
            if (id.includes('node_modules')) {
              if (id.includes('react') || id.includes('react-dom')) {
                return 'react-vendor'
              }
              if (id.includes('react-router')) {
                return 'router'
              }
              if (id.includes('axios')) {
                return 'http-client'
              }
              return 'vendor'
            }
            if (id.includes('/pages/')) {
              const pageName = id.split('/pages/')[1]?.split('/')[0]?.split('.')[0]
              return pageName ? `page-${pageName}` : 'pages'
            }
          },

          chunkFileNames: 'js/[name]-[hash].js',
          entryFileNames: 'js/[name]-[hash].js',
          assetFileNames: (assetInfo) => {
            if (!assetInfo.name) return 'assets/[name]-[hash][extname]'

            if (/\.css$/.test(assetInfo.name)) {
              return 'css/[name]-[hash][extname]'
            }
            if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico|webp|avif)$/i.test(assetInfo.name)) {
              return 'images/[name]-[hash][extname]'
            }
            if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
              return 'fonts/[name]-[hash][extname]'
            }
            return 'assets/[name]-[hash][extname]'
          },
        },
      },

      cssCodeSplit: true,
      assetsInlineLimit: 4096,
      modulePreload: {
        polyfill: true,
      },
    },

    // Development optimizations
    optimizeDeps: {
      include: ['react', 'react-dom', 'react-router-dom', 'axios'],
      exclude: ['fsevents'],
    },

    // Environment variables prefix
    envPrefix: 'VITE_',

    // CSS preprocessor options
    css: {
      devSourcemap: true,
      postcss: './postcss.config.js',
    },
  }
})
