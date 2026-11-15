import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Em desenvolvimento, /api vai para o PHP local (php -S localhost:8000 -t ../api ...),
// assim o front roda "no mesmo domínio" e os cookies de sessão funcionam como em produção.
// VITE_BASE=/atelie/ gera o build para rodar em subpasta (ver empacotar.sh).
export default defineConfig({
  base: process.env.VITE_BASE || '/',
  plugins: [react()],
  server: {
    port: 5173,
    proxy: { '/api': { target: 'http://localhost:8000', changeOrigin: false } },
  },
  build: { outDir: 'dist', sourcemap: false },
});
