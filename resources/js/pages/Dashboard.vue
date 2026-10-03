<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">{{ $t('dashboard.title') }}</h1>
        <div class="text-caption text-grey">Real-time freezing fish warehouse statistics and insights</div>
      </div>
      <div class="d-flex align-center ga-3 mt-4 mt-sm-0">
        <v-btn color="primary" variant="tonal" prepend-icon="mdi-refresh" @click="loadData" :loading="dashboardStore.loading" rounded="lg">
          {{ $t('common.refresh') }}
        </v-btn>
      </div>
    </div>

    <!-- Stat Cards Grid -->
    <v-row class="mb-6" v-if="stats">
      <!-- Total Fish Stock -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 rounded-xl border elevation-2">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption text-grey font-weight-bold text-uppercase">{{ $t('dashboard.total_fish_stock') }}</span>
            <v-avatar color="primary" variant="tonal" size="36">
              <v-icon icon="mdi-fish" color="primary"></v-icon>
            </v-avatar>
          </div>
          <div class="text-h4 font-weight-black color-primary">{{ stats.total_fish_kg.toLocaleString() }} <span class="text-caption">kg</span></div>
          <div class="text-caption text-grey mt-1">across active freezer rooms</div>
        </v-card>
      </v-col>

      <!-- Active Stock Lots -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 rounded-xl border elevation-2">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption text-grey font-weight-bold text-uppercase">{{ $t('dashboard.active_lots') }}</span>
            <v-avatar color="success" variant="tonal" size="36">
              <v-icon icon="mdi-package-variant" color="success"></v-icon>
            </v-avatar>
          </div>
          <div class="text-h4 font-weight-black text-success">{{ stats.active_lots_count }}</div>
          <div class="text-caption text-grey mt-1">Traceable active lots</div>
        </v-card>
      </v-col>

      <!-- Archived Lots -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 rounded-xl border elevation-2">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption text-grey font-weight-bold text-uppercase">{{ $t('dashboard.archived_lots') }}</span>
            <v-avatar color="warning" variant="tonal" size="36">
              <v-icon icon="mdi-archive" color="warning"></v-icon>
            </v-avatar>
          </div>
          <div class="text-h4 font-weight-black text-warning">{{ stats.archived_count }}</div>
          <div class="text-caption text-grey mt-1">Sold-out lots in archive</div>
        </v-card>
      </v-col>

      <!-- Total Vouchers -->
      <v-col cols="12" sm="6" md="3">
        <v-card class="pa-4 rounded-xl border elevation-2">
          <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-caption text-grey font-weight-bold text-uppercase">Total Operations</span>
            <v-avatar color="info" variant="tonal" size="36">
              <v-icon icon="mdi-file-document-multiple" color="info"></v-icon>
            </v-avatar>
          </div>
          <div class="text-h4 font-weight-black text-info">{{ stats.vouchers_stats.total_vouchers }}</div>
          <div class="text-caption text-grey mt-1">Registered vouchers</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts & Breakdown Section -->
    <v-row class="mb-6" v-if="stats">
      <!-- ApexCharts Donut Chart: Warehouse Stock Distribution -->
      <v-col cols="12" md="6">
        <v-card class="pa-5 rounded-xl border elevation-2 h-100">
          <div class="text-subtitle-1 font-weight-bold mb-4 d-flex align-center">
            <v-icon icon="mdi-chart-donut" color="primary" class="mr-2"></v-icon>
            {{ $t('dashboard.stock_distribution') }}
          </div>

          <div v-if="chartSeries.length > 0 && chartSeries.some(v => v > 0)">
            <apexchart
              type="donut"
              height="300"
              :options="chartOptions"
              :series="chartSeries"
            ></apexchart>
          </div>
          <div v-else class="text-center text-grey py-8">
            No stock currently available in warehouses
          </div>
        </v-card>
      </v-col>

      <!-- Stock Breakdown by Fish Type -->
      <v-col cols="12" md="6">
        <v-card class="pa-5 rounded-xl border elevation-2 h-100">
          <div class="text-subtitle-1 font-weight-bold mb-4 d-flex align-center">
            <v-icon icon="mdi-fish" color="secondary" class="mr-2"></v-icon>
            Stock by Freezing Fish Type
          </div>

          <v-list density="compact" class="bg-transparent">
            <v-list-item v-for="fish in stats.stock_by_fish" :key="fish.id" class="px-0 py-2 border-b">
              <template #prepend>
                <v-avatar size="32" color="secondary" variant="tonal" class="mr-3">
                  <v-icon icon="mdi-fish" size="18"></v-icon>
                </v-avatar>
              </template>
              <v-list-item-title class="font-weight-bold">{{ fish.name }}</v-list-item-title>
              <template #append>
                <span class="text-subtitle-2 font-weight-bold color-primary">
                  {{ fish.quantity_kg.toLocaleString() }} kg
                </span>
              </template>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Vouchers Table Section -->
    <v-card class="pa-5 rounded-xl border elevation-2 mb-6" v-if="stats">
      <div class="d-flex align-center justify-space-between mb-4">
        <div class="text-subtitle-1 font-weight-bold d-flex align-center">
          <v-icon icon="mdi-history" color="primary" class="mr-2"></v-icon>
          {{ $t('dashboard.recent_vouchers') }}
        </div>
        <v-btn to="/warehouse-operations" size="small" variant="text" color="primary" append-icon="mdi-arrow-right">
          View All
        </v-btn>
      </div>

      <v-table density="comfortable" class="bg-transparent">
        <thead>
          <tr>
            <th>Number / Series</th>
            <th>Type</th>
            <th>Date</th>
            <th>Warehouse</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="v in stats.recent_vouchers" :key="v.id">
            <td class="font-weight-bold">{{ v.voucher_number }}</td>
            <td>
              <v-chip size="small" :color="getVoucherColor(v.type?.effect)" variant="tonal">
                {{ v.type?.name }}
              </v-chip>
            </td>
            <td>{{ v.voucher_date }}</td>
            <td>{{ v.fish_warehouse?.name || 'N/A' }}</td>
            <td>
              <v-chip size="x-small" color="success" variant="elevated">
                Confirmed
              </v-chip>
            </td>
          </tr>
          <tr v-if="!stats.recent_vouchers || stats.recent_vouchers.length === 0">
            <td colspan="5" class="text-center text-grey py-4">No recent vouchers registered</td>
          </tr>
        </tbody>
      </v-table>
    </v-card>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useDashboardStore } from '../stores/dashboard';

const dashboardStore = useDashboardStore();

const stats = computed(() => dashboardStore.stats);

const chartSeries = computed(() => {
  if (!stats.value || !stats.value.chart_data) return [];
  return stats.value.chart_data.series.map(v => Number(v));
});

const chartOptions = computed(() => {
  return {
    labels: stats.value?.chart_data?.labels || [],
    legend: {
      position: 'bottom',
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'],
    dataLabels: {
      enabled: true,
      formatter: function (val) {
        return val.toFixed(1) + '%';
      },
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val.toLocaleString() + ' kg';
        },
      },
    },
  };
});

function getVoucherColor(effect) {
  if (effect === 'stock_in') return 'success';
  if (effect === 'stock_out') return 'error';
  return 'info';
}

function loadData() {
  dashboardStore.fetchDashboardData();
}

onMounted(() => {
  loadData();
});
</script>
