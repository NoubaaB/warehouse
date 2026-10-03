<template>
  <div class="d-inline-flex align-center header-filter-wrapper">
    <span class="font-weight-bold text-subtitle-2 mr-1">{{ title }}</span>
    <v-menu
      v-model="menu"
      :close-on-content-click="false"
      location="bottom end"
      transition="slide-y-transition"
    >
      <template #activator="{ props }">
        <v-btn
          v-bind="props"
          size="x-small"
          variant="text"
          :color="activeFilterCount > 0 ? 'primary' : 'grey-darken-1'"
          class="ml-1 filter-trigger-btn"
        >
          <v-badge
            v-if="activeFilterCount > 0"
            :content="activeFilterCount"
            color="primary"
            floating
          >
            <v-icon size="small">mdi-filter</v-icon>
          </v-badge>
          <v-icon v-else size="small">mdi-filter-outline</v-icon>
        </v-btn>
      </template>

      <v-card width="320" class="pa-3 rounded-xl border shadow-xl bg-surface filter-popover-card">
        <div class="d-flex align-center justify-space-between mb-2">
          <div class="d-flex align-center">
            <v-icon icon="mdi-filter-cog" size="small" color="primary" class="mr-1"></v-icon>
            <span class="text-caption font-weight-bold text-uppercase color-primary">{{ title }}</span>
          </div>
          <v-btn
            size="x-small"
            variant="text"
            color="grey"
            icon="mdi-close"
            @click="menu = false"
          ></v-btn>
        </div>

        <!-- Autocomplete Multi-Select Search -->
        <v-autocomplete
          v-model="selectedValues"
          :items="normalizedOptions"
          item-title="title"
          item-value="value"
          multiple
          chips
          closable-chips
          density="compact"
          variant="outlined"
          :placeholder="$t('common.search')"
          hide-details
          clearable
          class="mb-2"
        >
          <template #chip="{ props, item }">
            <v-chip v-bind="props" size="x-small" color="primary" variant="tonal">
              {{ item.title }}
            </v-chip>
          </template>
        </v-autocomplete>

        <!-- Quick Checkbox List Header -->
        <div class="d-flex align-center justify-space-between text-caption px-1 mb-1">
          <span class="text-grey font-weight-bold">{{ $t('common.options') }} ({{ normalizedOptions.length }})</span>
          <div>
            <v-btn size="x-small" variant="text" color="primary" class="pa-0 mr-2" @click="selectAll">
              {{ $t('common.select_all') }}
            </v-btn>
            <v-btn size="x-small" variant="text" color="error" class="pa-0" @click="clearFilter">
              {{ $t('common.clear_all') }}
            </v-btn>
          </div>
        </div>

        <!-- Quick Checkbox List Items -->
        <div style="max-height: 180px; overflow-y: auto;" class="border-t border-b py-1 custom-scroll">
          <v-checkbox
            v-for="opt in normalizedOptions"
            :key="String(opt.value)"
            :model-value="selectedValues.includes(opt.value)"
            :label="opt.title"
            density="compact"
            hide-details
            color="primary"
            @update:model-value="toggleValue(opt.value)"
          ></v-checkbox>

          <div v-if="normalizedOptions.length === 0" class="text-caption text-grey text-center py-3">
            {{ $t('common.no_options') }}
          </div>
        </div>

        <!-- Bottom Actions -->
        <div class="d-flex justify-space-between align-center pt-2 mt-1">
          <v-btn size="x-small" variant="text" color="grey" @click="clearFilter">
            {{ $t('common.clear_all') }}
          </v-btn>
          <v-btn size="x-small" variant="elevated" color="primary" @click="applyFilter">
            {{ $t('common.apply') }}
          </v-btn>
        </div>
      </v-card>
    </v-menu>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  options: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
  itemText: {
    type: String,
    default: 'name',
  },
  itemValue: {
    type: String,
    default: 'id',
  },
});

const emit = defineEmits(['update:modelValue', 'filter-change']);

const menu = ref(false);
const selectedValues = ref([...props.modelValue]);

watch(() => props.modelValue, (newVal) => {
  selectedValues.value = [...(newVal || [])];
}, { deep: true });

const normalizedOptions = computed(() => {
  if (!props.options) return [];
  return props.options.map((item) => {
    if (typeof item === 'object' && item !== null) {
      const value = item[props.itemValue] ?? item.id ?? item.code ?? item.name ?? String(item);
      const title = item[props.itemText] ?? item.name ?? item.title ?? item.code ?? item.voucher_number ?? String(value);
      return { title: String(title), value, raw: item };
    }
    return { title: String(item), value: item, raw: item };
  });
});

const activeFilterCount = computed(() => selectedValues.value.length);

const toggleValue = (val) => {
  const idx = selectedValues.value.indexOf(val);
  if (idx > -1) {
    selectedValues.value.splice(idx, 1);
  } else {
    selectedValues.value.push(val);
  }
};

const selectAll = () => {
  selectedValues.value = normalizedOptions.value.map(opt => opt.value);
};

const applyFilter = () => {
  emit('update:modelValue', [...selectedValues.value]);
  emit('filter-change', [...selectedValues.value]);
  menu.value = false;
};

const clearFilter = () => {
  selectedValues.value = [];
  emit('update:modelValue', []);
  emit('filter-change', []);
  menu.value = false;
};
</script>

<style scoped>
.header-filter-wrapper {
  display: inline-flex;
  align-items: center;
  width: 100%;
}
.filter-trigger-btn {
  opacity: 0.85;
  transition: opacity 0.2s ease-in-out;
}
.filter-trigger-btn:hover {
  opacity: 1;
}
.custom-scroll::-webkit-scrollbar {
  width: 4px;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 4px;
}
</style>
