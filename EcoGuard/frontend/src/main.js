import { createApp } from 'vue'
import 'leaflet/dist/leaflet.css'
import './app.css'
import App from './App.vue'
import router from './router'
import bgImage from './assets/bg.png'

document.documentElement.style.setProperty('--ecoguard-bg-image', `url(${bgImage})`)

const app = createApp(App)
app.use(router)
app.mount('#app')
