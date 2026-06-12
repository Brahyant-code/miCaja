<script setup>
import { ref, onMounted } from 'vue'
import api from '../api.js'
import { ArrowPathIcon, ArrowDownIcon } from '@heroicons/vue/24/outline'

// Stack mostrado como badges en el hero.
const stack = ['PHP 7.4+', 'PDO', 'Vue 3', 'Tailwind v4', 'Vite', 'Composer PSR-4']

// --- Estado de conexión con el backend (reutiliza GET /home) ---
const datos = ref(null)
const cargando = ref(true)
const error = ref(false)

async function verificar() {
  cargando.value = true
  error.value = false
  try {
    const resp = await api.get('home')
    datos.value = resp.datos
  } catch (e) {
    error.value = true
    console.error(e)
  } finally {
    cargando.value = false
  }
}

onMounted(verificar)
</script>

<template>
  <section class="relative overflow-hidden">
    <!-- Resplandor de fondo decorativo -->
    <div
      class="pointer-events-none absolute inset-0 -z-10 opacity-60 dark:opacity-40"
      aria-hidden="true"
    >
      <div class="absolute -top-24 left-1/2 -translate-x-1/2 h-72 w-[36rem] rounded-full bg-gradient-to-r from-emerald-300 to-indigo-300 blur-3xl"></div>
    </div>

    <div class="max-w-5xl mx-auto px-4 pt-20 pb-16 text-center">
      <!-- Pill de estado del backend -->
      <div class="flex justify-center mb-8">
        <div
          v-if="cargando"
          class="inline-flex items-center gap-2 rounded-full bg-gray-100 dark:bg-gray-800 px-4 py-1.5 text-sm text-gray-500 dark:text-gray-400 animate-pulse"
        >
          <span class="h-2 w-2 rounded-full bg-gray-400"></span>
          Verificando backend…
        </div>
        <button
          v-else-if="error"
          @click="verificar"
          class="inline-flex items-center gap-2 rounded-full bg-red-50 dark:bg-red-900/30 px-4 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 transition"
        >
          <span class="h-2 w-2 rounded-full bg-red-500"></span>
          Backend sin conexión — reintentar
          <ArrowPathIcon class="w-4 h-4" />
        </button>
        <div
          v-else
          class="inline-flex items-center gap-2 rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-4 py-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-300"
        >
          <span class="relative flex h-2 w-2">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
          </span>
          Backend conectado
        </div>
      </div>

      <!-- Título con gradiente -->
      <h1 class="text-6xl sm:text-7xl font-extrabold tracking-tight">
        <span class="bg-gradient-to-r from-emerald-500 to-indigo-500 bg-clip-text text-transparent">
          Nova
        </span>
      </h1>
      <p class="mt-4 text-lg sm:text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
        Micro-framework MVC en PHP — ligero, explícito y fácil de entender.
        Backend de API JSON con un frontend <span class="font-semibold text-gray-800 dark:text-gray-100">Vue 3 + Tailwind</span> ya integrado.
      </p>

      <!-- Badges del stack -->
      <div class="mt-8 flex flex-wrap justify-center gap-2">
        <span
          v-for="t in stack"
          :key="t"
          class="rounded-full border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 px-3 py-1 text-sm font-medium text-gray-700 dark:text-gray-300"
        >
          {{ t }}
        </span>
      </div>

      <!-- Botones de acción -->
      <div class="mt-10 flex flex-wrap justify-center gap-3">
        <a
          href="#caracteristicas"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-indigo-500 px-6 py-3 font-semibold text-white shadow-lg shadow-emerald-500/20 hover:opacity-90 transition"
        >
          Ver características
          <ArrowDownIcon class="w-5 h-5" />
        </a>
        <a
          href="https://github.com/Brahyant-code/nova"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-3 font-semibold text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
        >
          <!-- Logo de GitHub (SVG inline; Heroicons no trae logos de marca) -->
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 .5C5.73.5.5 5.73.5 12.04c0 5.1 3.29 9.41 7.86 10.94.58.1.79-.25.79-.56v-2c-3.2.7-3.88-1.54-3.88-1.54-.53-1.34-1.29-1.7-1.29-1.7-1.05-.72.08-.71.08-.71 1.16.08 1.77 1.2 1.77 1.2 1.03 1.77 2.7 1.26 3.36.96.1-.75.4-1.26.73-1.55-2.56-.29-5.25-1.28-5.25-5.69 0-1.26.45-2.28 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.18 1.18a11.1 11.1 0 0 1 5.8 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.81 1.19 1.83 1.19 3.09 0 4.42-2.69 5.39-5.26 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.55 11.55 0 0 0 23.5 12.04C23.5 5.73 18.27.5 12 .5Z" />
          </svg>
          GitHub
        </a>
      </div>

      <!-- Dato del backend (autor / framework) cuando conecta -->
      <p v-if="datos" class="mt-8 text-sm text-gray-400 dark:text-gray-500">
        {{ datos.framework }} · por
        <span class="font-semibold text-gray-500 dark:text-gray-400">{{ datos.autor }}</span>
      </p>
    </div>
  </section>
</template>
