<template>
  <!-- Summary Snackbar -->
  <v-snackbar
    v-model="snackbar.show"
    :color="snackbar.color"
    :timeout="snackbar.timeout"
    location="top right"
    elevation="24"
    rounded="lg"
    class="summary-snackbar"
  >
    <div class="d-flex align-center">
      <v-icon
        :icon="snackbar.color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'"
        class="mr-3"
      ></v-icon>
      <span class="text-body-2 font-weight-medium">{{ snackbar.text }}</span>
    </div>

    <template #actions>
      <v-btn
        color="white"
        variant="text"
        icon="mdi-close"
        size="small"
        @click="snackbar.show = false"
      ></v-btn>
    </template>
  </v-snackbar>
</template>

<script setup>
import { useSnackbarStore } from '../stores/snackbar';

const snackbar = useSnackbarStore();
</script>

<style scoped>
.summary-snackbar {
  font-weight: 500;
}
</style>
