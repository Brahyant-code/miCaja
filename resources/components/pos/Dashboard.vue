<script setup>
import { ref, computed, onMounted, onActivated } from 'vue'
import api from '../../api.js'
import { formatearCLP } from '../../utils/dinero.js'
import { oscuro } from '../../theme.js'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS, Title, Tooltip, Legend,
  BarElement, CategoryScale, LinearScale,
} from 'chart.js'
import {
  CurrencyDollarIcon, ShoppingBagIcon, CalendarDaysIcon, ChartBarIcon, Squares2X2Icon, TrophyIcon,
} from '@heroicons/vue/24/outline'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const datos = ref(null)
const cargando = ref(true)
const errorGeneral = ref(null)

async function cargar() {
  cargando.value = true
  errorGeneral.value = null
  try {
    datos.value = (await api.get('dashboard')).datos
  } catch (e) {
    errorGeneral.value = 'No se pudo cargar el panel de control.'
  } finally {
    cargando.value = false
  }
}

// Etiqueta corta de día: "2026-06-12" -> "12/06"
function etiquetaDia(iso) {
  return iso.slice(8, 10) + '/' + iso.slice(5, 7)
}

const tarjetas = computed(() => {
  const r = datos.value?.resumen
  if (!r) return []
  return [
    { titulo: 'Ventas hoy', valor: r.hoy.ventas, icono: ShoppingBagIcon, color: 'from-amber-500 to-orange-500' },
    { titulo: 'Recaudado hoy', valor: formatearCLP(r.hoy.recaudado), icono: CurrencyDollarIcon, color: 'from-emerald-500 to-green-600' },
    { titulo: 'Ventas esta semana', valor: r.semana.ventas, icono: CalendarDaysIcon, color: 'from-sky-500 to-blue-600' },
    { titulo: 'Recaudado esta semana', valor: formatearCLP(r.semana.recaudado), icono: ChartBarIcon, color: 'from-violet-500 to-purple-600' },
  ]
})

// Colores que dependen del tema (texto y rejilla de los gráficos).
const colorTexto = computed(() => (oscuro.value ? '#9ca3af' : '#6b7280'))
const colorGrid = computed(() => (oscuro.value ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)'))

function opcionesMoneda() {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: { label: (ctx) => formatearCLP(ctx.parsed.y) },
      },
    },
    scales: {
      x: { ticks: { color: colorTexto.value }, grid: { display: false } },
      y: {
        beginAtZero: true,
        ticks: { color: colorTexto.value, callback: (v) => formatearCLP(v) },
        grid: { color: colorGrid.value },
      },
    },
  }
}

const dataDia = computed(() => ({
  labels: (datos.value?.porDia ?? []).map((d) => etiquetaDia(d.dia)),
  datasets: [
    {
      label: 'Recaudado',
      data: (datos.value?.porDia ?? []).map((d) => d.recaudado),
      backgroundColor: '#f59e0b',
      borderRadius: 6,
    },
  ],
}))

const dataSemana = computed(() => ({
  labels: (datos.value?.porSemana ?? []).map((s) => s.semana),
  datasets: [
    {
      label: 'Recaudado',
      data: (datos.value?.porSemana ?? []).map((s) => s.recaudado),
      backgroundColor: '#8b5cf6',
      borderRadius: 6,
    },
  ],
}))

const opcionesDia = computed(opcionesMoneda)
const opcionesSemana = computed(opcionesMoneda)

onMounted(cargar)
onActivated(cargar)
</script>

<template>
  <div class="h-full overflow-auto p-4 sm:p-6 max-w-6xl mx-auto w-full">
    <header class="flex items-center gap-2 mb-6">
      <Squares2X2Icon class="w-6 h-6 text-amber-500" />
      <h1 class="text-2xl font-bold">Panel de control</h1>
    </header>

    <div v-if="errorGeneral" class="mb-4 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg p-3 text-sm">
      {{ errorGeneral }}
    </div>

    <div v-if="cargando" class="text-gray-400 animate-pulse py-8 text-center">Cargando…</div>

    <template v-else>
      <!-- Tarjetas de indicadores -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div
          v-for="t in tarjetas"
          :key="t.titulo"
          class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 flex items-center gap-4"
        >
          <div :class="['shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-white', t.color]">
            <component :is="t.icono" class="w-6 h-6" />
          </div>
          <div class="min-w-0">
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t.titulo }}</p>
            <p class="text-xl font-bold truncate">{{ t.valor }}</p>
          </div>
        </div>
      </div>

      <!-- Gráficos -->
      <div class="grid lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4">
          <h2 class="font-semibold mb-3">Recaudación últimos 7 días</h2>
          <div class="h-64">
            <Bar :data="dataDia" :options="opcionesDia" />
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4">
          <h2 class="font-semibold mb-3">Recaudación por semana</h2>
          <div class="h-64">
            <Bar :data="dataSemana" :options="opcionesSemana" />
          </div>
        </div>
      </div>

      <!-- Top 3 productos más vendidos -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 mt-4">
        <h2 class="font-semibold mb-3 flex items-center gap-2">
          <TrophyIcon class="w-5 h-5 text-amber-500" /> Top 3 productos más vendidos
        </h2>
        <ul v-if="(datos?.top ?? []).length" class="space-y-2">
          <li
            v-for="(p, i) in datos.top"
            :key="p.producto_id"
            class="flex items-center gap-3 p-2 rounded-lg bg-gray-50 dark:bg-gray-700/40"
          >
            <span
              class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-white"
              :class="['bg-amber-400', 'bg-gray-400', 'bg-orange-400'][i] || 'bg-gray-400'"
            >{{ i + 1 }}</span>
            <span class="flex-1 font-medium truncate">{{ p.nombre_producto }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ p.unidades }} u.</span>
            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatearCLP(p.recaudado) }}</span>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-400 py-2">Aún no hay ventas para calcular el ranking.</p>
      </div>
    </template>
  </div>
</template>
