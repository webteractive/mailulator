import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'node:path'
import fs from 'node:fs'

const HOT_FILE = path.resolve(__dirname, 'vendor/orchestra/testbench-core/laravel/public/vendor/mailulator/hot')
const HOT_DIR = path.dirname(HOT_FILE)

function mailulatorHotFile() {
  return {
    name: 'mailulator-hot-file',
    apply: 'serve',
    configureServer(server) {
      server.httpServer?.once('listening', () => {
        const address = server.httpServer.address()
        const protocol = server.config.server.https ? 'https' : 'http'
        const host = typeof address === 'string' ? address : `localhost:${address.port}`
        fs.mkdirSync(HOT_DIR, { recursive: true })
        fs.writeFileSync(HOT_FILE, `${protocol}://${host}`)
      })
      const cleanup = () => { try { fs.unlinkSync(HOT_FILE) } catch {} }
      process.on('exit', cleanup)
      process.on('SIGINT', () => { cleanup(); process.exit() })
      process.on('SIGTERM', () => { cleanup(); process.exit() })
      process.on('SIGHUP', () => { cleanup(); process.exit() })
    },
  }
}

export default defineConfig({
  plugins: [vue(), mailulatorHotFile()],
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://127.0.0.1:5173',
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
  build: {
    manifest: true,
    outDir: 'dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'resources/js/app.js'),
      },
      output: {
        assetFileNames: 'assets/[name]-[hash][extname]',
        chunkFileNames: 'assets/[name]-[hash].js',
        entryFileNames: 'assets/[name]-[hash].js',
      },
    },
  },
})
