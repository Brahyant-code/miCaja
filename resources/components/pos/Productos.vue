<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../api.js'
import { urlImagen } from '../../utils/imagen.js'
import { formatearCLP } from '../../utils/dinero.js'
import { parseCSV, toCSV } from '../../utils/csv.js'
import { confirmar, toastExito, alertaError } from '../../utils/alerta.js'
import { logout } from '../../utils/auth.js'
import { UMBRAL_STOCK_BAJO } from '../../utils/config.js'
import Swal from 'sweetalert2'
import { oscuro } from '../../theme.js'
import {
  PlusIcon, PencilSquareIcon, TrashIcon, PhotoIcon,
  ArrowUpTrayIcon, CheckIcon, XMarkIcon, CubeIcon, TableCellsIcon,
  MagnifyingGlassIcon, ArrowDownTrayIcon, ArrowUpCircleIcon, ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const API = import.meta.env.VITE_API_URL ?? '.' // mismo origen que api.js

const productos = ref([])
const categorias = ref([])
const cargando = ref(true)
const errorGeneral = ref(null)
const busqueda = ref('')
const soloStockBajo = ref(false)

// Nivel de stock de un producto: 'agotado' | 'bajo' | 'ok'.
function nivelStock(p) {
  const s = Number(p.stock)
  if (s <= 0) return 'agotado'
  if (s <= UMBRAL_STOCK_BAJO) return 'bajo'
  return 'ok'
}

// Cantidad de productos activos en alerta (stock <= umbral).
const conteoStockBajo = computed(
  () => productos.value.filter((p) => Number(p.activo) === 1 && Number(p.stock) <= UMBRAL_STOCK_BAJO).length
)

// Filtra por texto y, opcionalmente, solo los de stock bajo.
const productosFiltrados = computed(() => {
  const q = busqueda.value.trim().toLowerCase()
  return productos.value.filter((p) => {
    if (soloStockBajo.value && Number(p.stock) > UMBRAL_STOCK_BAJO) return false
    if (!q) return true
    return [p.nombre, p.categoria_nombre, p.descripcion]
      .filter(Boolean)
      .some((campo) => String(campo).toLowerCase().includes(q))
  })
})

const formVacio = () => ({ id: null, nombre: '', descripcion: '', precio: '', stock: '', categoria_id: '', activo: 1 })
const form = ref(formVacio())
const errores = ref({})
const guardando = ref(false)
const mostrarForm = ref(false)

const productoParaImagen = ref(null)
const imagenArchivo = ref(null)
const imagenPreview = ref(null)

// --- importación CSV ---
const mostrarImport = ref(false)
const importFilas = ref([])
const importColumnas = ref([])
const importando = ref(false)
const importResultado = ref(null)
const importError = ref(null)

async function cargar() {
  cargando.value = true
  errorGeneral.value = null
  try {
    const [rp, rc] = await Promise.all([api.get('productos'), api.get('categorias')])
    productos.value = rp.datos
    categorias.value = rc.datos
  } catch (e) {
    errorGeneral.value = 'No se pudieron cargar los productos.'
  } finally {
    cargando.value = false
  }
}

function nuevo() {
  form.value = formVacio()
  errores.value = {}
  productoParaImagen.value = null
  imagenArchivo.value = null
  mostrarForm.value = true
}

function editar(p) {
  form.value = {
    id: p.id,
    nombre: p.nombre,
    descripcion: p.descripcion ?? '',
    precio: p.precio,
    stock: p.stock,
    categoria_id: p.categoria_id,
    activo: Number(p.activo),
  }
  errores.value = {}
  productoParaImagen.value = p
  imagenArchivo.value = null
  mostrarForm.value = true
}

function cerrarForm() {
  mostrarForm.value = false
  form.value = formVacio()
  errores.value = {}
  productoParaImagen.value = null
  imagenArchivo.value = null
  if (imagenPreview.value) {
    URL.revokeObjectURL(imagenPreview.value)
  }
  imagenPreview.value = null
}

function seleccionarImagen(evento) {
  const archivo = evento.target.files?.[0] ?? null
  imagenArchivo.value = archivo

  if (imagenPreview.value) {
    URL.revokeObjectURL(imagenPreview.value)
    imagenPreview.value = null
  }

  if (archivo) {
    imagenPreview.value = URL.createObjectURL(archivo)
  }
}

function construirFormData(cuerpo) {
  const fd = new FormData()
  Object.entries(cuerpo).forEach(([clave, valor]) => {
    if (valor === null || valor === undefined) {
      fd.append(clave, '')
    } else {
      fd.append(clave, String(valor))
    }
  })
  if (imagenArchivo.value) {
    fd.append('imagen', imagenArchivo.value)
  }
  return fd
}

async function crearProductoConImagen(cuerpo) {
  const fd = construirFormData(cuerpo)
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('miCaja_token') : null
  const headers = {}
  if (token) {
    headers['Authorization'] = 'Bearer ' + token
  }

  const r = await fetch(`${API}/productos`, {
    method: 'POST',
    headers,
    body: fd,
  })

  const data = await r.json().catch(() => ({}))
  if (!r.ok) {
    if (r.status === 401) {
      logout()
    }
    throw Object.assign(new Error('HTTP ' + r.status), { status: r.status, data })
  }
  return data
}

async function subirImagenDirecta(id) {
  const fd = new FormData()
  fd.append('imagen', imagenArchivo.value)
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('miCaja_token') : null
  const headers = {}
  if (token) {
    headers['Authorization'] = 'Bearer ' + token
  }

  const r = await fetch(`${API}/productos/${id}/imagen`, {
    method: 'POST',
    headers,
    body: fd,
  })

  const data = await r.json().catch(() => ({}))
  if (!r.ok) {
    if (r.status === 401) {
      logout()
    }
    throw Object.assign(new Error('HTTP ' + r.status), { status: r.status, data })
  }
  return data
}

async function guardar() {
  errores.value = {}
  guardando.value = true
  const esEdicion = Boolean(form.value.id)
  const cuerpo = {
    nombre: form.value.nombre,
    descripcion: form.value.descripcion || null,
    precio: Number(form.value.precio),       // siempre número para el API
    stock: Number(form.value.stock || 0),
    categoria_id: Number(form.value.categoria_id),
    activo: Number(form.value.activo),
  }
  try {
    let resp
    if (!esEdicion && imagenArchivo.value) {
      resp = await crearProductoConImagen(cuerpo)
    } else if (esEdicion) {
      resp = await api.put(`productos/${form.value.id}`, cuerpo)
      if (imagenArchivo.value) {
        resp = await subirImagenDirecta(form.value.id)
      }
    } else {
      resp = await api.post('productos', cuerpo)
    }
    productoParaImagen.value = resp.datos
    form.value.id = resp.datos.id
    imagenArchivo.value = null
    toastExito(esEdicion ? 'Producto guardado' : 'Producto creado')
    if (!esEdicion) {
      cerrarForm()
    }
    await cargar()
  } catch (e) {
    if (e.status === 422) errores.value = e.data.errores ?? {}
    else alertaError('No se pudo guardar el producto.')
  } finally {
    guardando.value = false
  }
}

async function eliminar(p) {
  const ok = await confirmar(
    `Vas a eliminar "${p.nombre}". Si ya fue vendido, mejor desactívalo en su lugar.`,
    'Eliminar producto',
    'Sí, eliminar'
  )
  if (!ok) return
  try {
    await api.del(`productos/${p.id}`)
    toastExito('Producto eliminado')
    await cargar()
  } catch (e) {
    alertaError('No se pudo eliminar. ¿El producto ya tiene ventas registradas? En ese caso desactívalo.')
  }
}

// Descarga una plantilla CSV de ejemplo para que el usuario la complete y la suba.
// Usamos punto y coma (;) como separador porque Excel en español lo interpreta
// como separador de columnas (con coma quedaría todo en una sola columna).
function descargarPlantilla() {
  const contenido =
    'nombre;descripcion;precio;stock;categoria\n' +
    'Combo Familiar;8 presas + papas + bebida;12990;20;Combos\n' +
    'Bebida 1.5L;Bebida gaseosa;2500;50;Bebidas\n'
  const blob = new Blob(['﻿' + contenido], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'plantilla_productos.csv'
  a.click()
  URL.revokeObjectURL(url)
}


// ---- IMPORTACIÓN CSV ----
function abrirImport() {
  mostrarImport.value = true
  importFilas.value = []
  importColumnas.value = []
  importResultado.value = null
  importError.value = null
}

function cerrarImport() {
  mostrarImport.value = false
}

function onArchivoCSV(evento) {
  const archivo = evento.target.files?.[0]
  if (!archivo) return
  importResultado.value = null
  importError.value = null
  const lector = new FileReader()
  lector.onload = () => {
    const { columnas, filas } = parseCSV(String(lector.result))
    if (!columnas.includes('nombre') || !columnas.includes('precio')) {
      importError.value = 'El CSV debe tener al menos las columnas "nombre" y "precio".'
      importFilas.value = []
      return
    }
    importColumnas.value = columnas
    importFilas.value = filas
  }
  lector.readAsText(archivo, 'UTF-8')
  evento.target.value = ''
}

async function confirmarImport() {
  if (importFilas.value.length === 0) return
  importando.value = true
  importError.value = null
  try {
    const resp = await api.post('productos/importar', { productos: importFilas.value })
    importResultado.value = resp.datos
    await cargar()
  } catch (e) {
    importError.value = e.data?.mensaje ?? 'No se pudo importar el archivo.'
  } finally {
    importando.value = false
  }
}

onMounted(cargar)
</script>

<template>
  <div class="h-full overflow-auto p-4 sm:p-6 max-w-5xl mx-auto w-full">
    <header class="flex items-center gap-2 mb-6">
      <CubeIcon class="w-6 h-6 text-amber-500" />
      <h1 class="text-2xl font-bold flex-1">Productos</h1>
      <button
        @click="abrirImport"
        class="flex items-center gap-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 font-medium px-4 py-2 rounded-lg transition"
      >
        <TableCellsIcon class="w-5 h-5" /> Importar CSV
      </button>
      <button
        @click="nuevo"
        class="flex items-center gap-1 bg-gradient-to-r from-amber-500 to-red-500 hover:opacity-90 text-white font-medium px-4 py-2 rounded-lg transition"
      >
        <PlusIcon class="w-5 h-5" /> Nuevo producto
      </button>
    </header>

    <!-- Buscador -->
    <div class="relative mb-4">
      <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
      <input
        v-model="busqueda"
        type="search"
        placeholder="Buscar por nombre, categoría o descripción…"
        class="w-full pl-10 pr-3 py-2.5 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
      />
    </div>

    <div v-if="errorGeneral" class="mb-4 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg p-3 text-sm">
      {{ errorGeneral }}
    </div>

    <!-- Tabla -->
    <div v-if="cargando" class="text-gray-400 animate-pulse py-8 text-center">Cargando…</div>
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-left">
          <tr>
            <th class="p-3 font-medium">Imagen</th>
            <th class="p-3 font-medium">Nombre</th>
            <th class="p-3 font-medium hidden sm:table-cell">Categoría</th>
            <th class="p-3 font-medium text-right">Precio</th>
            <th class="p-3 font-medium text-right">Stock</th>
            <th class="p-3 font-medium">Estado</th>
            <th class="p-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr v-for="p in productosFiltrados" :key="p.id">
            <td class="p-3">
              <img v-if="p.imagen_url" :src="urlImagen(p.imagen_url)" :alt="p.nombre" class="w-12 h-12 rounded object-cover" />
              <PhotoIcon v-else class="w-12 h-12 p-2 text-gray-300 dark:text-gray-600" />
            </td>
            <td class="p-3 font-medium">{{ p.nombre }}</td>
            <td class="p-3 hidden sm:table-cell text-gray-500 dark:text-gray-400">{{ p.categoria_nombre }}</td>
            <td class="p-3 text-right font-semibold text-amber-600 dark:text-amber-400">{{ formatearCLP(p.precio) }}</td>
            <td class="p-3 text-right" :class="Number(p.stock) <= 0 ? 'text-red-500' : ''">{{ p.stock }}</td>
            <td class="p-3">
              <span
                class="text-xs px-2 py-0.5 rounded-full"
                :class="Number(p.activo) === 1
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                  : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'"
              >{{ Number(p.activo) === 1 ? 'Activo' : 'Inactivo' }}</span>
            </td>
            <td class="p-3 text-right whitespace-nowrap">
              <button @click="editar(p)" class="p-1.5 text-gray-500 hover:text-amber-600 transition" title="Editar">
                <PencilSquareIcon class="w-5 h-5" />
              </button>
              <button @click="eliminar(p)" class="p-1.5 text-red-500 hover:text-red-700 transition" title="Eliminar">
                <TrashIcon class="w-5 h-5" />
              </button>
            </td>
          </tr>
          <tr v-if="productosFiltrados.length === 0">
            <td colspan="7" class="p-6 text-center text-sm text-gray-400">
              {{ busqueda ? 'Sin resultados para tu búsqueda.' : 'No hay productos. Crea el primero.' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal formulario -->
    <div v-if="mostrarForm" class="fixed inset-0 z-40 bg-black/40 flex items-center justify-center p-4" @click.self="cerrarForm">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
          <h2 class="font-bold text-lg">{{ form.id ? 'Editar producto' : 'Nuevo producto' }}</h2>
          <button @click="cerrarForm" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <form @submit.prevent="guardar" class="flex-1 min-h-0 flex flex-col">
          <!-- Cuerpo con scroll -->
          <div class="flex-1 min-h-0 overflow-auto p-4 space-y-3">
          <div>
            <label class="block text-sm font-medium mb-1">Nombre</label>
            <input v-model="form.nombre" type="text" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
            <ul v-if="errores.nombre" class="mt-1 text-xs text-red-600 dark:text-red-400 list-disc list-inside">
              <li v-for="(m, i) in errores.nombre" :key="i">{{ m }}</li>
            </ul>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Categoría</label>
            <select v-model="form.categoria_id" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option value="" disabled>Selecciona una categoría…</option>
              <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
            <ul v-if="errores.categoria_id" class="mt-1 text-xs text-red-600 dark:text-red-400 list-disc list-inside">
              <li v-for="(m, i) in errores.categoria_id" :key="i">{{ m }}</li>
            </ul>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Descripción</label>
            <textarea
              v-model="form.descripcion"
              rows="3"
              maxlength="1000"
              class="w-full resize-none border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
            ></textarea>
            <p
              class="mt-1 text-xs text-right"
              :class="(form.descripcion?.length || 0) >= 1000 ? 'text-red-500' : 'text-gray-400'"
            >
              {{ form.descripcion?.length || 0 }} / 1000
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium mb-1">Precio</label>
              <input v-model="form.precio" type="number" min="0" max="9999999" step="1" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
              <p class="text-xs text-gray-400 mt-1">Máx 9.999.999</p>
              <ul v-if="errores.precio" class="mt-1 text-xs text-red-600 dark:text-red-400 list-disc list-inside">
                <li v-for="(m, i) in errores.precio" :key="i">{{ m }}</li>
              </ul>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Stock</label>
              <input v-model="form.stock" type="number" min="0" max="9999" step="1" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
              <p class="text-xs text-gray-400 mt-1">Máx 9.999 unidades</p>
              <ul v-if="errores.stock" class="mt-1 text-xs text-red-600 dark:text-red-400 list-disc list-inside">
                <li v-for="(m, i) in errores.stock" :key="i">{{ m }}</li>
              </ul>
            </div>
          </div>

          <label class="flex items-center gap-2 text-sm">
            <input v-model="form.activo" type="checkbox" :true-value="1" :false-value="0" class="rounded" />
            Producto activo
          </label>

          <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
            <p class="text-sm font-medium mb-2">Imagen del producto</p>
            <div class="flex items-center gap-3">
              <img
                v-if="imagenPreview || (productoParaImagen && productoParaImagen.imagen_url)"
                :src="imagenPreview || urlImagen(productoParaImagen.imagen_url)"
                class="w-20 h-20 rounded-lg object-cover"
                alt="Imagen del producto"
              />
              <div v-else class="w-20 h-20 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <PhotoIcon class="w-8 h-8 text-gray-300 dark:text-gray-500" />
              </div>
              <label class="inline-flex items-center gap-2 cursor-pointer bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 px-3 py-2 rounded-lg text-sm transition">
                <ArrowUpTrayIcon class="w-5 h-5" />
                {{ imagenArchivo ? 'Cambiar imagen' : 'Seleccionar imagen' }}
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  class="hidden"
                  @change="seleccionarImagen"
                />
              </label>
            </div>
            <p class="text-xs text-gray-400 mt-1">JPG, PNG o WEBP. Máx 2 MB. Se guardará con el producto.</p>
          </div>

          </div>
          <!-- Footer fijo -->
          <div class="flex gap-2 p-4 border-t border-gray-100 dark:border-gray-700 shrink-0">
            <button type="submit" :disabled="guardando" class="flex-1 flex items-center justify-center gap-1 bg-amber-500 hover:bg-amber-600 text-white font-medium px-4 py-2.5 rounded-lg transition disabled:opacity-50">
              <CheckIcon class="w-5 h-5" />
              {{ guardando ? 'Guardando…' : (form.id ? 'Guardar cambios' : 'Crear producto') }}
            </button>
            <button type="button" @click="cerrarForm" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
              {{ productoParaImagen ? 'Listo' : 'Cancelar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal importar CSV -->
    <div v-if="mostrarImport" class="fixed inset-0 z-40 bg-black/40 flex items-center justify-center p-4" @click.self="cerrarImport">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
          <h2 class="font-bold text-lg">Importar productos desde CSV</h2>
          <button @click="cerrarImport" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="flex-1 min-h-0 overflow-auto p-4 space-y-4">
          <!-- Instrucciones -->
          <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg p-3 text-sm text-gray-600 dark:text-gray-300">
            <p class="font-medium mb-1">Formato esperado (primera fila = encabezados):</p>
            <code class="block bg-white dark:bg-gray-900 rounded px-2 py-1 text-xs overflow-x-auto">nombre;descripcion;precio;stock;categoria</code>
            <p class="mt-2 text-xs">
              Descarga la plantilla, complétala en Excel y vuelve a subirla. Columnas <b>nombre</b> y
              <b>precio</b> obligatorias; <b>categoria</b> debe coincidir con una existente (o usa
              <b>categoria_id</b>). Acepta separador <b>;</b> o <b>,</b>. Guárdala como <b>CSV UTF-8</b>.
            </p>
          </div>

          <!-- Plantilla + Selector de archivo -->
          <div class="flex flex-wrap gap-2">
            <button
              @click="descargarPlantilla"
              class="inline-flex items-center gap-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium px-4 py-2 rounded-lg text-sm transition"
            >
              <ArrowDownTrayIcon class="w-5 h-5" /> Descargar plantilla
            </button>
            <label class="inline-flex items-center gap-2 cursor-pointer bg-amber-500 hover:bg-amber-600 text-white font-medium px-4 py-2 rounded-lg text-sm transition">
              <ArrowUpTrayIcon class="w-5 h-5" /> Elegir archivo CSV
              <input type="file" accept=".csv,text/csv" class="hidden" @change="onArchivoCSV" />
            </label>
          </div>

          <p v-if="importError" class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg p-3 text-sm">
            {{ importError }}
          </p>

          <!-- Resultado de una importación realizada -->
          <div v-if="importResultado" class="bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-lg p-3 text-sm">
            <p class="font-medium">{{ importResultado.creados }} de {{ importResultado.total }} productos creados.</p>
            <ul v-if="importResultado.errores.length" class="mt-2 text-amber-700 dark:text-amber-400 list-disc list-inside text-xs">
              <li v-for="(er, i) in importResultado.errores" :key="i">Fila {{ er.fila }} ({{ er.nombre || 's/n' }}): {{ er.mensaje }}</li>
            </ul>
          </div>

          <!-- Vista previa -->
          <div v-if="importFilas.length && !importResultado">
            <p class="text-sm font-medium mb-2">Vista previa ({{ importFilas.length }} filas):</p>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-auto max-h-60">
              <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-left sticky top-0">
                  <tr>
                    <th v-for="col in importColumnas" :key="col" class="p-2 font-medium whitespace-nowrap">{{ col }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(f, i) in importFilas.slice(0, 50)" :key="i">
                    <td v-for="col in importColumnas" :key="col" class="p-2 whitespace-nowrap">{{ f[col] }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="importFilas.length > 50" class="text-xs text-gray-400 mt-1">Mostrando las primeras 50 filas.</p>
          </div>

          <!-- Acciones -->
          <div class="flex gap-2 pt-2">
            <button
              v-if="importFilas.length && !importResultado"
              @click="confirmarImport"
              :disabled="importando"
              class="flex-1 flex items-center justify-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg transition disabled:opacity-50"
            >
              <CheckIcon class="w-5 h-5" />
              {{ importando ? 'Importando…' : `Importar ${importFilas.length} productos` }}
            </button>
            <button @click="cerrarImport" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
              {{ importResultado ? 'Cerrar' : 'Cancelar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
