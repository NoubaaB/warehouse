import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useSnackbarStore = defineStore('snackbar', () => {
  const show = ref(false);
  const text = ref('');
  const color = ref('success');
  const timeout = ref(4000);

  function showMessage(msg, type = 'success', duration = 4000) {
    text.value = msg;
    color.value = type;
    timeout.value = duration;
    show.value = true;
  }

  function showSuccess(msg) {
    showMessage(msg, 'success');
  }

  function showError(msg) {
    showMessage(msg, 'error', 6000);
  }

  return {
    show,
    text,
    color,
    timeout,
    showMessage,
    showSuccess,
    showError,
  };
});
