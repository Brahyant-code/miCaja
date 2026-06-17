import { logout } from './utils/auth.js'

// Helper reutilizable para hablar con el backend Nova.
// La URL base se toma de VITE_API_URL (ver .env.development / .env.production).
const API = import.meta.env.VITE_API_URL ?? '.'

async function req(ruta, opts = {}) {
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('miCaja_token') : null
  const headers = { 'Content-Type': 'application/json' }
  if (token) headers['Authorization'] = 'Bearer ' + token
  const r = await fetch(`${API}/${ruta}`, {
    headers,
    ...opts,
  })
  const data = await r.json().catch(() => ({}))
  if (!r.ok) {
    if (r.status === 401 && ruta !== 'login') {
      logout()
    }
    throw Object.assign(new Error('HTTP ' + r.status), { status: r.status, data })
  }
  return data
}

export default {
  get:  (ruta)         => req(ruta),
  post: (ruta, body)   => req(ruta, { method: 'POST',   body: JSON.stringify(body) }),
  put:  (ruta, body)   => req(ruta, { method: 'PUT',    body: JSON.stringify(body) }),
  del:  (ruta)         => req(ruta, { method: 'DELETE' }),
}
