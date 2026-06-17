// Utilidades de formato para dinero y fechas (contexto local: pesos chilenos).

// Formatea un número como peso chileno sin decimales: 12345 -> "$12.345"
const fmtCLP = new Intl.NumberFormat('es-CL', {
  style: 'currency',
  currency: 'CLP',
  maximumFractionDigits: 0,
})

export function formatearCLP(valor) {
  const n = Number(valor)
  return Number.isFinite(n) ? fmtCLP.format(n) : '$0'
}

// Fecha legible: "12-06-2026 14:30"
const fmtFecha = new Intl.DateTimeFormat('es-CL', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
})

export function formatearFecha(iso) {
  if (!iso) return ''
  // MySQL devuelve "YYYY-MM-DD HH:MM:SS"; lo normalizamos a ISO para el parser.
  const d = new Date(String(iso).replace(' ', 'T'))
  return Number.isNaN(d.getTime()) ? iso : fmtFecha.format(d)
}
