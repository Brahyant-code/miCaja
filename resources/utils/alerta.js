// Envoltorio de SweetAlert2 con estilo acorde al tema (claro/oscuro) de la app.
import Swal from 'sweetalert2'
import { oscuro } from '../theme.js'

const AMBAR = '#f59e0b'
const ROJO = '#ef4444'

// Colores base según el tema actual.
function baseTema() {
  return oscuro.value
    ? { background: '#1f2937', color: '#f3f4f6' } // gray-800 / gray-100
    : { background: '#ffffff', color: '#1f2937' }
}

// Toast pequeño en la esquina (no bloquea).
const Toast = (icon, title) =>
  Swal.fire({
    toast: true,
    position: 'top-end',
    timer: 2800,
    timerProgressBar: true,
    showConfirmButton: false,
    icon,
    title,
    ...baseTema(),
  })

export function toastExito(mensaje) {
  return Toast('success', mensaje)
}

export function toastError(mensaje) {
  return Toast('error', mensaje)
}

// Alerta de error (modal con botón de cierre).
export function alertaError(mensaje, titulo = 'Ups…') {
  return Swal.fire({
    icon: 'error',
    title: titulo,
    text: mensaje,
    confirmButtonColor: AMBAR,
    ...baseTema(),
  })
}

// Alerta de éxito (modal).
export function alertaExito(mensaje, titulo = 'Listo') {
  return Swal.fire({
    icon: 'success',
    title: titulo,
    text: mensaje,
    confirmButtonColor: AMBAR,
    ...baseTema(),
  })
}

// Confirmación. Devuelve true si el usuario confirma.
export async function confirmar(texto, titulo = '¿Estás seguro?', textoBoton = 'Sí, continuar') {
  const r = await Swal.fire({
    icon: 'warning',
    title: titulo,
    text: texto,
    showCancelButton: true,
    confirmButtonText: textoBoton,
    cancelButtonText: 'Cancelar',
    confirmButtonColor: ROJO,
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
    ...baseTema(),
  })
  return r.isConfirmed
}
