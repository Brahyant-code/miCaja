const STORAGE_KEY = 'miCaja_token'

function obtenerToken() {
  if (typeof localStorage === 'undefined') return null
  return localStorage.getItem(STORAGE_KEY)
}

function guardarToken(token) {
  if (typeof localStorage === 'undefined') return
  localStorage.setItem(STORAGE_KEY, token)
}

function borrarToken() {
  if (typeof localStorage === 'undefined') return
  localStorage.removeItem(STORAGE_KEY)
}

function decodificarToken(token) {
  if (typeof token !== 'string' || token.split('.').length !== 3) return null
  try {
    const payload = token.split('.')[1]
    const json = atob(payload.replace(/-/g, '+').replace(/_/g, '/'))
    return JSON.parse(json)
  } catch (err) {
    return null
  }
}

function esTokenValido() {
  const token = obtenerToken()
  if (!token) return false
  const payload = decodificarToken(token)
  if (!payload || !payload.exp) return false
  return Date.now() / 1000 < Number(payload.exp)
}

function logout() {
  borrarToken()
  if (typeof location !== 'undefined') {
    location.reload()
  }
}

export { obtenerToken, guardarToken, borrarToken, decodificarToken, esTokenValido, logout }
