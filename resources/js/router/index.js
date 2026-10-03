import { createRouter, createWebHistory } from 'vue-router';
import MainLayout from '../layouts/MainLayout.vue';
import Login from '../pages/Login.vue';
import Dashboard from '../pages/Dashboard.vue';
import WarehouseOperations from '../pages/WarehouseOperations.vue';
import FishWarehouse from '../pages/FishWarehouse.vue';
import ConsumableWarehouse from '../pages/ConsumableWarehouse.vue';
import WorkforcePage from '../pages/WorkforcePage.vue';
import SettingsPage from '../pages/SettingsPage.vue';

const routes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { guestOnly: true },
  },
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: Dashboard,
      },
      {
        path: 'warehouse-operations',
        name: 'warehouse-operations',
        component: WarehouseOperations,
      },
      {
        path: 'fish-warehouse',
        name: 'fish-warehouse',
        component: FishWarehouse,
      },
      {
        path: 'consumable-warehouse',
        name: 'consumable-warehouse',
        component: ConsumableWarehouse,
      },
      {
        path: 'workforce',
        name: 'workforce',
        component: WorkforcePage,
      },
      {
        path: 'settings/:section?',
        name: 'settings',
        component: SettingsPage,
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token');

  if (to.meta.requiresAuth && !token) {
    return next('/login');
  }

  if (to.meta.guestOnly && token) {
    return next('/dashboard');
  }

  next();
});

export default router;
