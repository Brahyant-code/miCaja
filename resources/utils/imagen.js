// Construye la URL absoluta de una imagen de producto.
// imagen_url viene del backend como ruta relativa ("uploads/productos/abc.jpg"),
// servida desde el mismo origen que el API.
const API = import.meta.env.VITE_API_URL ?? '.'

export function urlImagen(ruta) {
  if (!ruta) return ''
  if (/^https?:\/\//.test(ruta)) return ruta // por si el backend ya envía una URL absoluta

  // Normalizar la variable de entorno `VITE_API_URL` y permitir formas
  // como 'api', '/api', '/api/' o 'http://host/api'. Queremos quitar el
  // sufijo 'api' si está presente para que las imágenes se pidan en
  // '/uploads/...' (o 'http(s)://host/uploads/...').
  const raw = (API ?? '').toString().trim()

  if (raw === '.' || raw === '') {
    return ruta.startsWith('/') ? ruta : '/' + ruta
  }

  // Quitar barras finales y prefijo './'
  let base = raw.replace(/\/+$|^\.\//g, '')
  // Quitar un sufijo 'api' opcional
  base = base.replace(/\/?api$/i, '')

  if (base === '' ) {
    return ruta.startsWith('/') ? ruta : '/' + ruta
  }

  // Asegurar que no queden '//' al concatenar
  return `${base.replace(/\/+$/,'')}/${ruta.replace(/^\/+/, '')}`
}
