<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6">
      <div class="d-flex align-center">
        <v-avatar color="primary" size="48" class="mr-4 elevation-2">
          <span class="text-h6 font-weight-bold text-white">{{ userInitials }}</span>
        </v-avatar>
        <div>
          <h1 class="text-h4 font-weight-bold">{{ $t('profile.title') }}</h1>
          <div class="text-caption text-grey">{{ $t('profile.subtitle') }}</div>
        </div>
      </div>
    </div>

    <v-row class="ga-0">
      <!-- Left Column: Personal Information -->
      <v-col cols="12" md="6" class="pr-md-3 mb-6 mb-md-0">
        <v-card class="pa-6 rounded-xl border elevation-2 bg-surface h-100">
          <div class="d-flex align-center mb-6">
            <v-icon icon="mdi-account-edit-outline" color="primary" size="large" class="mr-3"></v-icon>
            <div>
              <h2 class="text-h6 font-weight-bold">{{ $t('profile.personal_info') }}</h2>
              <div class="text-caption text-grey">{{ $t('profile.personal_info_desc') }}</div>
            </div>
          </div>

          <v-form ref="profileFormRef" v-model="isProfileValid" @submit.prevent="handleSaveProfile">
            <v-text-field
              v-model="profileForm.name"
              :label="$t('settings.name')"
              prepend-inner-icon="mdi-account"
              variant="outlined"
              density="comfortable"
              class="mb-4"
              :rules="[v => !!v || 'Name is required']"
              required
            ></v-text-field>

            <v-text-field
              v-model="profileForm.email"
              label="Email"
              prepend-inner-icon="mdi-email"
              type="email"
              variant="outlined"
              density="comfortable"
              class="mb-6"
              :rules="[
                v => !!v || 'Email is required',
                v => /.+@.+\..+/.test(v) || 'Must be a valid email'
              ]"
              required
            ></v-text-field>

            <div class="d-flex justify-end">
              <v-btn
                color="primary"
                type="submit"
                prepend-icon="mdi-content-save"
                rounded="lg"
                class="px-6 font-weight-bold"
                :loading="savingProfile"
              >
                {{ $t('profile.save_profile') }}
              </v-btn>
            </div>
          </v-form>
        </v-card>
      </v-col>

      <!-- Right Column: Security & Password -->
      <v-col cols="12" md="6" class="pl-md-3">
        <v-card class="pa-6 rounded-xl border elevation-2 bg-surface h-100">
          <div class="d-flex align-center mb-6">
            <v-icon icon="mdi-shield-lock-outline" color="warning" size="large" class="mr-3"></v-icon>
            <div>
              <h2 class="text-h6 font-weight-bold">{{ $t('profile.change_password') }}</h2>
              <div class="text-caption text-grey">{{ $t('profile.change_password_desc') }}</div>
            </div>
          </div>

          <v-form ref="passwordFormRef" v-model="isPasswordValid" @submit.prevent="handleUpdatePassword">
            <v-text-field
              v-model="passwordForm.current_password"
              :label="$t('profile.current_password')"
              prepend-inner-icon="mdi-lock-outline"
              :type="showCurrentPassword ? 'text' : 'password'"
              :append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showCurrentPassword = !showCurrentPassword"
              variant="outlined"
              density="comfortable"
              class="mb-4"
              :rules="[v => !!v || 'Current password is required']"
              required
            ></v-text-field>

            <v-text-field
              v-model="passwordForm.password"
              :label="$t('profile.new_password')"
              prepend-inner-icon="mdi-lock-reset"
              :type="showNewPassword ? 'text' : 'password'"
              :append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showNewPassword = !showNewPassword"
              variant="outlined"
              density="comfortable"
              class="mb-4"
              :rules="[
                v => !!v || 'New password is required',
                v => v.length >= 8 || 'Password must be at least 8 characters'
              ]"
              required
            ></v-text-field>

            <v-text-field
              v-model="passwordForm.password_confirmation"
              :label="$t('profile.confirm_password')"
              prepend-inner-icon="mdi-lock-check"
              :type="showConfirmPassword ? 'text' : 'password'"
              :append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showConfirmPassword = !showConfirmPassword"
              variant="outlined"
              density="comfortable"
              class="mb-6"
              :rules="[
                v => !!v || 'Password confirmation is required',
                v => v === passwordForm.password || 'Passwords do not match'
              ]"
              required
            ></v-text-field>

            <div class="d-flex justify-end">
              <v-btn
                color="warning"
                type="submit"
                prepend-icon="mdi-key-change"
                rounded="lg"
                class="px-6 font-weight-bold"
                :loading="savingPassword"
              >
                {{ $t('profile.update_password') }}
              </v-btn>
            </div>
          </v-form>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();

const profileFormRef = ref(null);
const isProfileValid = ref(false);
const savingProfile = ref(false);

const profileForm = ref({
  name: '',
  email: '',
});

const passwordFormRef = ref(null);
const isPasswordValid = ref(false);
const savingPassword = ref(false);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const userInitials = computed(() => {
  const name = authStore.user?.name || 'User';
  return name.slice(0, 2).toUpperCase();
});

onMounted(() => {
  if (authStore.user) {
    profileForm.value.name = authStore.user.name || '';
    profileForm.value.email = authStore.user.email || '';
  }
});

async function handleSaveProfile() {
  if (!isProfileValid.value) return;
  savingProfile.value = true;
  try {
    await authStore.updateProfile({
      name: profileForm.value.name,
      email: profileForm.value.email,
    });
  } catch (e) {
    // Handled in store via snackbar
  } finally {
    savingProfile.value = false;
  }
}

async function handleUpdatePassword() {
  if (!isPasswordValid.value) return;
  savingPassword.value = true;
  try {
    await authStore.updatePassword({
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    });
    // Reset password form after success
    passwordForm.value.current_password = '';
    passwordForm.value.password = '';
    passwordForm.value.password_confirmation = '';
    passwordFormRef.value?.resetValidation();
  } catch (e) {
    // Handled in store via snackbar
  } finally {
    savingPassword.value = false;
  }
}
</script>
