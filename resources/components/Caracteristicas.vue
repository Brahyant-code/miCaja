<script setup>
import {
  MapIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
  CircleStackIcon,
  CodeBracketIcon,
  GlobeAltIcon,
  MoonIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'

// Cada característica del framework con su icono y descripción (basadas en el README).
const caracteristicas = [
  {
    icono: MapIcon,
    titulo: 'Routing explícito',
    texto: 'Defines todas tus rutas en routes/api.php con su verbo HTTP y parámetros dinámicos {id}.',
  },
  {
    icono: ShieldCheckIcon,
    titulo: 'Validación de datos',
    texto: 'Request::validar() con reglas required, email, numeric, min, max. Si falla, responde 422 solo.',
  },
  {
    icono: ExclamationTriangleIcon,
    titulo: 'Errores centralizados',
    texto: 'Un ErrorHandler captura toda excepción, la registra en el log y responde JSON limpio.',
  },
  {
    icono: CircleStackIcon,
    titulo: 'Base de datos con PDO',
    texto: 'Conexión singleton y consultas seguras con prepared statements: find(), all(), query().',
  },
  {
    icono: CodeBracketIcon,
    titulo: 'Respuestas JSON estándar',
    texto: 'Todas las respuestas usan el mismo sobre: mensaje + datos (+ errores cuando aplica).',
  },
  {
    icono: GlobeAltIcon,
    titulo: 'CORS configurable',
    texto: 'Origen permitido y preflight OPTIONS resueltos desde config/app.php.',
  },
  {
    icono: MoonIcon,
    titulo: 'Dark mode incluido',
    texto: 'Tema claro/oscuro con botón, respeta el sistema y recuerda la elección sin parpadeo.',
  },
  {
    icono: SparklesIcon,
    titulo: 'Frontend integrado',
    texto: 'Vue 3 + Tailwind v4 compilado con Vite, con Heroicons y cliente API listos para usar.',
  },
]
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-20 scroll-mt-20">
    <div class="text-center mb-12">
      <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">
        Todo lo esencial, sin lo pesado
      </h2>
      <p class="mt-3 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        Nova trae lo justo para construir un API JSON limpio y mantenible.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="c in caracteristicas"
        :key="c.titulo"
        class="group rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition"
      >
        <div class="inline-flex items-center justify-center h-11 w-11 rounded-xl bg-gradient-to-br from-emerald-500/10 to-indigo-500/10 text-emerald-600 dark:text-emerald-400 mb-4">
          <component :is="c.icono" class="w-6 h-6" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ c.titulo }}</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ c.texto }}</p>
      </div>
    </div>
  </section>
</template>
