import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useSnackbarStore } from './snackbar';

export const useWorkforceStore = defineStore('workforce', () => {
  const workers = ref([]);
  const assignments = ref([]);
  const monthlySummary = ref({ workers: [], grand_total: 0 });
  const loading = ref(false);

  const snackbar = useSnackbarStore();

  async function fetchWorkers() {
    try {
      const response = await axios.get('/api/workforces');
      workers.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load workers list');
    }
  }

  async function fetchAssignments(params = {}) {
    loading.value = true;
    try {
      const response = await axios.get('/api/workforce-assignments', { params });
      assignments.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load workforce assignments');
    } finally {
      loading.value = false;
    }
  }

  async function fetchMonthlySummary(year, month, fortnight = 'all') {
    loading.value = true;
    try {
      const response = await axios.get('/api/workforce-assignments/monthly-summary', {
        params: { year, month, fortnight },
      });
      monthlySummary.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load monthly summary');
    } finally {
      loading.value = false;
    }
  }

  async function saveAssignments(data) {
    loading.value = true;
    try {
      const response = await axios.post('/api/workforce-assignments', data);
      snackbar.showSuccess('Workforce rates assigned successfully!');
      return response.data;
    } catch (error) {
      const msg = error.response?.data?.message || 'Error assigning workforce rates';
      snackbar.showError(msg);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  return {
    workers,
    assignments,
    monthlySummary,
    loading,
    fetchWorkers,
    fetchAssignments,
    fetchMonthlySummary,
    saveAssignments,
  };
});
