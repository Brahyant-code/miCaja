// Parser CSV simple para la importación de productos.
// Soporta comillas dobles, comas dentro de comillas, y separador "," o ";".
// La primera fila debe ser el encabezado. Devuelve un arreglo de objetos
// { encabezado: valor } con las claves en minúsculas y sin espacios.

function detectarSeparador(linea) {
  const comas = (linea.match(/,/g) || []).length
  const puntoYComa = (linea.match(/;/g) || []).length
  return puntoYComa > comas ? ';' : ','
}

// Parsea una sola línea respetando comillas dobles.
function parsearLinea(linea, sep) {
  const campos = []
  let actual = ''
  let enComillas = false
  for (let i = 0; i < linea.length; i++) {
    const c = linea[i]
    if (enComillas) {
      if (c === '"') {
        if (linea[i + 1] === '"') { actual += '"'; i++ } // comilla escapada ""
        else enComillas = false
      } else {
        actual += c
      }
    } else if (c === '"') {
      enComillas = true
    } else if (c === sep) {
      campos.push(actual)
      actual = ''
    } else {
      actual += c
    }
  }
  campos.push(actual)
  return campos.map((s) => s.trim())
}

// Escapa un campo para CSV: lo encierra en comillas si contiene el separador,
// comillas o saltos de línea, duplicando las comillas internas.
function escaparCampo(valor, sep) {
  const s = valor === null || valor === undefined ? '' : String(valor)
  if (s.includes('"') || s.includes(sep) || s.includes('\n') || s.includes('\r')) {
    return '"' + s.replace(/"/g, '""') + '"'
  }
  return s
}

// Genera texto CSV a partir de filas (objetos) y una lista de columnas.
// Devuelve encabezado + filas separados por $sep (por defecto ";").
export function toCSV(filas, columnas, sep = ';') {
  const lineas = [columnas.map((c) => escaparCampo(c, sep)).join(sep)]
  for (const fila of filas) {
    lineas.push(columnas.map((c) => escaparCampo(fila[c], sep)).join(sep))
  }
  return lineas.join('\n')
}

// Convierte el texto CSV en un arreglo de objetos usando el encabezado.
export function parseCSV(texto) {
  // Normaliza saltos de línea y descarta líneas vacías.
  const lineas = texto
    .replace(/\r\n?/g, '\n')
    .split('\n')
    .filter((l) => l.trim() !== '')

  if (lineas.length < 2) return { columnas: [], filas: [] }

  const sep = detectarSeparador(lineas[0])
  const columnas = parsearLinea(lineas[0], sep).map((h) => h.toLowerCase().trim())

  const filas = lineas.slice(1).map((linea) => {
    const valores = parsearLinea(linea, sep)
    const obj = {}
    columnas.forEach((col, i) => {
      obj[col] = valores[i] !== undefined ? valores[i] : ''
    })
    return obj
  })

  return { columnas, filas }
}
