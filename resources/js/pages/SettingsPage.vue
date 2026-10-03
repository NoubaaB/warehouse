<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">{{ $t('nav.settings') }}</h1>
        <div class="text-caption text-grey">Manage master parameters, entities, containers, and voucher types</div>
      </div>
      <div class="d-flex align-center ga-3 mt-4 mt-sm-0">
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateModal" rounded="lg" class="elevation-1">
          {{ $t('settings.add_new') }}
        </v-btn>
      </div>
    </div>

    <!-- Settings Navigation Tabs -->
    <v-card class="pa-4 rounded-xl border elevation-2">
      <v-tabs v-model="currentSection" color="primary" show-arrows class="border-b mb-4">
        <v-tab value="providers" to="/settings/providers">{{ $t('nav.providers') }}</v-tab>
        <v-tab value="clients" to="/settings/clients">{{ $t('nav.clients') }}</v-tab>
        <v-tab value="freezing-fish" to="/settings/freezing-fish">{{ $t('nav.freezing_fish') }}</v-tab>
        <v-tab value="consumable-types" to="/settings/consumable-types">{{ $t('nav.consumable_types') }}</v-tab>
        <v-tab value="fish-warehouses" to="/settings/fish-warehouses">{{ $t('nav.fish_warehouses') }}</v-tab>
        <v-tab value="voucher-types" to="/settings/voucher-types">{{ $t('nav.voucher_types') }}</v-tab>
        <v-tab value="containers" to="/settings/containers">{{ $t('nav.containers') }}</v-tab>
        <v-tab value="workforces" to="/settings/workforces">{{ $t('nav.workforces') }}</v-tab>
      </v-tabs>

      <!-- Search & Reset Filters Bar -->
      <div class="d-flex align-center justify-space-between mb-4">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          :placeholder="$t('common.search')"
          density="comfortable"
          variant="outlined"
          hide-details
          style="max-width: 320px;"
          clearable
        ></v-text-field>

        <v-btn
          v-if="hasActiveFilters"
          size="small"
          variant="text"
          color="error"
          prepend-icon="mdi-filter-off"
          @click="resetAllFilters"
        >
          {{ $t('common.reset_filters') }}
        </v-btn>
      </div>

      <!-- Active Section Table -->
      <v-data-table-virtual
        :headers="activeHeaders"
        :items="filteredItems"
        :loading="settingsStore.loading"
        class="bg-transparent"
      >
        <template #header.name="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="nameOptions"
            v-model="filterName"
          />
        </template>

        <template #header.code="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="codeOptions"
            v-model="filterCode"
          />
        </template>

        <template #header.effect="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="effectOptions"
            v-model="filterEffect"
          />
        </template>

        <template #header.unit="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="unitOptions"
            v-model="filterUnit"
          />
        </template>

        <template #header.active="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="statusOptions"
            v-model="filterActive"
          />
        </template>

        <template #item.active="{ item }">
          <v-chip size="x-small" :color="item.active ? 'success' : 'grey'" variant="elevated">
            {{ item.active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" size="small" variant="text" color="primary" @click="openEditModal(item)"></v-btn>
          <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="confirmDelete(item)"></v-btn>
        </template>
      </v-data-table-virtual>
    </v-card>

    <!-- CREATE / EDIT DIALOG -->
    <v-dialog v-model="editDialog" max-width="500" persistentScroll>
      <v-card class="rounded-xl pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div class="text-h6 font-weight-bold color-primary">
            {{ isEditing ? 'Edit Record' : 'Create New Record' }}
          </div>
          <v-btn icon="mdi-close" variant="text" @click="editDialog = false"></v-btn>
        </div>

        <v-form ref="form" v-model="formValid" @submit.prevent="saveRecord">
          <!-- Common Name Field -->
          <v-text-field
            v-model="formData.name"
            :label="$t('settings.name')"
            variant="outlined"
            density="comfortable"
            :rules="[v => !!v || 'Name is required']"
            required
            class="mb-3"
          ></v-text-field>

          <!-- Specific Fields based on active section -->
          <template v-if="activeSectionKey === 'providers' || activeSectionKey === 'clients'">
            <v-text-field
              v-model="formData.contact_info"
              :label="$t('settings.contact')"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-text-field>
          </template>

          <template v-if="activeSectionKey === 'freezing-fish'">
            <v-text-field
              v-model="formData.code"
              :label="$t('settings.code')"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-text-field>
          </template>

          <template v-if="activeSectionKey === 'consumable-types'">
            <v-text-field
              v-model="formData.unit"
              :label="$t('settings.unit')"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-text-field>
          </template>

          <template v-if="activeSectionKey === 'fish-warehouses'">
            <v-textarea
              v-model="formData.description"
              label="Description"
              variant="outlined"
              density="comfortable"
              rows="2"
              class="mb-3"
            ></v-textarea>
          </template>

          <template v-if="activeSectionKey === 'containers'">
            <v-text-field
              v-model.number="formData.capacity"
              :label="$t('settings.capacity')"
              type="number"
              step="0.01"
              variant="outlined"
              density="comfortable"
              :rules="[v => v > 0 || 'Capacity must be > 0']"
              required
              class="mb-3"
            ></v-text-field>
            <v-text-field
              v-model="formData.unit"
              :label="$t('settings.unit')"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-text-field>
          </template>

          <template v-if="activeSectionKey === 'voucher-types'">
            <v-text-field
              v-model="formData.code"
              :label="$t('settings.code')"
              variant="outlined"
              density="comfortable"
              :rules="[v => !!v || 'Code required']"
              required
              class="mb-3"
            ></v-text-field>

            <v-select
              v-model="formData.effect"
              :items="[
                { title: 'Stock In (Reception)', value: 'stock_in' },
                { title: 'Stock Out (Sales)', value: 'stock_out' },
                { title: 'Consumable Stock In', value: 'consumable_stock_in' }
              ]"
              item-title="title"
              item-value="value"
              :label="$t('settings.effect')"
              variant="outlined"
              density="comfortable"
              required
              class="mb-3"
            ></v-select>
          </template>

          <template v-if="activeSectionKey === 'workforces'">
            <v-text-field
              v-model="formData.identifier"
              :label="$t('settings.identifier')"
              variant="outlined"
              density="comfortable"
              class="mb-3"
            ></v-text-field>
          </template>

          <v-switch
            v-model="formData.active"
            label="Active Status"
            color="primary"
            hide-details
            class="mb-4"
          ></v-switch>

          <div class="d-flex justify-end gap-2">
            <v-btn variant="text" @click="editDialog = false">{{ $t('common.cancel') }}</v-btn>
            <v-btn color="primary" type="submit" :loading="settingsStore.loading">
              {{ $t('common.save') }}
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useSettingsStore } from '../stores/settings';
import AppHeaderFilter from '../components/AppHeaderFilter.vue';

const settingsStore = useSettingsStore();
const route = useRoute();

const currentSection = ref('providers');
const search = ref('');

const filterName = ref([]);
const filterCode = ref([]);
const filterEffect = ref([]);
const filterUnit = ref([]);
const filterActive = ref([]);

const statusOptions = [
  { title: 'Active', value: true },
  { title: 'Inactive', value: false },
];

const activeSectionKey = computed(() => {
  const path = route.path.split('/').pop();
  return path || 'providers';
});

// Reset filters when tab changes
watch(activeSectionKey, () => {
  resetAllFilters();
});

const activeItems = computed(() => {
  const key = activeSectionKey.value;
  if (key === 'providers') return settingsStore.providers;
  if (key === 'clients') return settingsStore.clients;
  if (key === 'freezing-fish') return settingsStore.freezingFish;
  if (key === 'consumable-types') return settingsStore.consumableTypes;
  if (key === 'fish-warehouses') return settingsStore.fishWarehouses;
  if (key === 'voucher-types') return settingsStore.voucherTypes;
  if (key === 'containers') return settingsStore.containers;
  if (key === 'workforces') return settingsStore.workforces;
  return [];
});

const nameOptions = computed(() => {
  return activeItems.value.map(i => i.name).filter(Boolean);
});

const codeOptions = computed(() => {
  return activeItems.value.map(i => i.code).filter(Boolean);
});

const effectOptions = computed(() => {
  const effs = activeItems.value.map(i => i.effect).filter(Boolean);
  return Array.from(new Set(effs));
});

const unitOptions = computed(() => {
  const units = activeItems.value.map(i => i.unit).filter(Boolean);
  return Array.from(new Set(units));
});

const hasActiveFilters = computed(() => {
  return (
    filterName.value.length > 0 ||
    filterCode.value.length > 0 ||
    filterEffect.value.length > 0 ||
    filterUnit.value.length > 0 ||
    filterActive.value.length > 0 ||
    search.value !== ''
  );
});

function resetAllFilters() {
  filterName.value = [];
  filterCode.value = [];
  filterEffect.value = [];
  filterUnit.value = [];
  filterActive.value = [];
  search.value = '';
}

const filteredItems = computed(() => {
  return activeItems.value.filter(item => {
    if (filterName.value.length > 0 && !filterName.value.includes(item.name)) {
      return false;
    }
    if (filterCode.value.length > 0 && !filterCode.value.includes(item.code)) {
      return false;
    }
    if (filterEffect.value.length > 0 && !filterEffect.value.includes(item.effect)) {
      return false;
    }
    if (filterUnit.value.length > 0 && !filterUnit.value.includes(item.unit)) {
      return false;
    }
    if (filterActive.value.length > 0 && !filterActive.value.includes(Boolean(item.active))) {
      return false;
    }
    if (search.value) {
      const q = search.value.toLowerCase();
      const n = item.name ? item.name.toLowerCase() : '';
      const c = item.code ? item.code.toLowerCase() : '';
      const ci = item.contact_info ? item.contact_info.toLowerCase() : '';
      return n.includes(q) || c.includes(q) || ci.includes(q);
    }
    return true;
  });
});

const activeHeaders = computed(() => {
  const key = activeSectionKey.value;
  if (key === 'containers') {
    return [
      { title: 'Name', key: 'name', sortable: true },
      { title: 'Capacity', key: 'capacity', sortable: true },
      { title: 'Unit', key: 'unit', sortable: true },
      { title: 'Status', key: 'active', sortable: true },
      { title: 'Actions', key: 'actions', sortable: false },
    ];
  }
  if (key === 'voucher-types') {
    return [
      { title: 'Name', key: 'name', sortable: true },
      { title: 'Code', key: 'code', sortable: true },
      { title: 'Stock Effect', key: 'effect', sortable: true },
      { title: 'Status', key: 'active', sortable: true },
      { title: 'Actions', key: 'actions', sortable: false },
    ];
  }
  return [
    { title: 'Name', key: 'name', sortable: true },
    { title: 'Status', key: 'active', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false },
  ];
});

// Create / Edit Dialog State
const editDialog = ref(false);
const isEditing = ref(false);
const formValid = ref(false);
const selectedId = ref(null);
const formData = ref({
  name: '',
  contact_info: '',
  code: '',
  unit: 'pcs',
  capacity: 24,
  description: '',
  effect: 'stock_in',
  identifier: '',
  active: true,
});

function openCreateModal() {
  isEditing.value = false;
  selectedId.value = null;
  formData.value = {
    name: '',
    contact_info: '',
    code: '',
    unit: 'pcs',
    capacity: 24,
    description: '',
    effect: 'stock_in',
    identifier: '',
    active: true,
  };
  editDialog.value = true;
}

function openEditModal(item) {
  isEditing.value = true;
  selectedId.value = item.id;
  formData.value = { ...item };
  editDialog.value = true;
}

async function saveRecord() {
  if (!formValid.value) return;
  const endpoint = activeSectionKey.value;
  if (isEditing.value) {
    await settingsStore.updateEntity(endpoint, selectedId.value, formData.value);
  } else {
    await settingsStore.createEntity(endpoint, formData.value);
  }
  editDialog.value = false;
}

async function confirmDelete(item) {
  if (confirm(`Are you sure you want to delete ${item.name}?`)) {
    const endpoint = activeSectionKey.value;
    await settingsStore.deleteEntity(endpoint, item.id);
  }
}

onMounted(() => {
  settingsStore.fetchAllSettings();
});
</script>
