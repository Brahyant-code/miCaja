<script setup>
import { ref, onMounted, onActivated } from 'vue'
import api from '../../api.js'
import { formatearCLP, formatearFecha } from '../../utils/dinero.js'
import {
  EyeIcon, ArrowLeftIcon, ClipboardDocumentListIcon, NoSymbolIcon,
} from '@heroicons/vue/24/outline'

const ventas = ref([])
const cargando = ref(true)
const errorGeneral = ref(null)

const ventaSeleccionada = ref(null) // detalle (cabecera + detalle[])
const cargandoDetalle = ref(false)

const badgeEstado = {
  completada: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
  anulada: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
  pendiente: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
}

async function listar() {
  cargando.value = true
  errorGeneral.value = null
  try {
    ventas.value = (await api.get('ventas')).datos
  } catch (e) {
    errorGeneral.value = 'No se pudieron cargar las ventas.'
  } finally {
    cargando.value = false
  }
}

async function verDetalle(id) {
  cargandoDetalle.value = true
  try {
    ventaSeleccionada.value = (await api.get(`ventas/${id}`)).datos
  } catch (e) {
    errorGeneral.value = 'No se pudo cargar el detalle de la venta.'
  } finally {
    cargandoDetalle.value = false
  }
}

function volver() {
  ventaSeleccionada.value = null
}

async function anular(id) {
  if (!confirm('¿Anular esta venta? (no repone stock automáticamente)')) return
  try {
    const resp = await api.del(`ventas/${id}`)
    ventaSeleccionada.value = resp.datos
    await listar()
  } catch (e) {
    errorGeneral.value = e.data?.mensaje ?? 'No se pudo anular la venta.'
  }
}

onMounted(listar)
onActivated(listar)
</script>

<template>
  <div class="h-full overflow-auto p-4 sm:p-6 max-w-5xl mx-auto w-full">
    <!-- MODO LISTA -->
    <template v-if="!ventaSeleccionada">
      <header class="flex items-center gap-2 mb-6">
        <ClipboardDocumentListIcon class="w-6 h-6 text-amber-500" />
        <h1 class="text-2xl font-bold">Ventas</h1>
      </header>

      <div v-if="errorGeneral" class="mb-4 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg p-3 text-sm">
        {{ errorGeneral }}
      </div>

      <div v-if="cargando" class="text-gray-400 animate-pulse py-8 text-center">Cargando…</div>
      <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-left">
            <tr>
              <th class="p-3 font-medium">#</th>
              <th class="p-3 font-medium">Fecha</th>
              <th class="p-3 font-medium hidden sm:table-cell">Cliente</th>
              <th class="p-3 font-medium text-right">Total</th>
              <th class="p-3 font-medium hidden sm:table-cell">Método</th>
              <th class="p-3 font-medium">Estado</th>
              <th class="p-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="v in ventas" :key="v.id">
              <td class="p-3 font-medium">{{ v.id }}</td>
              <td class="p-3 text-gray-500 dark:text-gray-400">{{ formatearFecha(v.creado_en) }}</td>
              <td class="p-3 hidden sm:table-cell text-gray-500 dark:text-gray-400">{{ v.cliente || '—' }}</td>
              <td class="p-3 text-right font-semibold">{{ formatearCLP(v.total) }}</td>
              <td class="p-3 hidden sm:table-cell capitalize text-gray-500 dark:text-gray-400">{{ v.metodo_pago }}</td>
              <td class="p-3">
                <span class="text-xs px-2 py-0.5 rounded-full capitalize" :class="badgeEstado[v.estado]">{{ v.estado }}</span>
              </td>
              <td class="p-3 text-right">
                <button @click="verDetalle(v.id)" class="p-1.5 text-gray-500 hover:text-amber-600 transition" title="Ver detalle">
                  <EyeIcon class="w-5 h-5" />
                </button>
              </td>
            </tr>
            <tr v-if="ventas.length === 0">
              <td colspan="7" class="p-6 text-center text-sm text-gray-400">Aún no hay ventas registradas.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- MODO DETALLE -->
    <template v-else>
      <button @click="volver" class="flex items-center gap-1 text-sm text-gray-500 hover:text-amber-600 mb-4 transition">
        <ArrowLeftIcon class="w-4 h-4" /> Volver a la lista
      </button>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-5">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h2 class="text-xl font-bold">Venta #{{ ventaSeleccionada.id }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatearFecha(ventaSeleccionada.creado_en) }}</p>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full capitalize" :class="badgeEstado[ventaSeleccionada.estado]">
            {{ ventaSeleccionada.estado }}
          </span>
        </div>

        <dl class="grid grid-cols-2 gap-y-1 text-sm mb-4">
          <dt class="text-gray-500 dark:text-gray-400">Cliente</dt>
          <dd class="text-right">{{ ventaSeleccionada.cliente || '—' }}</dd>
          <dt class="text-gray-500 dark:text-gray-400">Método de pago</dt>
          <dd class="text-right capitalize">{{ ventaSeleccionada.metodo_pago }}</dd>
          <dt class="text-gray-500 dark:text-gray-400">Cajero</dt>
          <dd class="text-right">{{ ventaSeleccionada.cajero || '—' }}</dd>
          <dt v-if="ventaSeleccionada.nota" class="text-gray-500 dark:text-gray-400">Nota</dt>
          <dd v-if="ventaSeleccionada.nota" class="text-right">{{ ventaSeleccionada.nota }}</dd>
        </dl>

        <!-- Líneas -->
        <table class="w-full text-sm mb-4">
          <thead class="text-gray-500 dark:text-gray-400 text-left border-b border-gray-100 dark:border-gray-700">
            <tr>
              <th class="py-2 font-medium">Producto</th>
              <th class="py-2 font-medium text-center">Cant.</th>
              <th class="py-2 font-medium text-right">P. unit.</th>
              <th class="py-2 font-medium text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="d in ventaSeleccionada.detalle" :key="d.id">
              <td class="py-2">{{ d.nombre_producto }}</td>
              <td class="py-2 text-center">{{ d.cantidad }}</td>
              <td class="py-2 text-right">{{ formatearCLP(d.precio_unitario) }}</td>
              <td class="py-2 text-right font-medium">{{ formatearCLP(d.subtotal) }}</td>
            </tr>
          </tbody>
        </table>

        <!-- Totales -->
        <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-1 text-sm">
          <div class="flex justify-between font-bold text-base">
            <span>Total</span><span>{{ formatearCLP(ventaSeleccionada.total) }}</span>
          </div>
          <div class="flex justify-between text-gray-500 dark:text-gray-400">
            <span>Pagado</span><span>{{ formatearCLP(ventaSeleccionada.monto_pagado) }}</span>
          </div>
          <div class="flex justify-between text-emerald-600 dark:text-emerald-400 font-medium">
            <span>Vuelto</span><span>{{ formatearCLP(ventaSeleccionada.vuelto) }}</span>
          </div>
        </div>

        <button
          v-if="ventaSeleccionada.estado !== 'anulada'"
          @click="anular(ventaSeleccionada.id)"
          class="mt-5 flex items-center gap-1 text-sm text-red-600 hover:text-red-800 dark:hover:text-red-400 transition"
        >
          <NoSymbolIcon class="w-5 h-5" /> Anular venta
        </button>
      </div>
    </template>
  </div>
</template>
