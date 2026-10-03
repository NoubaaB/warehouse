<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">{{ $t('workforce.title') }}</h1>
        <div class="text-caption text-grey">{{ $t('workforce.subtitle') }}</div>
      </div>
      <div class="d-flex align-center ga-3 mt-4 mt-sm-0">
        <v-btn color="secondary" variant="tonal" prepend-icon="mdi-refresh" @click="loadMonthData" rounded="lg" :loading="workforceStore.loading">
          {{ $t('common.refresh') }}
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-cash-register" @click="openAssignDialog()" rounded="lg" class="elevation-1">
          {{ $t('workforce.assign_rate') }}
        </v-btn>
      </div>
    </div>

    <!-- Full Width Interactive VCalendar Section -->
    <v-card class="pa-4 mb-6 rounded-xl border elevation-2">
      <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-4">
        <div class="text-subtitle-1 font-weight-bold d-flex align-center">
          <v-icon icon="mdi-calendar-month" color="primary" class="mr-2"></v-icon>
          {{ $t('workforce.daily_calendar') }}
        </div>
        <v-chip color="primary" variant="tonal" class="font-weight-bold mt-2 mt-sm-0">
          {{ $t('workforce.grand_total') }}: {{ grandTotal.toLocaleString() }} MAD
        </v-chip>
      </div>

      <!-- ALWAYS VCalendar from VCalendar -->
      <v-calendar
        v-model="selectedDate"
        is-expanded
        expanded
        color="blue"
        :is-dark="isDark"
        :attributes="calendarAttributes"
        class="border rounded-xl custom-full-vcalendar w-100 pa-2"
        @update:page="onPageChange"
        @did-move="onPageChange"
        @dayclick="onDayClick"
      />
    </v-card>

    <!-- MONTHLY & FORTNIGHT SUMMARY DATA TABLE -->
    <v-card class="pa-4 rounded-xl border elevation-2">
      <!-- Fortnight Toggle Bar & Table Title -->
      <div class="d-flex flex-column flex-md-row align-md-center justify-space-between mb-4 ga-3">
        <div class="text-subtitle-1 font-weight-bold d-flex align-center">
          <v-icon icon="mdi-table" color="success" class="mr-2"></v-icon>
          {{ $t('workforce.monthly_table') }} ({{ currentMonthLabel }})
        </div>

        <!-- Fortnight Toggle Buttons -->
        <div class="d-flex align-center flex-wrap ga-2">
          <span class="text-caption font-weight-bold text-grey">{{ $t('workforce.fortnight_period') }}:</span>
          <v-btn-toggle
            v-model="selectedFortnight"
            mandatory
            color="primary"
            variant="outlined"
            rounded="lg"
            density="comfortable"
            @update:model-value="onFortnightChange"
          >
            <v-btn value="all" size="small" prepend-icon="mdi-calendar-month">
              {{ $t('workforce.all_month') }}
            </v-btn>
            <v-btn value="1st" size="small" prepend-icon="mdi-numeric-1-box-outline">
              {{ $t('workforce.first_fortnight') }}
            </v-btn>
            <v-btn value="2nd" size="small" prepend-icon="mdi-numeric-2-box-outline">
              {{ $t('workforce.second_fortnight') }}
            </v-btn>
          </v-btn-toggle>
        </div>

        <!-- Search input -->
        <v-text-field
          v-model="search"
          :placeholder="$t('common.search')"
          prepend-inner-icon="mdi-magnify"
          density="compact"
          variant="outlined"
          hide-details
          style="max-width: 250px;"
          clearable
        ></v-text-field>
      </div>

      <!-- Advanced Data Table -->
      <v-data-table-virtual
        :headers="headers"
        :items="filteredWorkers"
        :loading="workforceStore.loading"
        class="bg-transparent"
      >
        <template #header.worker_name="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="workforceStore.workers"
            v-model="filterWorker"
          />
        </template>

        <template #header.identifier="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="identifierOptions"
            v-model="filterIdentifier"
          />
        </template>

        <template #item.worker_name="{ item }">
          <span class="font-weight-bold color-primary">{{ item.worker_name }}</span>
        </template>

        <template #item.identifier="{ item }">
          <v-chip size="x-small" variant="tonal" color="info">
            {{ item.identifier || 'WF-N/A' }}
          </v-chip>
        </template>

        <template #item.worked_days="{ item }">
          <span class="font-weight-bold">{{ item.worked_days }} {{ $t('workforce.days') }}</span>
        </template>

        <template #item.total_amount="{ item }">
          <span class="font-weight-bold text-success">
            {{ item.total_amount.toLocaleString() }} MAD
          </span>
        </template>

        <!-- Summary Row at Bottom (Last Row showing Total Price) -->
        <template #bottom>
          <div class="d-flex justify-space-between align-center pa-4 bg-surface border-t font-weight-bold">
            <div class="d-flex align-center">
              <v-icon icon="mdi-calculator" color="primary" class="mr-2"></v-icon>
              <span class="text-subtitle-1">{{ $t('workforce.grand_total') }} ({{ getFortnightLabel(selectedFortnight) }})</span>
            </div>
            <span class="text-h6 text-success font-weight-black">{{ grandTotal.toLocaleString() }} MAD</span>
          </div>
        </template>
      </v-data-table-virtual>
    </v-card>

    <!-- ASSIGN DAILY PRICE DIALOG -->
    <v-dialog v-model="assignDialog" max-width="600" persistentScroll>
      <v-card class="rounded-xl pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div class="text-h6 font-weight-bold color-primary d-flex align-center">
            <v-icon icon="mdi-cash-register" class="mr-2"></v-icon>
            {{ $t('workforce.assign_rate') }}
          </div>
          <v-btn icon="mdi-close" variant="text" @click="assignDialog = false"></v-btn>
        </div>

        <v-form ref="assignForm" v-model="assignValid" @submit.prevent="submitAssignment">
          <!-- Select Multiple Workers -->
          <v-select
            v-model="assignData.workforce_ids"
            :items="workforceStore.workers"
            item-title="name"
            item-value="id"
            :label="$t('workforce.select_workers')"
            multiple
            chips
            variant="outlined"
            density="comfortable"
            :rules="[v => (v && v.length > 0) || $t('workforce.select_at_least_one_worker')]"
            required
            class="mb-3"
          ></v-select>

          <!-- Always VCalendar Date Picker -->
          <AppDatePicker
            v-model="assignData.single_date"
            :label="$t('workforce.select_date')"
            required
            class="mb-3"
          />

          <!-- Daily Rate / Price -->
          <v-text-field
            v-model.number="assignData.daily_rate"
            :label="$t('workforce.daily_rate')"
            type="number"
            step="0.01"
            variant="outlined"
            density="comfortable"
            :rules="[v => (v >= 0) || $t('workforce.must_be_positive')]"
            required
            class="mb-3"
          ></v-text-field>

          <v-textarea
            v-model="assignData.notes"
            :label="$t('common.notes')"
            variant="outlined"
            density="comfortable"
            rows="2"
            class="mb-4"
          ></v-textarea>

          <div class="d-flex justify-end ga-2">
            <v-btn variant="text" @click="assignDialog = false">{{ $t('common.cancel') }}</v-btn>
            <v-btn color="primary" type="submit" :loading="workforceStore.loading">
              {{ $t('common.save') }}
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTheme } from 'vuetify';
import { useI18n } from 'vue-i18n';
import { useWorkforceStore } from '../stores/workforce';
import AppDatePicker from '../components/AppDatePicker.vue';
import AppHeaderFilter from '../components/AppHeaderFilter.vue';

const workforceStore = useWorkforceStore();
const theme = useTheme();
const { t } = useI18n();

const isDark = computed(() => theme.global.current.value.dark);

const selectedMonth = ref(new Date().getMonth() + 1);
const selectedYear = ref(new Date().getFullYear());
const selectedDate = ref(new Date());
const selectedFortnight = ref('all');
const search = ref('');
const filterWorker = ref([]);
const filterIdentifier = ref([]);

function isSameDay(d1, d2) {
  if (!d1 || !d2) return false;
  const date1 = new Date(d1);
  const date2 = new Date(d2);
  return (
    date1.getFullYear() === date2.getFullYear() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getDate() === date2.getDate()
  );
}

const currentMonthLabel = computed(() => {
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  const name = monthNames[selectedMonth.value - 1] || '';
  return `${name} ${selectedYear.value}`;
});

function onPageChange(pages) {
  if (!pages) return;
  const p = Array.isArray(pages) ? pages[0] : pages;
  if (p && p.month && p.year) {
    if (selectedMonth.value !== p.month || selectedYear.value !== p.year) {
      selectedMonth.value = p.month;
      selectedYear.value = p.year;
      loadMonthData();
    }
  }
}

function onDayClick(day) {
  if (!day) return;
  const dateObj = day.date ? new Date(day.date) : (day instanceof Date ? day : new Date(day.id || day));
  if (isNaN(dateObj.getTime())) return;
  
  selectedDate.value = dateObj;

  const m = dateObj.getMonth() + 1;
  const y = dateObj.getFullYear();
  if (m !== selectedMonth.value || y !== selectedYear.value) {
    selectedMonth.value = m;
    selectedYear.value = y;
    loadMonthData();
  }

  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const dateStr = String(dateObj.getDate()).padStart(2, '0');
  const formattedDate = `${year}-${month}-${dateStr}`;

  assignData.value.single_date = formattedDate;
}

function onFortnightChange() {
  loadMonthData();
}

function getFortnightLabel(f) {
  if (f === '1st') return t('workforce.first_fortnight');
  if (f === '2nd') return t('workforce.second_fortnight');
  return t('workforce.all_month');
}

const identifierOptions = computed(() => {
  const ids = (monthlyWorkers.value || []).map(w => w.identifier).filter(Boolean);
  return Array.from(new Set(ids));
});

const headers = computed(() => [
  { title: t('workforce.worker_name'), key: 'worker_name', sortable: true },
  { title: t('workforce.identifier'), key: 'identifier', sortable: true },
  { title: t('workforce.worked_days'), key: 'worked_days', sortable: true },
  { title: t('workforce.total_amount'), key: 'total_amount', sortable: true },
]);

const monthlyWorkers = computed(() => workforceStore.monthlySummary.workers || []);
const grandTotal = computed(() => workforceStore.monthlySummary.grand_total || 0);

const filteredWorkers = computed(() => {
  return monthlyWorkers.value.filter(item => {
    if (filterWorker.value.length > 0 && !filterWorker.value.includes(item.workforce_id)) {
      return false;
    }
    if (filterIdentifier.value.length > 0 && !filterIdentifier.value.includes(item.identifier)) {
      return false;
    }
    if (search.value) {
      const q = search.value.toLowerCase();
      const name = item.worker_name ? item.worker_name.toLowerCase() : '';
      const id = item.identifier ? item.identifier.toLowerCase() : '';
      return name.includes(q) || id.includes(q);
    }
    return true;
  });
});

// VCalendar Attributes for calendar assignments
const calendarAttributes = computed(() => {
  const attrs = [];

  if (selectedDate.value) {
    attrs.push({
      key: 'selected-date-highlight',
      highlight: {
        color: 'blue',
        fillMode: 'light',
      },
      dates: selectedDate.value,
    });
  }

  const assignmentsByDate = {};
  workforceStore.assignments.forEach(a => {
    const dStr = a.date;
    if (!assignmentsByDate[dStr]) {
      assignmentsByDate[dStr] = [];
    }
    assignmentsByDate[dStr].push(a);
  });

  Object.entries(assignmentsByDate).forEach(([dStr, list]) => {
    const workerNames = list.map(a => `${a.workforce?.name || 'Worker'}: ${a.daily_rate} MAD`).join(' | ');
    attrs.push({
      key: `assignments-${dStr}`,
      dot: {
        color: 'blue',
      },
      popover: {
        label: workerNames,
      },
      dates: new Date(dStr),
    });
  });

  return attrs;
});

// Assign Dialog State
const assignDialog = ref(false);
const assignValid = ref(false);
const assignData = ref({
  workforce_ids: [],
  single_date: new Date().toISOString().slice(0, 10),
  daily_rate: 150,
  notes: '',
});

function openAssignDialog(targetDate = null) {
  const dateObj = targetDate 
    ? new Date(targetDate)
    : (selectedDate.value ? new Date(selectedDate.value) : new Date());

  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const dateStr = String(dateObj.getDate()).padStart(2, '0');
  const formattedDate = `${year}-${month}-${dateStr}`;

  assignData.value = {
    workforce_ids: workforceStore.workers.map(w => w.id),
    single_date: formattedDate,
    daily_rate: 150,
    notes: '',
  };
  assignDialog.value = true;
}

async function submitAssignment() {
  if (!assignValid.value) return;
  await workforceStore.saveAssignments({
    workforce_ids: assignData.value.workforce_ids,
    dates: [assignData.value.single_date],
    daily_rate: assignData.value.daily_rate,
    notes: assignData.value.notes,
  });
  assignDialog.value = false;
  loadMonthData();
}

function loadMonthData() {
  workforceStore.fetchWorkers();
  workforceStore.fetchAssignments({
    month: selectedMonth.value,
    year: selectedYear.value,
  });
  workforceStore.fetchMonthlySummary(selectedYear.value, selectedMonth.value, selectedFortnight.value);
}

onMounted(() => {
  loadMonthData();
});
</script>

<style scoped>
.custom-full-vcalendar {
  width: 100% !important;
  max-width: 100% !important;
  display: block !important;
}
.custom-full-vcalendar :deep(.vc-container) {
  width: 100% !important;
  max-width: 100% !important;
}
.custom-full-vcalendar :deep(.vc-pane-container),
.custom-full-vcalendar :deep(.vc-pane-layout),
.custom-full-vcalendar :deep(.vc-pane),
.custom-full-vcalendar :deep(.vc-header),
.custom-full-vcalendar :deep(.vc-weeks) {
  width: 100% !important;
  max-width: 100% !important;
}
</style>
