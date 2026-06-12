<script setup>
import Hero from './components/Hero.vue'
import Caracteristicas from './components/Caracteristicas.vue'
import InicioRapido from './components/InicioRapido.vue'
import DemoTareas from './components/DemoTareas.vue'
import PieDePagina from './components/PieDePagina.vue'
import { oscuro, alternarTema } from './theme.js'
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 transition-colors">
    <!-- Header sticky translúcido -->
    <header class="sticky top-0 z-30 backdrop-blur bg-white/70 dark:bg-gray-900/70 border-b border-gray-200/60 dark:border-gray-800/60">
      <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
        <a href="#top" class="text-xl font-extrabold tracking-tight">
          <span class="bg-gradient-to-r from-emerald-500 to-indigo-500 bg-clip-text text-transparent">Nova</span>
        </a>

        <nav class="flex items-center gap-1 sm:gap-2">
          <a href="#caracteristicas" class="hidden sm:inline-block px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            Características
          </a>
          <a href="#inicio" class="hidden sm:inline-block px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            Inicio rápido
          </a>
          <a href="#demo" class="hidden sm:inline-block px-3 py-1.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            Demo
          </a>
          <button
            @click="alternarTema"
            class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
            :title="oscuro ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          >
            <SunIcon v-if="oscuro" class="w-5 h-5" />
            <MoonIcon v-else class="w-5 h-5" />
          </button>
        </nav>
      </div>
    </header>

    <main id="top">
      <Hero />
      <Caracteristicas id="caracteristicas" />
      <InicioRapido id="inicio" />
      <DemoTareas id="demo" />
    </main>

    <PieDePagina />
  </div>
</template>
