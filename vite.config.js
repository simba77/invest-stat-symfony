import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import symfonyPlugin from "vite-plugin-symfony";

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
        app: './assets/app.ts',
      },
    },
  },
})
