<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { oscuro, alternarTema } from './theme.js'
import { esTokenValido, logout } from './utils/auth.js'
import {
  SunIcon, MoonIcon, ArrowLeftOnRectangleIcon, BuildingStorefrontIcon,
  ShoppingCartIcon, CubeIcon, TagIcon, ClipboardDocumentListIcon, Squares2X2Icon,
} from '@heroicons/vue/24/outline'
import Dashboard from './components/pos/Dashboard.vue'
import Caja from './components/pos/Caja.vue'
import Productos from './components/pos/Productos.vue'
import Categorias from './components/pos/Categorias.vue'
import Ventas from './components/pos/Ventas.vue'
import Login from './components/pos/Login.vue'

const vistas = [
  { id: 'panel',      nombre: 'Panel',      icono: Squares2X2Icon },
  { id: 'caja',       nombre: 'Caja',       icono: ShoppingCartIcon },
  { id: 'productos',  nombre: 'Productos',  icono: CubeIcon },
  { id: 'categorias', nombre: 'Categorías', icono: TagIcon },
  { id: 'ventas',     nombre: 'Ventas',     icono: ClipboardDocumentListIcon },
]

const componentes = { panel: Dashboard, caja: Caja, productos: Productos, categorias: Categorias, ventas: Ventas }

// Si no hay token válido, mostramos la pantalla de login en lugar de la app.
const estaAutenticado = esTokenValido()

// Navegación simple sincronizada con location.hash (sobrevive refresh + botón atrás).
const vistaActual = ref('caja')

function leerHash() {
  const h = location.hash.replace('#', '')
  vistaActual.value = componentes[h] ? h : 'caja'
}
function irA(id) {
  location.hash = id // dispara el evento hashchange -> leerHash()
}

onMounted(() => {
  if (estaAutenticado) {
    const hash = location.hash.replace('#', '')
    if (!hash || !componentes[hash]) {
      location.hash = '#panel'
    }
    leerHash()
    window.addEventListener('hashchange', leerHash)
  }
})
onUnmounted(() => window.removeEventListener('hashchange', leerHash))
</script>

<template>
  <div v-if="estaAutenticado" class="min-h-screen flex bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition-colors">
    <!-- Barra lateral -->
    <aside class="w-16 sm:w-56 shrink-0 border-r border-gray-200/60 dark:border-gray-800/60 bg-white/70 dark:bg-gray-900/70 backdrop-blur flex flex-col">
      <div class="h-16 flex items-center justify-center sm:justify-start sm:px-4 gap-2.5 border-b border-gray-100 dark:border-gray-800">
        <span class="shrink-0 w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-red-500 flex items-center justify-center shadow-md shadow-amber-500/30">
          <BuildingStorefrontIcon class="w-5 h-5 text-white" />
        </span>
        <span class="hidden sm:flex flex-col leading-none">
          <span class="font-extrabold tracking-tight text-lg">
            mi<span class="bg-gradient-to-r from-amber-500 to-red-500 bg-clip-text text-transparent">Caja</span>
          </span>
          <span class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500">Punto de venta</span>
        </span>
      </div>

      <nav class="flex-1 px-2 space-y-1">
        <button
          v-for="v in vistas"
          :key="v.id"
          @click="irA(v.id)"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
          :class="vistaActual === v.id
            ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
        >
          <component :is="v.icono" class="w-5 h-5 shrink-0" />
          <span class="hidden sm:inline">{{ v.nombre }}</span>
        </button>
      </nav>

      <button
        @click="alternarTema"
        class="m-2 p-2 rounded-lg flex items-center justify-center sm:justify-start gap-3 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
        :title="oscuro ? 'Modo claro' : 'Modo oscuro'"
      >
        <SunIcon v-if="oscuro" class="w-5 h-5" />
        <MoonIcon v-else class="w-5 h-5" />
        <span class="hidden sm:inline text-sm">{{ oscuro ? 'Claro' : 'Oscuro' }}</span>
      </button>

      <button
        @click="logout"
        class="m-2 p-2 rounded-lg flex items-center justify-center sm:justify-start gap-3 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
      >
        <ArrowLeftOnRectangleIcon class="w-5 h-5" />
        <span class="hidden sm:inline">Cerrar sesión</span>
      </button>
    </aside>

    <main class="flex-1 min-w-0 h-screen overflow-hidden">
      <KeepAlive>
        <component :is="componentes[vistaActual]" />
      </KeepAlive>
    </main>
  </div>

  <div v-else class="min-h-screen bg-slate-950 text-white flex items-center justify-center px-4">
    <Login />
  </div>
</template>
