<script setup>
import { ref, computed, onMounted, onActivated } from 'vue'
import api from '../../api.js'
import { urlImagen } from '../../utils/imagen.js'
import { formatearCLP } from '../../utils/dinero.js'
import { alertaError } from '../../utils/alerta.js'
import {
  PlusIcon, MinusIcon, TrashIcon, BanknotesIcon, PhotoIcon, CheckIcon,
} from '@heroicons/vue/24/outline'

const productos = ref([])
const categorias = ref([])
const categoriaFiltro = ref(null) // null = todas
const cargando = ref(true)
const errorGeneral = ref(null)

// --- carrito ---
const carrito = ref([])

// --- pago ---
const metodoPago = ref('efectivo')
const montoPagado = ref('')
const cliente = ref('')
const cajero = ref('')
const nota = ref('')
const cobrando = ref(false)
const ultimaVenta = ref(null)
const erroresCobro = ref({})

async function cargar() {
  cargando.value = true
  errorGeneral.value = null
  try {
    const [rp, rc] = await Promise.all([
      api.get('productos?activo=1'),
      api.get('categorias?activo=1'),
    ])
    productos.value = rp.datos
    categorias.value = rc.datos
  } catch (e) {
    errorGeneral.value = 'No se pudieron cargar los productos.'
  } finally {
    cargando.value = false
  }
}

const productosFiltrados = computed(() =>
  productos.value.filter(
    (p) => categoriaFiltro.value === null || Number(p.categoria_id) === categoriaFiltro.value
  )
)

const total = computed(() =>
  carrito.value.reduce((s, it) => s + it.precio * it.cantidad, 0)
)

const vuelto = computed(() => {
  const pagado = Number(montoPagado.value)
  if (!pagado || pagado < total.value) return 0
  return pagado - total.value
})

const puedeCobrar = computed(
  () =>
    carrito.value.length > 0 &&
    (metodoPago.value !== 'efectivo' || Number(montoPagado.value) >= total.value) &&
    !cobrando.value
)

// Cantidad de un producto que ya está en el carrito (0 si no está).
function cantidadEnCarrito(id) {
  const it = carrito.value.find((x) => x.producto_id === id)
  return it ? it.cantidad : 0
}

function agregarItem(p) {
  if (Number(p.stock) <= 0) return
  const ex = carrito.value.find((it) => it.producto_id === p.id)
  if (ex) {
    if (ex.cantidad < ex.stock) ex.cantidad++
  } else {
    carrito.value.push({
      producto_id: p.id,
      nombre: p.nombre,
      precio: Number(p.precio),
      cantidad: 1,
      stock: Number(p.stock),
      imagen_url: p.imagen_url,
    })
  }
}

function cambiarCantidad(it, delta) {
  const nueva = it.cantidad + delta
  if (nueva <= 0) {
    quitarItem(it)
    return
  }
  if (nueva > it.stock) return
  it.cantidad = nueva
}

function quitarItem(it) {
  carrito.value = carrito.value.filter((x) => x.producto_id !== it.producto_id)
}

function limpiarCarrito() {
  carrito.value = []
  montoPagado.value = ''
  cliente.value = ''
  nota.value = ''
  erroresCobro.value = {}
}

async function cobrar() {
  erroresCobro.value = {}
  cobrando.value = true
  try {
    const resp = await api.post('ventas', {
      metodo_pago: metodoPago.value,
      monto_pagado: Number(montoPagado.value) || total.value,
      cliente: cliente.value || null,
      cajero: cajero.value || null,
      nota: nota.value || null,
      items: carrito.value.map((it) => ({ producto_id: it.producto_id, cantidad: it.cantidad })),
    })
    ultimaVenta.value = resp.datos
    limpiarCarrito()
    await cargar() // refresca stock tras la venta
  } catch (e) {
    if (e.status === 422) erroresCobro.value = e.data.errores ?? {}
    else if (e.status === 409) alertaError(e.data?.mensaje ?? 'No se pudo registrar la venta.', 'No se pudo cobrar')
    else alertaError('No se pudo registrar la venta.', 'No se pudo cobrar')
  } finally {
    cobrando.value = false
  }
}

onMounted(cargar)
// Con <KeepAlive>, al volver a la vista de Caja refrescamos stock/precios.
onActivated(cargar)
</script>

<template>
  <div class="h-full flex flex-col lg:flex-row">
    <!-- IZQUIERDA: catálogo -->
    <section class="flex-1 min-w-0 p-4 overflow-auto">
      <h1 class="text-2xl font-bold mb-4">Caja</h1>

      <!-- Filtro de categorías -->
      <div class="flex gap-2 flex-wrap mb-4">
        <button
          @click="categoriaFiltro = null"
          class="px-3 py-1.5 rounded-full text-sm font-medium transition"
          :class="categoriaFiltro === null ? 'bg-amber-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'"
        >Todas</button>
        <button
          v-for="c in categorias"
          :key="c.id"
          @click="categoriaFiltro = c.id"
          class="px-3 py-1.5 rounded-full text-sm font-medium transition"
          :class="categoriaFiltro === c.id ? 'bg-amber-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'"
        >{{ c.nombre }}</button>
      </div>

      <div v-if="cargando" class="text-gray-400 animate-pulse py-8 text-center">Cargando…</div>
      <div v-else-if="errorGeneral" class="bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg p-3 text-sm">
        {{ errorGeneral }}
      </div>

      <!-- Grilla de productos -->
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
        <button
          v-for="p in productosFiltrados"
          :key="p.id"
          @click="agregarItem(p)"
          :disabled="Number(p.stock) <= 0"
          class="relative text-left bg-white dark:bg-gray-800 rounded-xl border overflow-hidden transition hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed"
          :class="cantidadEnCarrito(p.id) > 0
            ? 'border-amber-500 ring-2 ring-amber-400/60'
            : 'border-gray-100 dark:border-gray-700'"
        >
          <!-- Insignia de seleccionado: check + cantidad en el carrito -->
          <span
            v-if="cantidadEnCarrito(p.id) > 0"
            class="absolute top-1.5 right-1.5 z-10 flex items-center gap-0.5 bg-amber-500 text-white text-xs font-bold pl-1 pr-1.5 py-0.5 rounded-full shadow"
          >
            <CheckIcon class="w-3.5 h-3.5" />{{ cantidadEnCarrito(p.id) }}
          </span>
          <div class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
            <img v-if="p.imagen_url" :src="urlImagen(p.imagen_url)" :alt="p.nombre" class="w-full h-full object-cover" />
            <PhotoIcon v-else class="w-10 h-10 text-gray-300 dark:text-gray-500" />
          </div>
          <div class="p-2">
            <p class="font-medium text-sm truncate">{{ p.nombre }}</p>
            <p class="text-amber-600 dark:text-amber-400 font-semibold text-sm">{{ formatearCLP(p.precio) }}</p>
            <p class="text-xs" :class="Number(p.stock) <= 0 ? 'text-red-500' : 'text-gray-400'">
              {{ Number(p.stock) <= 0 ? 'Sin stock' : 'Stock: ' + p.stock }}
            </p>
          </div>
        </button>
        <p v-if="productosFiltrados.length === 0" class="col-span-full text-center text-sm text-gray-400 py-8">
          No hay productos en esta categoría.
        </p>
      </div>
    </section>

    <!-- DERECHA: carrito + pago -->
    <aside class="w-full lg:w-96 shrink-0 border-t lg:border-t-0 lg:border-l border-gray-200/60 dark:border-gray-800/60 bg-white dark:bg-gray-800 flex flex-col max-h-[45vh] lg:max-h-none">
      <h2 class="px-4 h-14 flex items-center font-bold border-b border-gray-100 dark:border-gray-700 shrink-0">Pedido actual</h2>

      <!-- Líneas del carrito -->
      <ul class="flex-1 overflow-auto divide-y divide-gray-100 dark:divide-gray-700">
        <li v-for="it in carrito" :key="it.producto_id" class="p-3 flex items-center gap-2">
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium truncate">{{ it.nombre }}</p>
            <p class="text-xs text-gray-400">{{ formatearCLP(it.precio) }} c/u</p>
          </div>
          <div class="flex items-center gap-1">
            <button @click="cambiarCantidad(it, -1)" class="p-1 rounded bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
              <MinusIcon class="w-4 h-4" />
            </button>
            <span class="w-7 text-center text-sm font-semibold">{{ it.cantidad }}</span>
            <button @click="cambiarCantidad(it, 1)" :disabled="it.cantidad >= it.stock" class="p-1 rounded bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition disabled:opacity-40">
              <PlusIcon class="w-4 h-4" />
            </button>
          </div>
          <span class="w-20 text-right text-sm font-semibold">{{ formatearCLP(it.precio * it.cantidad) }}</span>
          <button @click="quitarItem(it)" class="text-red-500 hover:text-red-700 p-1 transition">
            <TrashIcon class="w-4 h-4" />
          </button>
        </li>
        <li v-if="carrito.length === 0" class="p-6 text-center text-sm text-gray-400">Toca un producto para agregarlo.</li>
      </ul>

      <!-- Pago -->
      <div class="border-t border-gray-100 dark:border-gray-700 p-4 space-y-3 shrink-0">
        <div class="flex justify-between text-lg font-bold">
          <span>Total</span><span>{{ formatearCLP(total) }}</span>
        </div>

        <select v-model="metodoPago" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
          <option value="efectivo">Efectivo</option>
          <option value="tarjeta">Tarjeta</option>
          <option value="transferencia">Transferencia</option>
        </select>

        <div v-if="metodoPago === 'efectivo'">
          <input v-model="montoPagado" type="number" min="0" inputmode="numeric" placeholder="Monto pagado" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
          <p v-if="vuelto > 0" class="mt-1 text-sm text-emerald-600 dark:text-emerald-400 font-medium">
            Vuelto: {{ formatearCLP(vuelto) }}
          </p>
        </div>

        <input v-model="cliente" type="text" placeholder="Cliente (opcional)" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
        <input v-model="cajero" type="text" placeholder="Cajero (opcional)" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />
        <input v-model="nota" type="text" placeholder="Nota (opcional)" class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400" />

        <ul v-if="Object.keys(erroresCobro).length" class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
          <li v-for="(msgs, campo) in erroresCobro" :key="campo">{{ msgs[0] }}</li>
        </ul>

        <button @click="cobrar" :disabled="!puedeCobrar" class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 to-red-500 hover:opacity-90 text-white font-semibold px-4 py-3 rounded-lg transition disabled:opacity-40 disabled:cursor-not-allowed">
          <BanknotesIcon class="w-5 h-5" />
          {{ cobrando ? 'Cobrando…' : 'Cobrar' }}
        </button>
      </div>
    </aside>

    <!-- Modal de venta exitosa con el vuelto -->
    <div v-if="ultimaVenta" class="fixed inset-0 z-40 bg-black/40 flex items-center justify-center p-4" @click.self="ultimaVenta = null">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 max-w-sm w-full text-center">
        <h3 class="text-xl font-bold mb-1">Venta registrada ✅</h3>
        <p class="text-gray-500 dark:text-gray-400">Venta #{{ ultimaVenta.id }} · Total {{ formatearCLP(ultimaVenta.total) }}</p>
        <p v-if="Number(ultimaVenta.vuelto) > 0" class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400 my-4">
          Vuelto {{ formatearCLP(ultimaVenta.vuelto) }}
        </p>
        <button @click="ultimaVenta = null" class="mt-2 w-full bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 rounded-lg transition">
          Nuevo pedido
        </button>
      </div>
    </div>
  </div>
</template>
