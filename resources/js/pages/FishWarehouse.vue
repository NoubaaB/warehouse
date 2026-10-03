<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">{{ $t('fish_warehouse.title') }}</h1>
        <div class="text-caption text-grey">Traceable freezing fish stock lots with full reception history</div>
      </div>
      <div class="d-flex align-center ga-3 mt-4 mt-sm-0">
        <v-btn color="primary" variant="tonal" prepend-icon="mdi-refresh" @click="refreshData" rounded="lg">
          {{ $t('common.refresh') }}
        </v-btn>
      </div>
    </div>

    <!-- Main Card with Active vs Archived Tabs -->
    <v-card class="pa-4 rounded-xl border elevation-2">
      <div class="d-flex align-center justify-space-between border-b pb-3 mb-4">
        <v-tabs v-model="tab" color="primary">
          <v-tab value="active">
            <v-icon icon="mdi-package-variant" class="mr-2"></v-icon>
            {{ $t('fish_warehouse.active_tab') }} ({{ filteredActiveStock.length }})
          </v-tab>
          <v-tab value="archived">
            <v-icon icon="mdi-archive" class="mr-2"></v-icon>
            {{ $t('fish_warehouse.archived_tab') }} ({{ filteredArchivedStock.length }})
          </v-tab>
        </v-tabs>

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

      <!-- ACTIVE STOCK LOTS DATATABLE -->
      <v-window v-model="tab">
        <v-window-item value="active">
          <v-data-table-virtual
            :headers="activeHeaders"
            :items="filteredActiveStock"
            :loading="stockStore.loading"
            class="bg-transparent"
          >
            <!-- Header Filter Slots for Cumulative Multi-Select Header Filtering -->
            <template #header.freezing_fish="{ column }">
              <AppHeaderFilter
                :title="column.title"
                :options="settingsStore.freezingFish"
                v-model="filterFish"
              />
            </template>

            <template #header.fish_warehouse="{ column }">
              <AppHeaderFilter
                :title="column.title"
                :options="settingsStore.fishWarehouses"
                v-model="filterWarehouse"
              />
            </template>

            <template #header.voucher="{ column }">
              <AppHeaderFilter
                :title="column.title"
                :options="voucherOptions"
                v-model="filterVoucher"
              />
            </template>

            <template #header.container="{ column }">
              <AppHeaderFilter
                :title="column.title"
                :options="settingsStore.containers"
                v-model="filterContainer"
              />
            </template>

            <!-- Item Slots -->
            <template #item.freezing_fish="{ item }">
              <span class="font-weight-bold color-primary">{{ item.freezing_fish?.name }}</span>
            </template>

            <template #item.fish_warehouse="{ item }">
              {{ item.fish_warehouse?.name }}
            </template>

            <template #item.voucher="{ item }">
              <v-chip size="x-small" variant="tonal" color="info">
                {{ item.voucher?.voucher_number }}
              </v-chip>
            </template>

            <template #item.original_quantity="{ item }">
              {{ Number(item.original_quantity).toLocaleString() }} kg
            </template>

            <template #item.remaining_quantity="{ item }">
              <span class="font-weight-bold text-success">
                {{ Number(item.remaining_quantity).toLocaleString() }} kg
              </span>
            </template>

            <template #item.container="{ item }">
              {{ item.container?.name || 'N/A' }}
            </template>

            <template #item.calculated_boxes="{ item }">
              {{ item.calculated_boxes }}
            </template>

            <template #item.status="{ item }">
              <v-chip size="x-small" color="success" variant="elevated">
                Active
              </v-chip>
            </template>
          </v-data-table-virtual>
        </v-window-item>

        <!-- ARCHIVED STOCK LOTS DATATABLE -->
        <v-window-item value="archived">
          <v-data-table-virtual
            :headers="archivedHeaders"
            :items="filteredArchivedStock"
            :loading="stockStore.loading"
            class="bg-transparent"
          >
            <template #header.freezing_fish="{ column }">
              <AppHeaderFilter
                :title="column.title"
                :options="settingsStore.freezingFish"
                v-model="filterFish"
              />
            </template>

            <template #header.fish_warehouse="{ column }">
              <AppHeaderFilter
                :title="column.title"
                :options="settingsStore.fishWarehouses"
                v-model="filterWarehouse"
              />
            </template>

            <template #header.voucher="{ column }">
              <AppHeaderFilter
                :title="column.title"
                :options="voucherOptions"
                v-model="filterVoucher"
              />
            </template>

            <template #item.freezing_fish="{ item }">
              <span class="font-weight-bold text-grey">{{ item.freezing_fish?.name }}</span>
            </template>

            <template #item.fish_warehouse="{ item }">
              {{ item.fish_warehouse?.name }}
            </template>

            <template #item.voucher="{ item }">
              <v-chip size="x-small" variant="tonal" color="grey">
                {{ item.voucher?.voucher_number }}
              </v-chip>
            </template>

            <template #item.original_quantity="{ item }">
              {{ Number(item.original_quantity).toLocaleString() }} kg
            </template>

            <template #item.final_quantity="{ item }">
              <span class="font-weight-bold text-error">0 kg</span>
            </template>

            <template #item.status="{ item }">
              <v-chip size="x-small" color="grey" variant="elevated">
                Archived (Sold Out)
              </v-chip>
            </template>
          </v-data-table-virtual>
        </v-window-item>
      </v-window>
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

const tab = ref('active');
const search = ref('');

// Header Filter selections
const filterFish = ref([]);
const filterWarehouse = ref([]);
const filterVoucher = ref([]);
const filterContainer = ref([]);

const voucherOptions = computed(() => {
  const vMap = new Map();
  [...stockStore.activeFishStocks, ...stockStore.archivedFishStocks].forEach(item => {
    if (item.voucher?.id && item.voucher?.voucher_number) {
      vMap.set(item.voucher.id, { id: item.voucher.id, name: item.voucher.voucher_number });
    }
  });
  return Array.from(vMap.values());
});

const activeHeaders = [
  { title: 'Fish Type', key: 'freezing_fish', sortable: true },
  { title: 'Warehouse', key: 'fish_warehouse', sortable: true },
  { title: 'Source Voucher', key: 'voucher', sortable: true },
  { title: 'Reception Date', key: 'reception_date', sortable: true },
  { title: 'Provider(s)', key: 'provider_names', sortable: true },
  { title: 'Original Qty', key: 'original_quantity', sortable: true },
  { title: 'Remaining Qty', key: 'remaining_quantity', sortable: true },
  { title: 'Container', key: 'container', sortable: true },
  { title: 'Calculated Boxes', key: 'calculated_boxes', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
];

const archivedHeaders = [
  { title: 'Fish Type', key: 'freezing_fish', sortable: true },
  { title: 'Warehouse', key: 'fish_warehouse', sortable: true },
  { title: 'Source Voucher', key: 'voucher', sortable: true },
  { title: 'Reception Date', key: 'reception_date', sortable: true },
  { title: 'Provider(s)', key: 'provider_names', sortable: true },
  { title: 'Original Qty', key: 'original_quantity', sortable: true },
  { title: 'Final Qty', key: 'final_quantity', sortable: true },
  { title: 'Archived Date', key: 'archived_at', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
];

const hasActiveFilters = computed(() => {
  return (
    filterFish.value.length > 0 ||
    filterWarehouse.value.length > 0 ||
    filterVoucher.value.length > 0 ||
    filterContainer.value.length > 0 ||
    search.value !== ''
  );
});

function resetAllFilters() {
  filterFish.value = [];
  filterWarehouse.value = [];
  filterVoucher.value = [];
  filterContainer.value = [];
  search.value = '';
}

// Cumulative Header Filtering for Active Stock
const filteredActiveStock = computed(() => {
  return stockStore.activeFishStocks.filter(item => {
    if (filterFish.value.length > 0 && !filterFish.value.includes(item.freezing_fish_id)) {
      return false;
    }
    if (filterWarehouse.value.length > 0 && !filterWarehouse.value.includes(item.fish_warehouse_id)) {
      return false;
    }
    if (filterVoucher.value.length > 0 && !filterVoucher.value.includes(item.voucher_id)) {
      return false;
    }
    if (filterContainer.value.length > 0 && !filterContainer.value.includes(item.container_id)) {
      return false;
    }
    if (search.value) {
      const q = search.value.toLowerCase();
      const fishName = item.freezing_fish?.name?.toLowerCase() || '';
      const whName = item.fish_warehouse?.name?.toLowerCase() || '';
      const vNum = item.voucher?.voucher_number?.toLowerCase() || '';
      const pNames = item.provider_names?.toLowerCase() || '';
      return fishName.includes(q) || whName.includes(q) || vNum.includes(q) || pNames.includes(q);
    }
    return true;
  });
});

// Cumulative Filtering for Archived Stock
const filteredArchivedStock = computed(() => {
  return stockStore.archivedFishStocks.filter(item => {
    if (filterFish.value.length > 0 && !filterFish.value.includes(item.freezing_fish_id)) {
      return false;
    }
    if (filterWarehouse.value.length > 0 && !filterWarehouse.value.includes(item.fish_warehouse_id)) {
      return false;
    }
    if (filterVoucher.value.length > 0 && !filterVoucher.value.includes(item.voucher_id)) {
      return false;
    }
    if (search.value) {
      const q = search.value.toLowerCase();
      const fishName = item.freezing_fish?.name?.toLowerCase() || '';
      const whName = item.fish_warehouse?.name?.toLowerCase() || '';
      return fishName.includes(q) || whName.includes(q);
    }
    return true;
  });
});

function refreshData() {
  stockStore.fetchActiveFishStocks();
  stockStore.fetchArchivedFishStocks();
  settingsStore.fetchAllSettings();
}

onMounted(() => {
  refreshData();
});
</script>
