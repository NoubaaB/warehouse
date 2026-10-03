<template>
  <v-menu
    v-model="menu"
    :close-on-content-click="false"
    transition="scale-transition"
    min-width="auto"
  >
    <template #activator="{ props }">
      <v-text-field
        v-bind="props"
        :model-value="displayRangeText"
        :label="label"
        density="comfortable"
        variant="outlined"
        prepend-inner-icon="mdi-calendar-range"
        readonly
        hide-details="auto"
        class="custom-date-range-field"
        :clearable="!!formattedRange.start && !!formattedRange.end"
        @click:clear="clearRange"
      >
        <template #append-inner>
          <v-chip
            v-if="formattedRange.start && formattedRange.end"
            size="x-small"
            color="primary"
            variant="tonal"
            class="mr-1"
          >
            Range Active
          </v-chip>
        </template>
      </v-text-field>
    </template>

    <v-card class="pa-3 border shadow-xl rounded-xl bg-surface" max-width="360">
      <div class="d-flex align-center justify-space-between mb-2">
        <span class="text-caption font-weight-bold text-uppercase color-primary">
          {{ label }}
        </span>
        <v-btn size="x-small" icon="mdi-close" variant="text" color="grey" @click="menu = false"></v-btn>
      </div>

      <!-- ALWAYS VDatePicker from VCalendar with Range Mode -->
      <v-date-picker
        v-model="rangeSelection"
        is-range
        color="blue"
        class="v-calendar-range-picker border rounded-lg"
      />

      <div class="d-flex justify-space-between align-center pt-3">
        <v-btn size="small" variant="text" color="error" @click="clearRange">
          {{ $t('common.clear_all') }}
        </v-btn>
        <v-btn size="small" variant="elevated" color="primary" @click="applyRange">
          {{ $t('common.apply') }}
        </v-btn>
      </div>
    </v-card>
  </v-menu>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ start: null, end: null }),
  },
  label: {
    type: String,
    default: 'Filter Date Range',
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const menu = ref(false);

function toDate(val) {
  if (!val) return null;
  const d = new Date(val);
  return isNaN(d.getTime()) ? null : d;
}

function formatDateStr(d) {
  if (!d || !(d instanceof Date) || isNaN(d.getTime())) return '';
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const rangeSelection = ref({
  start: toDate(props.modelValue?.start),
  end: toDate(props.modelValue?.end),
});

watch(
  () => props.modelValue,
  (newVal) => {
    rangeSelection.value = {
      start: toDate(newVal?.start),
      end: toDate(newVal?.end),
    };
  },
  { deep: true }
);

const formattedRange = computed(() => {
  const start = formatDateStr(rangeSelection.value?.start);
  const end = formatDateStr(rangeSelection.value?.end);
  return { start, end };
});

const displayRangeText = computed(() => {
  const { start, end } = formattedRange.value;
  if (start && end) {
    return `${start}  →  ${end}`;
  }
  if (start) return `${start} → ...`;
  return '';
});

function applyRange() {
  const { start, end } = formattedRange.value;
  const rangeObj = start && end ? { start, end } : { start: null, end: null };
  emit('update:modelValue', rangeObj);
  emit('change', rangeObj);
  menu.value = false;
}

function clearRange() {
  rangeSelection.value = { start: null, end: null };
  const rangeObj = { start: null, end: null };
  emit('update:modelValue', rangeObj);
  emit('change', rangeObj);
  menu.value = false;
}
</script>

<style scoped>
.custom-date-range-field {
  border-radius: 8px;
}
.v-calendar-range-picker {
  width: 100%;
}
</style>
