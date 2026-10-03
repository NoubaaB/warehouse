import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useSnackbarStore } from './snackbar';

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref(null);
  const loading = ref(false);
  const snackbar = useSnackbarStore();

  async function fetchDashboardData() {
    loading.value = true;
    try {
      const response = await axios.get('/api/dashboard');
      stats.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load dashboard statistics');
    } finally {
      loading.value = false;
    }
  }

  return {
    stats,
    loading,
    fetchDashboardData,
  };
});
