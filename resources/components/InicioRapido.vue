<script setup>
import { CommandLineIcon } from '@heroicons/vue/24/outline'

// Endpoints del ejemplo incluido (CRUD de tareas).
const endpoints = [
  { metodo: 'GET',    ruta: '/tareas',      accion: 'Lista todas las tareas' },
  { metodo: 'GET',    ruta: '/tareas/{id}', accion: 'Muestra una tarea' },
  { metodo: 'POST',   ruta: '/tareas',      accion: 'Crea una tarea (valida título)' },
  { metodo: 'DELETE', ruta: '/tareas/{id}', accion: 'Elimina una tarea' },
]

const colorMetodo = {
  GET:    'text-emerald-600 dark:text-emerald-400',
  POST:   'text-indigo-600 dark:text-indigo-400',
  DELETE: 'text-red-600 dark:text-red-400',
}
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-20 scroll-mt-20">
    <div class="text-center mb-12">
      <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">Inicio rápido</h2>
      <p class="mt-3 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        De cero a un API funcionando en unos pocos comandos.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Bloque: instalación -->
      <div class="rounded-2xl overflow-hidden border border-gray-800 shadow-lg">
        <div class="flex items-center gap-2 bg-gray-800 px-4 py-2.5">
          <span class="h-3 w-3 rounded-full bg-red-400"></span>
          <span class="h-3 w-3 rounded-full bg-amber-400"></span>
          <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
          <span class="ml-2 flex items-center gap-1 text-xs font-medium text-gray-400">
            <CommandLineIcon class="w-4 h-4" /> instalación
          </span>
        </div>
        <pre v-pre class="bg-gray-900 text-gray-100 text-sm p-4 overflow-x-auto leading-relaxed"><code><span class="text-gray-500"># 1. Backend: autoload de Composer</span>
<span class="text-emerald-400">composer</span> install

<span class="text-gray-500"># 2. Configura tu base de datos</span>
<span class="text-emerald-400">cp</span> config/database.example.php config/database.php

<span class="text-gray-500"># 3. Frontend: dependencias y dev server</span>
<span class="text-emerald-400">npm</span> install
<span class="text-emerald-400">npm</span> run dev   <span class="text-gray-500"># http://localhost:5173</span></code></pre>
      </div>

      <!-- Bloque: ejemplo de código -->
      <div class="rounded-2xl overflow-hidden border border-gray-800 shadow-lg">
        <div class="flex items-center gap-2 bg-gray-800 px-4 py-2.5">
          <span class="h-3 w-3 rounded-full bg-red-400"></span>
          <span class="h-3 w-3 rounded-full bg-amber-400"></span>
          <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
          <span class="ml-2 text-xs font-medium text-gray-400">routes/api.php + Controller</span>
        </div>
        <pre v-pre class="bg-gray-900 text-gray-100 text-sm p-4 overflow-x-auto leading-relaxed"><code><span class="text-gray-500">// routes/api.php — define la ruta</span>
$router-&gt;<span class="text-indigo-400">get</span>(<span class="text-amber-300">'/tareas'</span>, <span class="text-amber-300">'TareaController@index'</span>);

<span class="text-gray-500">// app/Controllers/TareaController.php</span>
<span class="text-sky-400">class</span> <span class="text-emerald-400">TareaController</span> <span class="text-sky-400">extends</span> Controller {
    <span class="text-sky-400">public function</span> <span class="text-emerald-400">index</span>() {
        $this-&gt;<span class="text-indigo-400">exito</span>($this-&gt;tareas-&gt;<span class="text-indigo-400">listar</span>());
    }
}</code></pre>
      </div>
    </div>

    <!-- Tabla de endpoints del ejemplo -->
    <div class="mt-10 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
      <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
        <h3 class="font-semibold text-gray-900 dark:text-white">Endpoints del ejemplo</h3>
      </div>
      <table class="w-full text-sm">
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-for="e in endpoints" :key="e.metodo + e.ruta" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-5 py-3 w-24">
              <span :class="colorMetodo[e.metodo]" class="font-mono font-bold">{{ e.metodo }}</span>
            </td>
            <td class="px-5 py-3 font-mono text-gray-800 dark:text-gray-200">{{ e.ruta }}</td>
            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ e.accion }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
