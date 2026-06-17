<script setup>
import { ref } from 'vue'
import api from '../../api.js'
import { guardarToken } from '../../utils/auth.js'
import { BuildingStorefrontIcon } from '@heroicons/vue/24/outline'

const username = ref('')
const password = ref('')
const cargando = ref(false)
const error = ref('')

async function entrar() {
  error.value = ''
  cargando.value = true
  try {
    const resp = await api.post('login', { username: username.value, password: password.value })
    const token = resp.datos?.token
    if (token) {
      guardarToken(token)
      location.hash = '#panel'
      location.reload()
    } else {
      error.value = 'Respuesta inválida del servidor.'
    }
  } catch (e) {
    if (e.status === 401) error.value = 'Usuario o contraseña incorrectos.'
    else error.value = e.data?.mensaje ?? 'Error al iniciar sesión.'
  } finally {
    cargando.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-950 px-4">
    <div class="w-full max-w-2xl bg-slate-900/95 border border-slate-700 shadow-2xl shadow-black/40 rounded-2xl p-8 backdrop-blur-sm">
      <div class="mb-8 text-center">
        <span class="inline-flex items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-red-500 p-3 shadow-md shadow-amber-500/20 mx-auto mb-4">
          <BuildingStorefrontIcon class="w-6 h-6 text-white" />
        </span>
        <p class="text-sm text-slate-400 uppercase tracking-[0.24em]">Bienvenido a</p>
        <h1 class="mt-3 text-4xl font-semibold text-white">
          mi<span class="bg-gradient-to-r from-amber-500 to-red-500 bg-clip-text text-transparent">Caja</span>
        </h1>
        <p class="mt-3 text-sm text-slate-400">Ingresa tus credenciales para continuar en tu punto de venta.</p>
      </div>

      <div class="space-y-5">
        <div>
          <label class="block text-sm text-slate-400 mb-2">Usuario</label>
          <input
            v-model="username"
            placeholder="usuario"
            class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-white outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          />
        </div>

        <div>
          <label class="block text-sm text-slate-400 mb-2">Contraseña</label>
          <input
            v-model="password"
            type="password"
            placeholder="••••••••"
            class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-white outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          />
        </div>

        <div v-if="error" class="rounded-3xl bg-red-500/10 border border-red-500/20 p-3 text-sm text-red-100">
          {{ error }}
        </div>

        <button
          @click="entrar"
          :disabled="cargando || !username || !password"
          class="w-full rounded-3xl bg-gradient-to-r from-amber-500 to-red-500 px-5 py-3 text-sm font-semibold text-white transition hover:from-amber-400 hover:to-red-400 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ cargando ? 'Iniciando...' : 'Ingresar' }}
        </button>
      </div>

    </div>
  </div>
</template>
