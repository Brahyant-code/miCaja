import { createApp } from 'vue'
import App from './App.vue'
import './style.css'
import { iniciarTema } from './theme.js'

iniciarTema()              // deja el estado del tema sincronizado al arrancar
createApp(App).mount('#app')
