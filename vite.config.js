import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from "vite-plugin-symfony";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    symfonyPlugin()
  ],
  publicDir: false,
  base: '/build/',
  build: {
    manifest: true,
    outDir: 'public/build/',
    rollupOptions: {
      input: {
        app: 'assets/app.ts',
      },
    },
  },
  server: {
    origin: 'http://127.0.0.1:5173',
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./assets"),
    },
  },
})
