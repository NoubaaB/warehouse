<template>
  <v-app theme="dark">
    <SummarySnackbar />
    <v-container fluid class="fill-height bg-background flex-center pa-4">
      <v-row justify="center" align="center" class="fill-height">
        <v-col cols="12" sm="8" md="5" lg="4">
          <v-card class="pa-6 rounded-xl border shadow-24 glass-card">
            <div class="text-center mb-6">
              <v-avatar color="primary" size="64" class="mb-3 shadow-lg">
                <v-icon icon="mdi-fish" size="36" color="white"></v-icon>
              </v-avatar>
              <h2 class="text-h5 font-weight-bold">Freezing Fish Factory</h2>
              <div class="text-body-2 text-grey">Warehouse Management System</div>
            </div>

            <v-form @submit.prevent="handleLogin" v-model="valid">
              <v-text-field
                v-model="email"
                label="Email Address"
                type="email"
                prepend-inner-icon="mdi-email-outline"
                variant="outlined"
                density="comfortable"
                :rules="[v => !!v || 'Email is required']"
                required
                class="mb-3 custom-field"
              ></v-text-field>

              <v-text-field
                v-model="password"
                label="Password"
                :type="showPassword ? 'text' : 'password'"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPassword = !showPassword"
                variant="outlined"
                density="comfortable"
                :rules="[v => !!v || 'Password is required']"
                required
                class="mb-4 custom-field"
              ></v-text-field>

              <v-btn
                type="submit"
                color="primary"
                size="large"
                block
                rounded="lg"
                :loading="authStore.loading"
                :disabled="!valid || authStore.loading"
                elevation="4"
              >
                Sign In
              </v-btn>
            </v-form>

            <v-alert
              type="info"
              variant="tonal"
              density="compact"
              class="mt-6 text-caption text-center rounded-lg"
            >
              Demo Credentials: <strong>test@test.com</strong> / <strong>password</strong>
            </v-alert>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import SummarySnackbar from '../components/SummarySnackbar.vue';

const email = ref('test@test.com');
const password = ref('password');
const showPassword = ref(false);
const valid = ref(false);

const authStore = useAuthStore();
const router = useRouter();

async function handleLogin() {
  if (!valid.value) return;
  try {
    await authStore.login(email.value, password.value);
    router.push('/dashboard');
  } catch (e) {
    // Handled in store
  }
}
</script>

<style scoped>
.glass-card {
  background: rgba(30, 41, 59, 0.85) !important;
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}
.custom-field :deep(.v-field) {
  border-radius: 12px;
}
</style>
