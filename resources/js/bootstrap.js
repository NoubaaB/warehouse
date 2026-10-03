import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.withCredentials = true;

// Set baseURL
window.axios.defaults.baseURL = window.location.origin;

// Set authorization header if token exists
const token = localStorage.getItem('auth_token');
if (token) {
  window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}
