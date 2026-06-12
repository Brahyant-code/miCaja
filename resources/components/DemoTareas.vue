<script setup>
import { ref, onMounted, nextTick } from 'vue'
import api from '../api.js'
import { PlusIcon, TrashIcon, PencilSquareIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const tareas = ref([])
const titulo = ref('')
const cargando = ref(true)
const errorGeneral = ref(null)   // p. ej. si falta la tabla en la BD
const erroresValidacion = ref({}) // errores de validación del backend (422) al crear

// --- Estado de la edición en línea ---
const editandoId = ref(null)       // id de la tarea que se está editando (null = ninguna)
const tituloEditado = ref('')      // texto del input de edición
const erroresEdicion = ref({})     // errores de validación (422) al editar
const inputEdicion = ref(null)     // referencia al <input> para enfocarlo

async function listar() {
  cargando.value = true
  errorGeneral.value = null
  try {
    const resp = await api.get('tareas')
    tareas.value = resp.datos
  } catch (e) {
    errorGeneral.value = 'No se pudo cargar. ¿Importaste database/nova.sql?'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

async function crear() {
  erroresValidacion.value = {}
  try {
    await api.post('tareas', { titulo: titulo.value })
    titulo.value = ''
    await listar()
  } catch (e) {
    // El backend responde 422 con { error, errores: { campo: [mensajes] } }
    if (e.status === 422) {
      erroresValidacion.value = e.data.errores ?? {}
    } else {
      errorGeneral.value = 'Ocurrió un error al crear la tarea.'
    }
  }
}

async function eliminar(id) {
  try {
    await api.del(`tareas/${id}`)
    await listar()
  } catch (e) {
    errorGeneral.value = 'No se pudo eliminar la tarea.'
  }
}

// PUT /tareas/{id}: marca o desmarca "completada" (manda el título actual, que es obligatorio).
async function alternarCompletada(t) {
  try {
    await api.put(`tareas/${t.id}`, {
      titulo: t.titulo,
      completada: t.completada == 1 ? 0 : 1,
    })
    await listar()
  } catch (e) {
    errorGeneral.value = 'No se pudo actualizar la tarea.'
  }
}

// Activa el modo edición sobre una tarea y enfoca el input.
async function iniciarEdicion(t) {
  editandoId.value = t.id
  tituloEditado.value = t.titulo
  erroresEdicion.value = {}
  await nextTick()
  // Un ref dentro de un v-for se recoge como array; tomamos el único visible.
  const el = Array.isArray(inputEdicion.value) ? inputEdicion.value[0] : inputEdicion.value
  el?.focus()
}

function cancelarEdicion() {
  editandoId.value = null
  tituloEditado.value = ''
  erroresEdicion.value = {}
}

// PUT /tareas/{id}: guarda el nuevo título (conserva "completada" enviándolo igual).
async function guardarEdicion(t) {
  erroresEdicion.value = {}
  try {
    await api.put(`tareas/${t.id}`, {
      titulo: tituloEditado.value,
      completada: t.completada,
    })
    cancelarEdicion()
    await listar()
  } catch (e) {
    if (e.status === 422) {
      erroresEdicion.value = e.data.errores ?? {}
    } else {
      errorGeneral.value = 'No se pudo guardar la tarea.'
    }
  }
}

onMounted(listar)
</script>

<template>
  <section class="max-w-3xl mx-auto px-4 py-20 scroll-mt-20">
    <div class="text-center mb-12">
      <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white">Demo en vivo</h2>
      <p class="mt-3 text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        Un CRUD de tareas real consumiendo el API: crea, edita, completa y elimina con validación incluida.
      </p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 transition-colors">
      <!-- Formulario de creación -->
      <form @submit.prevent="crear" class="mb-4">
        <div class="flex gap-2">
          <input
            v-model="titulo"
            type="text"
            placeholder="Nueva tarea…"
            class="flex-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400"
          />
          <button
            type="submit"
            class="flex items-center gap-1 bg-gradient-to-r from-emerald-500 to-indigo-500 hover:opacity-90 text-white font-semibold px-4 py-2 rounded-lg transition"
          >
            <PlusIcon class="w-5 h-5" />
            Agregar
          </button>
        </div>
        <!-- Errores de validación devueltos por el backend (422) -->
        <ul v-if="erroresValidacion.titulo" class="mt-2 text-sm text-red-600 list-disc list-inside">
          <li v-for="(msg, i) in erroresValidacion.titulo" :key="i">{{ msg }}</li>
        </ul>
      </form>

      <!-- Estados -->
      <div v-if="cargando" class="text-gray-400 animate-pulse py-2">Cargando…</div>
      <div v-else-if="errorGeneral" class="bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg p-3 text-sm">
        {{ errorGeneral }}
      </div>

      <!-- Lista de tareas -->
      <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <li v-for="t in tareas" :key="t.id" class="py-2">
          <!-- Modo edición en línea -->
          <div v-if="editandoId === t.id">
            <form @submit.prevent="guardarEdicion(t)" class="flex items-center gap-2">
              <input
                ref="inputEdicion"
                v-model="tituloEditado"
                type="text"
                @keyup.esc="cancelarEdicion"
                class="flex-1 border border-emerald-400 dark:border-emerald-500 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-400"
              />
              <button type="submit" class="text-emerald-600 hover:text-emerald-700 p-1.5 rounded transition" title="Guardar">
                <CheckIcon class="w-5 h-5" />
              </button>
              <button type="button" @click="cancelarEdicion" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1.5 rounded transition" title="Cancelar">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </form>
            <ul v-if="erroresEdicion.titulo" class="mt-1 text-sm text-red-600 list-disc list-inside">
              <li v-for="(msg, i) in erroresEdicion.titulo" :key="i">{{ msg }}</li>
            </ul>
          </div>

          <!-- Modo normal -->
          <div v-else class="flex items-center justify-between gap-2">
            <label class="flex items-center gap-2 cursor-pointer min-w-0">
              <input
                type="checkbox"
                :checked="t.completada == 1"
                @change="alternarCompletada(t)"
                class="h-4 w-4 shrink-0 rounded border-gray-300 dark:border-gray-600 text-emerald-500 focus:ring-emerald-400"
              />
              <span class="truncate" :class="t.completada == 1 ? 'line-through text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-100'">
                {{ t.titulo }}
              </span>
            </label>
            <div class="flex items-center shrink-0">
              <button
                @click="iniciarEdicion(t)"
                class="text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 p-1 rounded transition"
                title="Editar"
              >
                <PencilSquareIcon class="w-5 h-5" />
              </button>
              <button
                @click="eliminar(t.id)"
                class="text-red-500 hover:text-red-700 p-1 rounded transition"
                title="Eliminar"
              >
                <TrashIcon class="w-5 h-5" />
              </button>
            </div>
          </div>
        </li>
        <li v-if="tareas.length === 0" class="py-2 text-gray-400 dark:text-gray-500 text-sm">
          No hay tareas todavía.
        </li>
      </ul>
    </div>
  </section>
</template>
