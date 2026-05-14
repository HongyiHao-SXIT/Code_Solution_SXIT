import { createRouter, createWebHistory } from 'vue-router'
import SearchView from '../views/SearchView.vue'
import PaperDetailView from '../views/PaperDetailView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'search',
      component: SearchView,
    },
    {
      path: '/paper/:id',
      name: 'paper-detail',
      component: PaperDetailView,
      props: true,
    },
  ],
})

export default router
