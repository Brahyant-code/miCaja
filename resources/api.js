// Helper reutilizable para hablar con el backend Nova.
// La URL base se toma de VITE_API_URL (ver .env.development / .env.production).
const API = import.meta.env.VITE_API_URL ?? '.'

async function req(ruta, opts = {}) {
  const r = await fetch(`${API}/${ruta}`, {
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  })
  const data = await r.json().catch(() => ({}))
  if (!r.ok) {
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
