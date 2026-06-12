<script setup>
// Año fijo: el entorno de Vite no necesita fecha dinámica para un footer informativo.
const anio = 2026
</script>

<template>
  <footer class="border-t border-gray-200 dark:border-gray-800 mt-10">
    <div class="max-w-5xl mx-auto px-4 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="text-center sm:text-left">
        <p class="font-bold text-lg">
          <span class="bg-gradient-to-r from-emerald-500 to-indigo-500 bg-clip-text text-transparent">Nova</span>
          <span class="text-gray-400 dark:text-gray-500 font-normal text-sm"> · Framework MVC en PHP</span>
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          PHP · PDO · Vue 3 · Tailwind v4 · Vite
        </p>
      </div>

      <div class="text-center sm:text-right text-sm text-gray-500 dark:text-gray-400">
        <p>
          Desarrollado por
          <a
            href="https://github.com/Brahyant-code"
            target="_blank"
            rel="noopener noreferrer"
            class="font-semibold text-gray-700 dark:text-gray-200 hover:text-emerald-600 dark:hover:text-emerald-400 transition"
          >
            @Brahyant-code
          </a>
        </p>
        <p class="mt-1">© {{ anio }} Nova Framework</p>
      </div>
    </div>
  </footer>
</template>
