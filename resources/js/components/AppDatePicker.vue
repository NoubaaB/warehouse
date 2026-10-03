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
        :model-value="formattedDate"
        :label="label"
        :rules="rules"
        :required="required"
        :disabled="disabled"
        :density="density"
        :variant="variant"
        prepend-inner-icon="mdi-calendar"
        readonly
        hide-details="auto"
        class="custom-input-field"
      ></v-text-field>
    </template>
    
    <v-card class="pa-2 border shadow-lg rounded-xl">
      <!-- ALWAYS VDatePicker from VCalendar -->
      <v-date-picker
        v-model="selectedDate"
        @update:model-value="onDateSelected"
        :attributes="attributes"
        is-required
        mode="date"
      />
      <div class="d-flex justify-end pt-2">
        <v-btn size="small" variant="text" color="primary" @click="menu = false">
          OK
        </v-btn>
      </div>
    </v-card>
  </v-menu>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: 'Select Date',
  },
  rules: {
    type: Array,
    default: () => [],
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  density: {
    type: String,
    default: 'comfortable',
  },
  variant: {
    type: String,
    default: 'outlined',
  },
});

const emit = defineEmits(['update:modelValue']);

const menu = ref(false);

// Format initial date for VDatePicker
function parseDate(dateStr) {
  if (!dateStr) return new Date();
  const d = new Date(dateStr);
  return isNaN(d.getTime()) ? new Date() : d;
}

const selectedDate = ref(parseDate(props.modelValue));

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      selectedDate.value = parseDate(newVal);
    }
  }
);

const formattedDate = computed(() => {
  if (!props.modelValue && !selectedDate.value) return '';
  const d = selectedDate.value instanceof Date ? selectedDate.value : parseDate(props.modelValue);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
});

const attributes = ref([
  {
    highlight: true,
    dates: new Date(),
  },
]);

function onDateSelected(val) {
  if (val) {
    const d = new Date(val);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const dateStr = `${year}-${month}-${day}`;
    emit('update:modelValue', dateStr);
    menu.value = false;
  }
}
</script>

<style scoped>
.custom-input-field {
  border-radius: 8px;
}
</style>
