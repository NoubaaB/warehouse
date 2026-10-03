import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useSnackbarStore } from './snackbar';

export const useStockStore = defineStore('stock', () => {
  const activeFishStocks = ref([]);
  const archivedFishStocks = ref([]);
  const consumableStocks = ref([]);
  const loading = ref(false);
  const snackbar = useSnackbarStore();

  async function fetchActiveFishStocks(params = {}) {
    loading.value = true;
    try {
      const response = await axios.get('/api/fish-stock', { params });
      activeFishStocks.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load active fish stock');
    } finally {
      loading.value = false;
    }
  }

  async function fetchArchivedFishStocks(params = {}) {
    loading.value = true;
    try {
      const response = await axios.get('/api/fish-stock/archive', { params });
      archivedFishStocks.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load archived fish stock');
    } finally {
      loading.value = false;
    }
  }

  async function fetchConsumableStocks() {
    loading.value = true;
    try {
      const response = await axios.get('/api/consumable-stock');
      consumableStocks.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load consumable stock');
    } finally {
      loading.value = false;
    }
  }

  return {
    activeFishStocks,
    archivedFishStocks,
    consumableStocks,
    loading,
    fetchActiveFishStocks,
    fetchArchivedFishStocks,
    fetchConsumableStocks,
  };
});
