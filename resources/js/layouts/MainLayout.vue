<template>
  <v-app :theme="currentTheme">
    <!-- Summary Snackbar -->
    <SummarySnackbar />

    <!-- Navigation Drawer -->
    <v-navigation-drawer
      v-model="drawer"
      expand-on-hover
      permanent
      rail
      elevation="2"
      class="nav-drawer"
    >
      <v-list>
        <v-list-item
          prepend-icon="mdi-fish"
          base-color="blue"
          :subtitle="authStore.user?.email"
          :title="authStore.user?.name"
        ></v-list-item>
      </v-list>

      <v-divider></v-divider>

      <v-list density="compact" nav>

        <!-- Dashboard -->
        <v-list-item
          to="/dashboard"
          prepend-icon="mdi-view-dashboard"
          :title="$t('nav.dashboard')"
          value="dashboard"
          color="primary"
          rounded="lg"
        ></v-list-item>

        <!-- Warehouse Operations -->
        <v-list-item
          to="/warehouse-operations"
          prepend-icon="mdi-file-document-outline"
          :title="$t('nav.warehouse_operations')"
          value="warehouse_operations"
          color="primary"
          rounded="lg"
        ></v-list-item>

        <!-- Fish Warehouse -->
        <v-list-item
          to="/fish-warehouse"
          prepend-icon="mdi-snowflake"
          :title="$t('nav.fish_warehouse')"
          value="fish_warehouse"
          color="primary"
          rounded="lg"
        ></v-list-item>

        <!-- Consumable Warehouse -->
        <v-list-item
          to="/consumable-warehouse"
          prepend-icon="mdi-package-variant-closed"
          :title="$t('nav.consumable_warehouse')"
          value="consumable_warehouse"
          color="primary"
          rounded="lg"
        ></v-list-item>

        <!-- Workforce -->
        <v-list-item
          to="/workforce"
          prepend-icon="mdi-account-group"
          :title="$t('nav.workforce')"
          value="workforce"
          color="primary"
          rounded="lg"
        ></v-list-item>

        <!-- Settings Group -->
        <v-list-group value="settings">
          <template #activator="{ props }">
            <v-list-item
              v-bind="props"
              prepend-icon="mdi-cog"
              :title="$t('nav.settings')"
              rounded="lg"
            ></v-list-item>
          </template>

          <v-list-item
            to="/settings/providers"
            prepend-icon="mdi-truck-delivery"
            :title="$t('nav.providers')"
            value="providers"
            rounded="lg"
          ></v-list-item>

          <v-list-item
            to="/settings/clients"
            prepend-icon="mdi-domain"
            :title="$t('nav.clients')"
            value="clients"
            rounded="lg"
          ></v-list-item>

          <v-list-item
            to="/settings/freezing-fish"
            prepend-icon="mdi-fish"
            :title="$t('nav.freezing_fish')"
            value="freezing_fish"
            rounded="lg"
          ></v-list-item>

          <v-list-item
            to="/settings/consumable-types"
            prepend-icon="mdi-archive"
            :title="$t('nav.consumable_types')"
            value="consumable_types"
            rounded="lg"
          ></v-list-item>

          <v-list-item
            to="/settings/fish-warehouses"
            prepend-icon="mdi-warehouse"
            :title="$t('nav.fish_warehouses')"
            value="fish_warehouses"
            rounded="lg"
          ></v-list-item>

          <v-list-item
            to="/settings/voucher-types"
            prepend-icon="mdi-file-cog"
            :title="$t('nav.voucher_types')"
            value="voucher_types"
            rounded="lg"
          ></v-list-item>

          <v-list-item
            to="/settings/containers"
            prepend-icon="mdi-cube-outline"
            :title="$t('nav.containers')"
            value="containers"
            rounded="lg"
          ></v-list-item>

          <v-list-item
            to="/settings/workforces"
            prepend-icon="mdi-account-hard-hat"
            :title="$t('nav.workforces')"
            value="workforces"
            rounded="lg"
          ></v-list-item>
        </v-list-group>
      </v-list>
    </v-navigation-drawer>

    <!-- Top App Bar -->
    <v-app-bar app flat border-b class="px-2">
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>

      <v-toolbar-title class="font-weight-bold text-subtitle-1">
        {{ $t('app_name') }}
      </v-toolbar-title>

      <v-spacer></v-spacer>

      <!-- Language Selector -->
      <v-menu location="bottom end">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" prepend-icon="mdi-translate">
            {{ currentLangUpper }}
          </v-btn>
        </template>
        <v-list density="compact" class="rounded-lg">
          <v-list-item @click="changeLang('en')" :active="currentLang === 'en'">
            <v-list-item-title>English</v-list-item-title>
          </v-list-item>
          <v-list-item @click="changeLang('fr')" :active="currentLang === 'fr'">
            <v-list-item-title>Français</v-list-item-title>
          </v-list-item>
          <v-list-item @click="changeLang('es')" :active="currentLang === 'es'">
            <v-list-item-title>Español</v-list-item-title>
          </v-list-item>
          <v-list-item @click="changeLang('ar')" :active="currentLang === 'ar'">
            <v-list-item-title>العربية</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>

      <!-- Theme Toggle -->
      <v-btn
        icon
        variant="text"
        @click="toggleTheme"
        :title="currentTheme === 'dark' ? 'Light Mode' : 'Dark Mode'"
      >
        <v-icon>{{ currentTheme === 'dark' ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
      </v-btn>

      <!-- User Menu -->
      <v-menu location="bottom end">
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" class="ml-2">
            <v-avatar color="primary" size="32" class="mr-2">
              <span class="text-caption font-weight-bold text-white">
                {{ userInitials }}
              </span>
            </v-avatar>
            <span class="d-none d-sm-inline">{{ authStore.user?.name || 'User' }}</span>
          </v-btn>
        </template>
        <v-list density="compact" class="rounded-lg">
          <v-list-item prepend-icon="mdi-account" :title="authStore.user?.email || 'User'"></v-list-item>
          <v-divider></v-divider>
          <v-list-item prepend-icon="mdi-logout" :title="$t('nav.logout')" color="error" @click="handleLogout"></v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Main Content Area -->
    <v-main class="bg-background">
      <v-container fluid class="pa-4 pa-sm-6">
        <router-view></router-view>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useTheme, useLocale } from 'vuetify';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import SummarySnackbar from '../components/SummarySnackbar.vue';

const drawer = ref(true);
const authStore = useAuthStore();
const router = useRouter();

const theme = useTheme();
const { locale } = useI18n();

const currentTheme = computed(() => theme.global.name.value);
const currentLang = computed(() => locale.value);
const currentLangUpper = computed(() => locale.value.toUpperCase());

const userInitials = computed(() => {
  const name = authStore.user?.name || 'User';
  return name.slice(0, 2).toUpperCase();
});

function toggleTheme() {
  const newTheme = theme.global.name.value === 'dark' ? 'light' : 'dark';
  theme.global.name.value = newTheme;
  localStorage.setItem('app_theme', newTheme);
}

function changeLang(lang) {
  locale.value = lang;
  localStorage.setItem('app_lang', lang);

  // Set RTL for Arabic
  if (lang === 'ar') {
    document.documentElement.dir = 'rtl';
    document.documentElement.lang = 'ar';
  } else {
    document.documentElement.dir = 'ltr';
    document.documentElement.lang = lang;
  }
}

onMounted(() => {
  const savedLang = localStorage.getItem('app_lang') || 'en';
  changeLang(savedLang);
});

async function handleLogout() {
  await authStore.logout();
  router.push('/login');
}
</script>

<style scoped>
.nav-drawer {
  border-right: 1px solid rgba(255, 255, 255, 0.08);
}
</style>
