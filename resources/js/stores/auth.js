import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
import { useSnackbarStore } from './snackbar';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'));
  const token = ref(localStorage.getItem('auth_token') || '');
  const isAuthenticated = ref(!!token.value);
  const loading = ref(false);

  const snackbar = useSnackbarStore();

  async function login(email, password) {
    loading.value = true;
    try {
      await axios.get('/sanctum/csrf-cookie');
      const response = await axios.post('/api/auth/login', { email, password });
      
      user.value = response.data.user;
      token.value = response.data.token;
      isAuthenticated.value = true;

      localStorage.setItem('auth_token', token.value);
      localStorage.setItem('auth_user', JSON.stringify(user.value));
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;

      snackbar.showSuccess('Logged in successfully!');
      return true;
    } catch (error) {
      const msg = error.response?.data?.message || 'Login failed. Please check your credentials.';
      snackbar.showError(msg);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  async function logout() {
    try {
      await axios.post('/api/auth/logout');
    } catch (e) {
      // ignore
    } finally {
      user.value = null;
      token.value = '';
      isAuthenticated.value = false;
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      delete axios.defaults.headers.common['Authorization'];
      snackbar.showSuccess('Logged out.');
    }
  }

  async function fetchUser() {
    if (!token.value) return;
    try {
      const response = await axios.get('/api/auth/user');
      user.value = response.data;
      localStorage.setItem('auth_user', JSON.stringify(user.value));
    } catch (e) {
      logout();
    }
  }

  async function updateProfile(data) {
    loading.value = true;
    try {
      const response = await axios.put('/api/auth/profile', data);
      user.value = response.data.user;
      localStorage.setItem('auth_user', JSON.stringify(user.value));
      snackbar.showSuccess(response.data.message || 'Profile updated successfully!');
      return response.data;
    } catch (error) {
      const msg = error.response?.data?.message || 
        (error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join(' ') : 'Error updating profile');
      snackbar.showError(msg);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  async function updatePassword(data) {
    loading.value = true;
    try {
      const response = await axios.put('/api/auth/password', data);
      snackbar.showSuccess(response.data.message || 'Password changed successfully!');
      return response.data;
    } catch (error) {
      const msg = error.response?.data?.message || 
        (error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join(' ') : 'Error updating password');
      snackbar.showError(msg);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    loading,
    login,
    logout,
    fetchUser,
    updateProfile,
    updatePassword,
  };
});
