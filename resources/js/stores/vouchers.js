import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useSnackbarStore } from './snackbar';

export const useVouchersStore = defineStore('vouchers', () => {
  const vouchers = ref([]);
  const loading = ref(false);
  const snackbar = useSnackbarStore();

  async function fetchVouchers(params = {}) {
    loading.value = true;
    try {
      const response = await axios.get('/api/vouchers', { params });
      vouchers.value = response.data;
    } catch (error) {
      snackbar.showError('Failed to load vouchers list');
    } finally {
      loading.value = false;
    }
  }

  async function createVoucher(voucherData) {
    loading.value = true;
    try {
      const response = await axios.post('/api/vouchers', voucherData);
      snackbar.showSuccess(response.data.message || 'Voucher saved successfully');
      await fetchVouchers();
      return response.data.voucher;
    } catch (error) {
      const msg = error.response?.data?.message || 
        (error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join(' ') : 'Error saving voucher');
      snackbar.showError(msg);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  return {
    vouchers,
    loading,
    fetchVouchers,
    createVoucher,
  };
});
