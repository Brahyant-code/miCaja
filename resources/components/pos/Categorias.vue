<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api.js'
import { confirmar, toastExito, alertaError } from '../../utils/alerta.js'
import {
  PlusIcon, PencilSquareIcon, TrashIcon, CheckIcon, XMarkIcon, TagIcon,
  MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline'

const categorias = ref([])
const cargando = ref(true)
const errorGeneral = ref(null)
const busqueda = ref('')

const categoriasFiltradas = computed(() => {
  const q = busqueda.value.trim().toLowerCase()
  if (!q) return categorias.value
  return categorias.value.filter((c) => c.nombre.toLowerCase().includes(q))
})

const form = ref({ id: null, nombre: '', activo: 1 })
const errores = ref({})
const guardando = ref(false)

async function listar() {
  cargando.value = true
  errorGeneral.value = null
  try {
    categorias.value = (await api.get('categorias')).datos
  } catch (e) {
    errorGeneral.value = 'No se pudieron cargar las categorías.'
  } finally {
    cargando.value = false
  }
}

async function guardar() {
  errores.value = {}
  guardando.value = true
  const cuerpo = { nombre: form.value.nombre, activo: Number(form.value.activo) }
  try {
    const editando = !!form.value.id
    if (editando) await api.put(`categorias/${form.value.id}`, cuerpo)
    else await api.post('categorias', cuerpo)
    cancelar()
    toastExito(editando ? 'Categoría actualizada' : 'Categoría creada')
    await listar()
  } catch (e) {
    if (e.status === 422) errores.value = e.data.errores ?? {}
    else alertaError('No se pudo guardar la categoría.')
  } finally {
    guardando.value = false
  }
}

function editar(c) {
  form.value = { id: c.id, nombre: c.nombre, activo: Number(c.activo) }
  errores.value = {}
}

function cancelar() {
  form.value = { id: null, nombre: '', activo: 1 }
  errores.value = {}
}

async function eliminar(c) {
  const ok = await confirmar(
    `Vas a eliminar "${c.nombre}". Si tiene productos asociados, mejor desactívala.`,
    'Eliminar categoría',
    'Sí, eliminar'
  )
  if (!ok) return
  try {
    await api.del(`categorias/${c.id}`)
    toastExito('Categoría eliminada')
    await listar()
  } catch (e) {
    alertaError('No se pudo eliminar. La categoría tiene productos asociados; desactívala en su lugar.')
  }
}

onMounted(listar)
</script>

<template>
  <div class="h-full overflow-auto p-4 sm:p-6 max-w-5xl mx-auto w-full">
    <header class="flex items-center gap-2 mb-6">
      <TagIcon class="w-6 h-6 text-amber-500" />
      <h1 class="text-2xl font-bold">Categorías</h1>
    </header>

    <!-- Formulario crear/editar -->
    <form @submit.prevent="guardar" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <input
            v-model="form.nombre"
            type="text"
            placeholder="Nombre de la categoría"
            class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
          />
          <ul v-if="errores.nombre" class="mt-1 text-xs text-red-600 dark:text-red-400 list-disc list-inside">
            <li v-for="(m, i) in errores.nombre" :key="i">{{ m }}</li>
          </ul>
        </div>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.activo" type="checkbox" :true-value="1" :false-value="0" class="rounded" />
          Activa
        </label>
        <button
          type="submit"
          :disabled="guardando"
          class="flex items-center justify-center gap-1 bg-amber-500 hover:bg-amber-600 text-white font-medium px-4 py-2 rounded-lg transition disabled:opacity-50"
        >
          <CheckIcon v-if="form.id" class="w-5 h-5" />
          <PlusIcon v-else class="w-5 h-5" />
          {{ form.id ? 'Guardar' : 'Agregar' }}
        </button>
        <button
          v-if="form.id"
          type="button"
          @click="cancelar"
          class="flex items-center justify-center gap-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 px-3 py-2 rounded-lg transition"
        >
          <XMarkIcon class="w-5 h-5" /> Cancelar
        </button>
      </div>
    </form>

    <!-- Buscador -->
    <div class="relative mb-4">
      <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
      <input
        v-model="busqueda"
        type="search"
        placeholder="Buscar categoría…"
        class="w-full pl-10 pr-3 py-2.5 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
      />
    </div>

    <div v-if="errorGeneral" class="mb-4 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg p-3 text-sm">
      {{ errorGeneral }}
    </div>

    <!-- Lista -->
    <div v-if="cargando" class="text-gray-400 animate-pulse py-8 text-center">Cargando…</div>
    <ul v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
      <li v-for="c in categoriasFiltradas" :key="c.id" class="flex items-center gap-3 p-3">
        <span class="flex-1 font-medium">{{ c.nombre }}</span>
        <span
          class="text-xs px-2 py-0.5 rounded-full"
          :class="Number(c.activo) === 1
            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
        >{{ Number(c.activo) === 1 ? 'Activa' : 'Inactiva' }}</span>
        <button @click="editar(c)" class="p-1.5 text-gray-500 hover:text-amber-600 transition" title="Editar">
          <PencilSquareIcon class="w-5 h-5" />
        </button>
        <button @click="eliminar(c)" class="p-1.5 text-red-500 hover:text-red-700 transition" title="Eliminar">
          <TrashIcon class="w-5 h-5" />
        </button>
      </li>
      <li v-if="categoriasFiltradas.length === 0" class="p-6 text-center text-sm text-gray-400">
        {{ busqueda ? 'Sin resultados para tu búsqueda.' : 'No hay categorías. Agrega la primera arriba.' }}
      </li>
    </ul>
  </div>
</template>
