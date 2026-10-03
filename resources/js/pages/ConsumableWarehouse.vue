<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">{{ $t('consumable_warehouse.title') }}</h1>
        <div class="text-caption text-grey">Track consumable receipts, consumption, and current stock</div>
      </div>
      <div class="d-flex align-center ga-3 mt-4 mt-sm-0">
        <v-btn color="primary" variant="tonal" prepend-icon="mdi-refresh" @click="refreshData" rounded="lg">
          {{ $t('common.refresh') }}
        </v-btn>
      </div>
    </div>

    <!-- Consumables Table Card -->
    <v-card class="pa-4 rounded-xl border elevation-2">
      <!-- Search Bar -->
      <v-text-field
        v-model="search"
        prepend-inner-icon="mdi-magnify"
        :placeholder="$t('common.search')"
        density="comfortable"
        variant="outlined"
        hide-details
        class="mb-4"
        clearable
      ></v-text-field>

      <!-- Data Table -->
      <v-data-table-virtual
        :headers="headers"
        :items="filteredConsumables"
        :loading="stockStore.loading"
        class="bg-transparent"
      >
        <template #header.consumable_name="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="settingsStore.consumableTypes"
            v-model="filterConsumables"
          />
        </template>

        <template #header.unit="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="unitOptions"
            v-model="filterUnits"
          />
        </template>

        <template #header.status="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="['Normal', 'Low Stock']"
            v-model="filterStatus"
          />
        </template>

        <template #item.consumable_name="{ item }">
          <span class="font-weight-bold color-primary">{{ item.consumable_name }}</span>
        </template>

        <template #item.unit="{ item }">
          <v-chip size="x-small" variant="tonal" color="info">
            {{ item.unit }}
          </v-chip>
        </template>

        <template #item.total_received="{ item }">
          {{ Number(item.total_received).toLocaleString() }}
        </template>

        <template #item.total_consumed="{ item }">
          <span class="text-warning font-weight-bold">
            {{ Number(item.total_consumed).toLocaleString() }}
          </span>
        </template>

        <template #item.current_quantity="{ item }">
          <span class="font-weight-bold text-h6 text-success">
            {{ Number(item.current_quantity).toLocaleString() }}
          </span>
        </template>

        <template #item.status="{ item }">
          <v-chip
            size="small"
            :color="item.current_quantity <= 10 ? 'error' : 'success'"
            variant="tonal"
          >
            {{ item.current_quantity <= 10 ? 'Low Stock' : 'Normal' }}
          </v-chip>
        </template>
      </v-data-table-virtual>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStockStore } from '../stores/stock';
import { useSettingsStore } from '../stores/settings';
import AppHeaderFilter from '../components/AppHeaderFilter.vue';

const stockStore = useStockStore();
const settingsStore = useSettingsStore();

const search = ref('');
const filterConsumables = ref([]);
const filterUnits = ref([]);
const filterStatus = ref([]);

const unitOptions = computed(() => {
  const units = (stockStore.consumableStocks || []).map(item => item.unit).filter(Boolean);
  return Array.from(new Set(units));
});

const headers = [
  { title: 'Consumable Type', key: 'consumable_name', sortable: true },
  { title: 'Unit', key: 'unit', sortable: true },
  { title: 'Total Received', key: 'total_received', sortable: true },
  { title: 'Total Consumed', key: 'total_consumed', sortable: true },
  { title: 'Current Quantity', key: 'current_quantity', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
];

const filteredConsumables = computed(() => {
  return stockStore.consumableStocks.filter(item => {
    if (filterConsumables.value.length > 0 && !filterConsumables.value.includes(item.consumable_type_id)) {
      return false;
    }
    if (filterUnits.value.length > 0 && !filterUnits.value.includes(item.unit)) {
      return false;
    }
    const itemStatus = item.current_quantity <= 10 ? 'Low Stock' : 'Normal';
    if (filterStatus.value.length > 0 && !filterStatus.value.includes(itemStatus)) {
      return false;
    }
    if (search.value) {
      const q = search.value.toLowerCase();
      return item.consumable_name.toLowerCase().includes(q);
    }
    return true;
  });
});

function refreshData() {
  stockStore.fetchConsumableStocks();
  settingsStore.fetchAllSettings();
}

onMounted(() => {
  refreshData();
});
</script>
