import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useSnackbarStore } from './snackbar';

export const useSettingsStore = defineStore('settings', () => {
  const providers = ref([]);
  const clients = ref([]);
  const freezingFish = ref([]);
  const consumableTypes = ref([]);
  const fishWarehouses = ref([]);
  const containers = ref([]);
  const voucherTypes = ref([]);
  const workforces = ref([]);
  const loading = ref(false);

  const snackbar = useSnackbarStore();

  async function fetchAllSettings() {
    loading.value = true;
    try {
      const [
        resProviders,
        resClients,
        resFish,
        resConsumables,
        resWarehouses,
        resContainers,
        resVoucherTypes,
        resWorkforces
      ] = await Promise.all([
        axios.get('/api/providers'),
        axios.get('/api/clients'),
        axios.get('/api/freezing-fish'),
        axios.get('/api/consumable-types'),
        axios.get('/api/fish-warehouses'),
        axios.get('/api/containers'),
        axios.get('/api/voucher-types'),
        axios.get('/api/workforces'),
      ]);

      providers.value = resProviders.data;
      clients.value = resClients.data;
      freezingFish.value = resFish.data;
      consumableTypes.value = resConsumables.data;
      fishWarehouses.value = resWarehouses.data;
      containers.value = resContainers.data;
      voucherTypes.value = resVoucherTypes.data;
      workforces.value = resWorkforces.data;
    } catch (error) {
      snackbar.showError('Failed to load settings data');
    } finally {
      loading.value = false;
    }
  }

  // Generic CRUD helpers
  async function createEntity(endpoint, payload) {
    try {
      const res = await axios.post(`/api/${endpoint}`, payload);
      snackbar.showSuccess('Record created successfully');
      await fetchAllSettings();
      return res.data;
    } catch (error) {
      const msg = error.response?.data?.message || 'Failed to create record';
      snackbar.showError(msg);
      throw error;
    }
  }

  async function updateEntity(endpoint, id, payload) {
    try {
      const res = await axios.put(`/api/${endpoint}/${id}`, payload);
      snackbar.showSuccess('Record updated successfully');
      await fetchAllSettings();
      return res.data;
    } catch (error) {
      const msg = error.response?.data?.message || 'Failed to update record';
      snackbar.showError(msg);
      throw error;
    }
  }

  async function deleteEntity(endpoint, id) {
    try {
      await axios.delete(`/api/${endpoint}/${id}`);
      snackbar.showSuccess('Record deleted successfully');
      await fetchAllSettings();
    } catch (error) {
      const msg = error.response?.data?.message || 'Failed to delete record';
      snackbar.showError(msg);
      throw error;
    }
  }

  return {
    providers,
    clients,
    freezingFish,
    consumableTypes,
    fishWarehouses,
    containers,
    voucherTypes,
    workforces,
    loading,
    fetchAllSettings,
    createEntity,
    updateEntity,
    deleteEntity,
  };
});
