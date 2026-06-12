import { ref } from 'vue'

// Estado reactivo del tema (true = oscuro). Compartido por toda la app.
const oscuro = ref(false)

// Aplica o quita la clase "dark" en <html>.
function aplicar() {
  document.documentElement.classList.toggle('dark', oscuro.value)
}

// Inicializa el tema: usa la elección guardada o, si no hay, la preferencia del sistema.
function iniciarTema() {
  const guardado = localStorage.getItem('tema')
  oscuro.value = guardado
    ? guardado === 'oscuro'
    : window.matchMedia('(prefers-color-scheme: dark)').matches
  aplicar()
}

// Alterna entre claro/oscuro y recuerda la elección.
function alternarTema() {
  oscuro.value = !oscuro.value
  localStorage.setItem('tema', oscuro.value ? 'oscuro' : 'claro')
  aplicar()
}

export { oscuro, iniciarTema, alternarTema }
