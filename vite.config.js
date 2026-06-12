import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// Configuración de Vite para el frontend de Nova.
// - El código fuente vive en resources/
// - La SPA se compila a public/ (junto al index.php del backend, sin borrarlo)
// - base './' permite servir la app desde el subdirectorio /nova/
export default defineConfig({
  root: 'resources',
  // Los archivos .env (.env.development / .env.production) viven en la raíz
  // del proyecto, un nivel arriba de 'resources'. Sin esto, Vite los buscaría
  // dentro de resources/ y VITE_API_URL quedaría sin definir.
  envDir: '..',
  base: './',
  plugins: [vue(), tailwindcss()],
  build: {
    outDir: '../public',
    emptyOutDir: false, // NO borrar index.php ni .htaccess al compilar
  },
  server: {
    port: 5173,
  },
})
