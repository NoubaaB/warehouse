<template>
  <div>
    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4 font-weight-bold">{{ $t('vouchers.title') }}</h1>
        <div class="text-caption text-grey">Create & manage stock-affecting reception, sales, and consumable vouchers</div>
      </div>
      <div class="d-flex flex-wrap align-center ga-3 mt-4 mt-sm-0">
        <v-btn color="success" prepend-icon="mdi-plus-box" @click="openReceptionModal" rounded="lg" class="elevation-1">
          {{ $t('vouchers.new_reception') }}
        </v-btn>
        <v-btn color="error" prepend-icon="mdi-minus-box" @click="openStockOutModal" rounded="lg" class="elevation-1">
          {{ $t('vouchers.new_stock_out') }}
        </v-btn>
        <v-btn color="info" prepend-icon="mdi-package-down" @click="openConsumableModal" rounded="lg" class="elevation-1">
          {{ $t('vouchers.new_consumable_receipt') }}
        </v-btn>
      </div>
    </div>

    <!-- INLINE EXPANDED VCALENDAR DATE RANGE SECTION -->
    <v-card class="pa-5 mb-6 rounded-xl border elevation-2 bg-surface">
      <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-4 gap-2">
        <div class="d-flex align-center">
          <v-icon icon="mdi-calendar-range" color="primary" class="mr-2" size="large"></v-icon>
          <div>
            <div class="text-subtitle-1 font-weight-bold color-primary">VCalendar Date Range Selection</div>
            <div class="text-caption text-grey">Select start & end date on calendar directly to filter all vouchers</div>
          </div>
        </div>

        <div class="d-flex align-center gap-2 flex-wrap">
          <v-chip
            v-if="dateRange.start && dateRange.end"
            color="primary"
            variant="elevated"
            class="font-weight-bold"
          >
            <v-icon icon="mdi-calendar-check" class="mr-1"></v-icon>
            {{ dateRange.start }} → {{ dateRange.end }}
          </v-chip>

          <v-chip color="info" variant="tonal" class="font-weight-bold">
            <v-icon icon="mdi-file-document-multiple" class="mr-1"></v-icon>
            {{ $t('common.bons_in_range') }}: {{ filteredVouchers.length }} / {{ vouchersStore.vouchers.length }}
          </v-chip>

          <!-- Stylish Preset Month Buttons -->
          <v-btn-group rounded="pill" density="comfortable" class="border shadow-xs bg-surface pa-1 ga-1">
            <v-btn
              size="small"
              :variant="activeRangePreset === 'current' ? 'elevated' : 'text'"
              :color="activeRangePreset === 'current' ? 'primary' : 'grey-darken-1'"
              prepend-icon="mdi-calendar-month"
              rounded="pill"
              class="text-none font-weight-bold px-4"
              @click="selectCurrentMonth"
            >
              {{ $t('common.this_month') }}
            </v-btn>

            <v-btn
              size="small"
              :variant="activeRangePreset === 'last' ? 'elevated' : 'text'"
              :color="activeRangePreset === 'last' ? 'indigo' : 'grey-darken-1'"
              prepend-icon="mdi-calendar-clock"
              rounded="pill"
              class="text-none font-weight-bold px-4"
              @click="selectLastMonth"
            >
              {{ $t('common.last_month') }}
            </v-btn>
          </v-btn-group>

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
      </div>

      <!-- DIRECTLY EMBEDDED EXPANDED VCALENDAR RANGE PICKER -->
      <v-date-picker
        v-model="vCalendarRange"
        is-range
        expanded
        color="blue"
        :is-dark="isDark"
        class="border rounded-xl shadow-sm custom-inline-vcalendar pa-2"
      />
    </v-card>

    <!-- Vouchers Data Table Card -->
    <v-card class="pa-4 rounded-xl border elevation-2">
      <!-- Search Bar -->
      <div class="mb-4">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          :placeholder="$t('common.search')"
          density="comfortable"
          variant="outlined"
          hide-details
          clearable
        ></v-text-field>
      </div>

      <!-- Data Table -->
      <v-data-table-virtual
        :headers="headers"
        :items="filteredVouchers"
        :loading="vouchersStore.loading"
        class="bg-transparent"
      >
        <!-- Header Filter Slots -->
        <template #header.voucher_number="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="allVoucherNumbers"
            v-model="filterVoucherNumber"
          />
        </template>

        <template #header.voucher_type="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="settingsStore.voucherTypes"
            v-model="filterVoucherType"
          />
        </template>

        <template #header.fish_warehouse="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="settingsStore.fishWarehouses"
            v-model="filterWarehouse"
          />
        </template>

        <template #header.providers="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="settingsStore.providers"
            v-model="filterProviders"
          />
        </template>

        <template #header.clients="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="settingsStore.clients"
            v-model="filterClients"
          />
        </template>

        <template #header.article_details="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="settingsStore.freezingFish"
            v-model="filterArticles"
          />
        </template>

        <template #header.status="{ column }">
          <AppHeaderFilter
            :title="column.title"
            :options="statusOptions"
            v-model="filterStatus"
          />
        </template>

        <template #item.voucher_type="{ item }">
          <v-chip size="small" :color="getVoucherColor(item.type?.effect)" variant="tonal">
            {{ item.type?.name }}
          </v-chip>
        </template>

        <template #item.fish_warehouse="{ item }">
          {{ item.fish_warehouse?.name || 'N/A' }}
        </template>

        <template #item.providers="{ item }">
          <span v-if="item.providers && item.providers.length > 0">
            {{ item.providers.map(p => p.name).join(', ') }}
          </span>
          <span v-else class="text-grey">-</span>
        </template>

        <template #item.clients="{ item }">
          <span v-if="item.clients && item.clients.length > 0">
            {{ item.clients.map(c => c.name).join(', ') }}
          </span>
          <span v-else class="text-grey">-</span>
        </template>

        <template #item.article_details="{ item }">
          <div v-if="getVoucherArticles(item).length > 0" class="d-flex flex-wrap ga-1 py-1">
            <v-chip
              v-for="detail in getVoucherArticles(item)"
              :key="detail.id"
              size="x-small"
              color="primary"
              variant="tonal"
              class="font-weight-medium"
            >
              <v-icon icon="mdi-fish" size="x-small" class="mr-1"></v-icon>
              {{ getFishName(detail) }} ({{ detail.quantity }} kg<span v-if="detail.calculated_boxes"> / {{ detail.calculated_boxes }} bxs</span><span v-if="detail.unit_price"> • {{ Number(detail.unit_price).toLocaleString() }} MAD</span>)
            </v-chip>
          </div>
          <div v-else-if="getVoucherConsumables(item).length > 0" class="d-flex flex-wrap ga-1 py-1">
            <v-chip
              v-for="c in getVoucherConsumables(item)"
              :key="c.id"
              size="x-small"
              color="info"
              variant="tonal"
              class="font-weight-medium"
            >
              <v-icon icon="mdi-package-down" size="x-small" class="mr-1"></v-icon>
              {{ getConsumableName(c) }} ({{ c.quantity }} {{ c.unit }})
            </v-chip>
          </div>
          <span v-else class="text-grey text-caption">-</span>
        </template>

        <template #item.total_amount="{ item }">
          <span class="font-weight-bold text-success">
            {{ calculateVoucherTotal(item).toLocaleString() }} MAD
          </span>
        </template>

        <template #item.status="{ item }">
          <v-chip size="x-small" color="success" variant="elevated">
            {{ item.status }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn icon="mdi-eye" size="small" variant="tonal" color="primary" @click="viewVoucher(item)"></v-btn>
        </template>

        <!-- Summary Row at Bottom (Last Row showing Totals) -->
        <template #bottom>
          <div class="d-flex justify-space-between align-center pa-4 bg-surface border-t font-weight-bold">
            <div class="d-flex align-center">
              <v-icon icon="mdi-file-document-multiple" color="primary" class="mr-2"></v-icon>
              <span>{{ $t('vouchers.total_vouchers') }}: {{ filteredVouchers.length }}</span>
            </div>
            <div class="text-subtitle-1 color-primary font-weight-black">
              {{ $t('vouchers.total_price') }}: {{ grandTotalVouchersAmount.toLocaleString() }} MAD
            </div>
          </div>
        </template>
      </v-data-table-virtual>
    </v-card>

    <!-- 1. RECEPTION VOUCHER DIALOG -->
    <v-dialog v-model="receptionDialog" max-width="900" persistentScroll>
      <v-card class="rounded-xl pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div class="text-h6 font-weight-bold text-success d-flex align-center">
            <v-icon icon="mdi-plus-box" class="mr-2"></v-icon>
            {{ $t('vouchers.new_reception') }}
          </div>
          <v-btn icon="mdi-close" variant="text" @click="receptionDialog = false"></v-btn>
        </div>

        <v-form ref="receptionForm" v-model="receptionValid" @submit.prevent="submitReception">
          <v-row>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="receptionData.voucher_number"
                :label="$t('vouchers.voucher_number')"
                variant="outlined"
                density="comfortable"
                :rules="[v => !!v || 'Series / number required']"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="4">
              <!-- Always VCalendar Date Picker -->
              <AppDatePicker
                v-model="receptionData.voucher_date"
                :label="$t('vouchers.voucher_date')"
                required
              />
            </v-col>

            <v-col cols="12" sm="4">
              <v-select
                v-model="receptionData.fish_warehouse_id"
                :items="settingsStore.fishWarehouses"
                item-title="name"
                item-value="id"
                :label="$t('vouchers.warehouse')"
                variant="outlined"
                density="comfortable"
                :rules="[v => !!v || 'Warehouse required']"
                required
              ></v-select>
            </v-col>

            <v-col cols="12" sm="6">
              <v-select
                v-model="receptionData.provider_ids"
                :items="settingsStore.providers"
                item-title="name"
                item-value="id"
                :label="$t('vouchers.providers')"
                multiple
                chips
                variant="outlined"
                density="comfortable"
              ></v-select>
            </v-col>

            <v-col cols="12" sm="6">
              <v-text-field
                v-model="receptionData.truck_licence"
                :label="$t('vouchers.truck_licence')"
                variant="outlined"
                density="comfortable"
              ></v-text-field>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <!-- Article Details Section -->
          <div class="d-flex align-center justify-space-between mb-3">
            <span class="text-subtitle-1 font-weight-bold">{{ $t('vouchers.articles') }}</span>
            <v-btn size="small" color="primary" variant="tonal" prepend-icon="mdi-plus" @click="addReceptionArticle">
              {{ $t('vouchers.add_article') }}
            </v-btn>
          </div>

          <v-card
            v-for="(art, idx) in receptionData.articles"
            :key="idx"
            variant="outlined"
            class="pa-4 mb-4 rounded-lg bg-surface"
          >
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption font-weight-bold color-primary">Article #{{ idx + 1 }}</span>
              <v-btn
                v-if="receptionData.articles.length > 1"
                icon="mdi-delete"
                size="x-small"
                color="error"
                variant="text"
                @click="removeReceptionArticle(idx)"
              ></v-btn>
            </div>

            <v-row>
              <v-col cols="12" sm="4">
                <v-select
                  v-model="art.freezing_fish_id"
                  :items="settingsStore.freezingFish"
                  item-title="name"
                  item-value="id"
                  :label="$t('vouchers.fish_type')"
                  variant="outlined"
                  density="comfortable"
                  :rules="[v => !!v || 'Fish type required']"
                  required
                ></v-select>
              </v-col>

              <v-col cols="12" sm="4">
                <v-text-field
                  v-model.number="art.quantity"
                  :label="$t('vouchers.quantity_kg')"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  density="comfortable"
                  @input="updateBoxes(art)"
                  :rules="[v => (v > 0) || 'Must be > 0']"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12" sm="4">
                <v-select
                  v-model="art.container_id"
                  :items="settingsStore.containers"
                  item-title="name"
                  item-value="id"
                  :label="$t('vouchers.container')"
                  variant="outlined"
                  density="comfortable"
                  clearable
                  @update:model-value="updateBoxes(art)"
                ></v-select>
              </v-col>

              <v-col cols="12">
                <div class="text-caption text-grey">
                  Calculated Boxes: <strong>{{ art.calculated_boxes || 0 }}</strong>
                </div>
              </v-col>
            </v-row>

            <!-- Consumables consumed per article -->
            <div class="mt-3 pl-3 border-l">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-caption font-weight-bold text-uppercase">{{ $t('vouchers.consumables') }}</span>
                <v-btn size="x-small" variant="text" color="primary" prepend-icon="mdi-plus" @click="addConsumableToArticle(art)">
                  {{ $t('vouchers.add_consumable') }}
                </v-btn>
              </div>

              <div v-for="(c, cIdx) in art.consumables" :key="cIdx" class="d-flex align-center gap-2 mb-2">
                <v-select
                  v-model="c.consumable_type_id"
                  :items="settingsStore.consumableTypes"
                  item-title="name"
                  item-value="id"
                  label="Consumable"
                  density="compact"
                  variant="outlined"
                  hide-details
                  class="flex-grow-1"
                ></v-select>

                <v-text-field
                  v-model.number="c.quantity"
                  label="Qty"
                  type="number"
                  step="0.01"
                  density="compact"
                  variant="outlined"
                  hide-details
                  style="width: 100px;"
                ></v-text-field>

                <v-btn icon="mdi-close" size="x-small" color="error" variant="text" @click="art.consumables.splice(cIdx, 1)"></v-btn>
              </div>
            </div>
          </v-card>

          <div class="d-flex justify-end gap-2 mt-4">
            <v-btn variant="text" @click="receptionDialog = false">{{ $t('common.cancel') }}</v-btn>
            <v-btn color="success" type="submit" :loading="vouchersStore.loading">
              {{ $t('vouchers.confirm_save') }}
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- 2. STOCK-OUT / SALES VOUCHER DIALOG -->
    <v-dialog v-model="stockOutDialog" max-width="900" persistentScroll>
      <v-card class="rounded-xl pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div class="text-h6 font-weight-bold text-error d-flex align-center">
            <v-icon icon="mdi-minus-box" class="mr-2"></v-icon>
            {{ $t('vouchers.new_stock_out') }}
          </div>
          <v-btn icon="mdi-close" variant="text" @click="stockOutDialog = false"></v-btn>
        </div>

        <v-form ref="stockOutForm" v-model="stockOutValid" @submit.prevent="submitStockOut">
          <v-row>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="stockOutData.voucher_number"
                :label="$t('vouchers.voucher_number')"
                variant="outlined"
                density="comfortable"
                :rules="[v => !!v || 'Series required']"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" sm="4">
              <AppDatePicker
                v-model="stockOutData.voucher_date"
                :label="$t('vouchers.voucher_date')"
                @update:model-value="loadAvailableStockLots"
                required
              />
            </v-col>

            <v-col cols="12" sm="4">
              <v-select
                v-model="stockOutData.fish_warehouse_id"
                :items="settingsStore.fishWarehouses"
                item-title="name"
                item-value="id"
                :label="$t('vouchers.warehouse')"
                variant="outlined"
                density="comfortable"
                @update:model-value="loadAvailableStockLots"
                :rules="[v => !!v || 'Warehouse required']"
                required
              ></v-select>
            </v-col>

            <v-col cols="12">
              <v-select
                v-model="stockOutData.client_ids"
                :items="settingsStore.clients"
                item-title="name"
                item-value="id"
                :label="$t('vouchers.clients')"
                multiple
                chips
                variant="outlined"
                density="comfortable"
              ></v-select>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <!-- Sales Lines -->
          <div class="d-flex align-center justify-space-between mb-3">
            <span class="text-subtitle-1 font-weight-bold">Sales Lines</span>
            <v-btn size="small" color="error" variant="tonal" prepend-icon="mdi-plus" @click="addSalesLine">
              Add Sales Line
            </v-btn>
          </div>

          <v-card
            v-for="(art, idx) in stockOutData.articles"
            :key="idx"
            variant="outlined"
            class="pa-4 mb-4 rounded-lg bg-surface"
          >
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption font-weight-bold text-error">Line #{{ idx + 1 }}</span>
              <v-btn
                v-if="stockOutData.articles.length > 1"
                icon="mdi-delete"
                size="x-small"
                color="error"
                variant="text"
                @click="stockOutData.articles.splice(idx, 1)"
              ></v-btn>
            </div>

            <v-row>
              <v-col cols="12" sm="6">
                <!-- Select Lot with date <= voucher_date rule! -->
                <v-select
                  v-model="art.fish_stock_id"
                  :items="availableStockLots"
                  :item-title="getLotTitle"
                  item-value="id"
                  :label="$t('vouchers.stock_lot')"
                  variant="outlined"
                  density="comfortable"
                  @update:model-value="onStockLotSelect(art)"
                  :rules="[v => !!v || 'Stock lot required']"
                  required
                ></v-select>
              </v-col>

              <v-col cols="12" sm="3">
                <v-text-field
                  v-model.number="art.quantity"
                  :label="$t('vouchers.quantity_kg')"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  density="comfortable"
                  :rules="[v => (v > 0) || 'Must be > 0']"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12" sm="3">
                <v-text-field
                  v-model.number="art.unit_price"
                  :label="$t('vouchers.unit_price')"
                  type="number"
                  step="0.01"
                  variant="outlined"
                  density="comfortable"
                  :rules="[v => (v >= 0) || 'Must be >= 0']"
                  required
                ></v-text-field>
              </v-col>

              <v-col cols="12" class="d-flex justify-space-between text-caption font-weight-bold text-error">
                <span>Available: {{ art.max_qty || 0 }} kg</span>
                <span>Total Line: {{ (art.quantity * art.unit_price || 0).toFixed(2) }} MAD</span>
              </v-col>
            </v-row>
          </v-card>

          <div class="d-flex justify-end gap-2 mt-4">
            <v-btn variant="text" @click="stockOutDialog = false">{{ $t('common.cancel') }}</v-btn>
            <v-btn color="error" type="submit" :loading="vouchersStore.loading">
              {{ $t('vouchers.confirm_save') }}
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- 3. CONSUMABLE RECEIPT DIALOG -->
    <v-dialog v-model="consumableDialog" max-width="700" persistentScroll>
      <v-card class="rounded-xl pa-6">
        <div class="d-flex align-center justify-space-between mb-4">
          <div class="text-h6 font-weight-bold text-info d-flex align-center">
            <v-icon icon="mdi-package-down" class="mr-2"></v-icon>
            {{ $t('vouchers.new_consumable_receipt') }}
          </div>
          <v-btn icon="mdi-close" variant="text" @click="consumableDialog = false"></v-btn>
        </div>

        <v-form ref="consumableForm" v-model="consumableValid" @submit.prevent="submitConsumableReceipt">
          <v-row>
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="consumableData.voucher_number"
                :label="$t('vouchers.voucher_number')"
                variant="outlined"
                density="comfortable"
                :rules="[v => !!v || 'Series required']"
                required
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="6">
              <AppDatePicker
                v-model="consumableData.voucher_date"
                :label="$t('vouchers.voucher_date')"
                required
              />
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <div class="d-flex align-center justify-space-between mb-3">
            <span class="text-subtitle-1 font-weight-bold">Consumable Details</span>
            <v-btn size="small" color="info" variant="tonal" prepend-icon="mdi-plus" @click="addConsumableRow">
              Add Row
            </v-btn>
          </div>

          <div v-for="(c, idx) in consumableData.consumables" :key="idx" class="d-flex align-center gap-2 mb-3">
            <v-select
              v-model="c.consumable_type_id"
              :items="settingsStore.consumableTypes"
              item-title="name"
              item-value="id"
              label="Consumable Type"
              variant="outlined"
              density="comfortable"
              hide-details
              class="flex-grow-1"
            ></v-select>

            <v-text-field
              v-model.number="c.quantity"
              label="Qty Received"
              type="number"
              step="0.01"
              variant="outlined"
              density="comfortable"
              hide-details
              style="width: 140px;"
            ></v-text-field>

            <v-btn
              v-if="consumableData.consumables.length > 1"
              icon="mdi-delete"
              size="small"
              color="error"
              variant="text"
              @click="consumableData.consumables.splice(idx, 1)"
            ></v-btn>
          </div>

          <div class="d-flex justify-end gap-2 mt-4">
            <v-btn variant="text" @click="consumableDialog = false">{{ $t('common.cancel') }}</v-btn>
            <v-btn color="info" type="submit" :loading="vouchersStore.loading">
              {{ $t('vouchers.confirm_save') }}
            </v-btn>
          </div>
        </v-form>
      </v-card>
    </v-dialog>

    <!-- VIEW VOUCHER DETAILS DIALOG -->
    <v-dialog v-model="viewDialog" max-width="900" persistentScroll>
      <v-card class="rounded-xl pa-6 bg-surface" v-if="selectedVoucher">
        <!-- Dialog Header -->
        <div class="d-flex align-center justify-space-between mb-4 border-b pb-3">
          <div class="d-flex align-center">
            <v-icon icon="mdi-file-document-outline" color="primary" class="mr-2" size="large"></v-icon>
            <div>
              <div class="text-h6 font-weight-black color-primary">
                {{ selectedVoucher.voucher_number }}
              </div>
              <div class="text-caption text-grey">
                {{ selectedVoucher.voucher_date }} • {{ selectedVoucher.type?.name }}
              </div>
            </div>
          </div>
          <div class="d-flex align-center ga-2">
            <v-chip size="small" color="success" variant="elevated">
              {{ selectedVoucher.status }}
            </v-chip>
            <v-btn icon="mdi-close" variant="text" @click="viewDialog = false"></v-btn>
          </div>
        </div>

        <!-- Voucher Summary Info Grid -->
        <v-row class="mb-4">
          <v-col cols="12" sm="4">
            <div class="text-caption text-grey font-weight-bold">{{ $t('vouchers.voucher_type') }}</div>
            <v-chip size="small" :color="getVoucherColor(selectedVoucher.type?.effect)" variant="tonal" class="mt-1">
              {{ selectedVoucher.type?.name }}
            </v-chip>
          </v-col>
          <v-col cols="12" sm="4">
            <div class="text-caption text-grey font-weight-bold">{{ $t('vouchers.warehouse') }}</div>
            <div class="text-body-2 font-weight-medium mt-1">
              {{ selectedVoucher.fish_warehouse?.name || 'N/A' }}
            </div>
          </v-col>
          <v-col cols="12" sm="4">
            <div class="text-caption text-grey font-weight-bold">{{ $t('vouchers.truck_licence') }}</div>
            <div class="text-body-2 font-weight-medium mt-1">
              {{ selectedVoucher.truck_licence || 'N/A' }}
            </div>
          </v-col>
          <v-col cols="12" sm="6">
            <div class="text-caption text-grey font-weight-bold">{{ $t('vouchers.providers') }}</div>
            <div class="text-body-2 font-weight-medium mt-1">
              <span v-if="selectedVoucher.providers && selectedVoucher.providers.length > 0">
                {{ selectedVoucher.providers.map(p => p.name).join(', ') }}
              </span>
              <span v-else class="text-grey">-</span>
            </div>
          </v-col>
          <v-col cols="12" sm="6">
            <div class="text-caption text-grey font-weight-bold">{{ $t('vouchers.clients') }}</div>
            <div class="text-body-2 font-weight-medium mt-1">
              <span v-if="selectedVoucher.clients && selectedVoucher.clients.length > 0">
                {{ selectedVoucher.clients.map(c => c.name).join(', ') }}
              </span>
              <span v-else class="text-grey">-</span>
            </div>
          </v-col>
          <v-col cols="12" v-if="selectedVoucher.notes">
            <div class="text-caption text-grey font-weight-bold">{{ $t('vouchers.notes') }}</div>
            <div class="text-body-2 font-weight-medium mt-1 pa-2 bg-primary-subtle rounded">
              {{ selectedVoucher.notes }}
            </div>
          </v-col>
        </v-row>

        <!-- Fish Articles Details Table -->
        <!-- Fish Articles Details Table -->
        <div v-if="getVoucherArticles(selectedVoucher).length > 0" class="mb-4">
          <div class="text-subtitle-2 font-weight-bold mb-2 color-primary d-flex align-center">
            <v-icon icon="mdi-fish" class="mr-1"></v-icon>
            {{ $t('vouchers.articles') }}
          </div>
          <v-table density="compact" class="border rounded-lg">
            <thead>
              <tr class="bg-primary-subtle">
                <th class="text-left">{{ $t('vouchers.fish_type') }}</th>
                <th class="text-center">{{ $t('vouchers.quantity_kg') }}</th>
                <th class="text-center">{{ $t('vouchers.container') }}</th>
                <th class="text-center">{{ $t('vouchers.calculated_boxes') }}</th>
                <th class="text-right">{{ $t('vouchers.unit_price') }}</th>
                <th class="text-right">{{ $t('vouchers.total_price') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="art in getVoucherArticles(selectedVoucher)" :key="art.id">
                <td>
                  <span class="font-weight-bold">{{ getFishName(art) }}</span>
                </td>
                <td class="text-center font-weight-medium">{{ art.quantity }} kg</td>
                <td class="text-center">{{ art.container?.name || '-' }}</td>
                <td class="text-center">{{ art.calculated_boxes || 0 }}</td>
                <td class="text-right">{{ art.unit_price ? Number(art.unit_price).toLocaleString() + ' MAD' : '-' }}</td>
                <td class="text-right font-weight-bold text-success">
                  {{ Number(art.total_price || (art.quantity * (art.unit_price || 0))).toLocaleString() }} MAD
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>

        <!-- Consumables Receipt Details Table -->
        <div v-if="getVoucherConsumables(selectedVoucher).length > 0" class="mb-4">
          <div class="text-subtitle-2 font-weight-bold mb-2 color-primary d-flex align-center">
            <v-icon icon="mdi-package-down" class="mr-1"></v-icon>
            {{ $t('vouchers.consumables') }}
          </div>
          <v-table density="compact" class="border rounded-lg">
            <thead>
              <tr class="bg-primary-subtle">
                <th class="text-left">{{ $t('consumable_warehouse.consumable') }}</th>
                <th class="text-center">{{ $t('vouchers.quantity_kg') }}</th>
                <th class="text-center">{{ $t('consumable_warehouse.unit') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in getVoucherConsumables(selectedVoucher)" :key="c.id">
                <td class="font-weight-bold">{{ getConsumableName(c) }}</td>
                <td class="text-center font-weight-medium">{{ c.quantity }}</td>
                <td class="text-center">{{ c.unit }}</td>
              </tr>
            </tbody>
          </v-table>
        </div>

        <!-- Dialog Footer with Total Amount -->
        <div class="d-flex align-center justify-space-between pt-3 border-t">
          <div>
            <span class="text-caption text-grey">{{ $t('vouchers.status') }}:</span>
            <v-chip size="x-small" color="success" class="ml-2">{{ selectedVoucher.status }}</v-chip>
          </div>
          <div class="text-h6 font-weight-black text-success">
            {{ $t('vouchers.total_price') }}: {{ calculateVoucherTotal(selectedVoucher).toLocaleString() }} MAD
          </div>
        </div>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTheme } from 'vuetify';
import { useI18n } from 'vue-i18n';
import { useVouchersStore } from '../stores/vouchers';
import { useSettingsStore } from '../stores/settings';
import { useStockStore } from '../stores/stock';
import AppDatePicker from '../components/AppDatePicker.vue';
import AppHeaderFilter from '../components/AppHeaderFilter.vue';
import AppDateRangePicker from '../components/AppDateRangePicker.vue';

const vouchersStore = useVouchersStore();
const settingsStore = useSettingsStore();
const stockStore = useStockStore();
const theme = useTheme();
const { t } = useI18n();

const isDark = computed(() => theme.global.current.value.dark);

const search = ref('');

const viewDialog = ref(false);
const selectedVoucher = ref(null);

function getVoucherArticles(voucher) {
  if (!voucher) return [];
  return voucher.article_details || voucher.articleDetails || [];
}

function getVoucherConsumables(voucher) {
  if (!voucher) return [];
  return voucher.consumable_receipt_details || voucher.consumableReceiptDetails || [];
}

function getFishName(detail) {
  if (!detail) return 'Fish';
  return detail.freezing_fish?.name || detail.freezingFish?.name || 'Fish';
}

function getConsumableName(c) {
  if (!c) return 'Consumable';
  return c.consumable_type?.name || c.consumableType?.name || 'Consumable';
}

function calculateVoucherTotal(voucher) {
  if (!voucher) return 0;
  const articles = getVoucherArticles(voucher);
  if (articles.length > 0) {
    return articles.reduce((sum, d) => {
      const lineTotal = d.total_price
        ? Number(d.total_price)
        : (Number(d.quantity || 0) * Number(d.unit_price || 0));
      return sum + lineTotal;
    }, 0);
  }
  return 0;
}

function getInitialMonthRange() {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  return {
    start: new Date(year, month, 1),
    end: new Date(year, month + 1, 0),
  };
}

function formatDateStr(d) {
  if (!d || !(d instanceof Date) || isNaN(d.getTime())) return '';
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const vCalendarRange = ref(getInitialMonthRange());

const dateRange = computed(() => {
  const start = formatDateStr(vCalendarRange.value?.start);
  const end = formatDateStr(vCalendarRange.value?.end);
  return { start, end };
});

const activeRangePreset = computed(() => {
  const currentMonthRange = getInitialMonthRange();
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth() - 1;
  const lastMonthRange = {
    start: new Date(year, month, 1),
    end: new Date(year, month + 1, 0),
  };

  const startStr = dateRange.value.start;
  const endStr = dateRange.value.end;

  if (startStr === formatDateStr(currentMonthRange.start) && endStr === formatDateStr(currentMonthRange.end)) {
    return 'current';
  }
  if (startStr === formatDateStr(lastMonthRange.start) && endStr === formatDateStr(lastMonthRange.end)) {
    return 'last';
  }
  return 'custom';
});

function selectCurrentMonth() {
  vCalendarRange.value = getInitialMonthRange();
}

function selectLastMonth() {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth() - 1;
  vCalendarRange.value = {
    start: new Date(year, month, 1),
    end: new Date(year, month + 1, 0),
  };
}

const filterVoucherNumber = ref([]);
const filterVoucherType = ref([]);
const filterWarehouse = ref([]);
const filterProviders = ref([]);
const filterClients = ref([]);
const filterArticles = ref([]);
const filterStatus = ref([]);

const statusOptions = ['validated', 'draft', 'completed'];

const allVoucherNumbers = computed(() => {
  return (vouchersStore.vouchers || []).map(v => v.voucher_number).filter(Boolean);
});

const headers = computed(() => [
  { title: t('vouchers.voucher_number'), key: 'voucher_number' },
  { title: t('vouchers.voucher_type'), key: 'voucher_type' },
  { title: t('vouchers.voucher_date'), key: 'voucher_date' },
  { title: t('vouchers.warehouse'), key: 'fish_warehouse' },
  { title: t('vouchers.providers'), key: 'providers' },
  { title: t('vouchers.clients'), key: 'clients' },
  { title: t('vouchers.articles'), key: 'article_details' },
  { title: t('vouchers.total_price'), key: 'total_amount' },
  { title: t('vouchers.status'), key: 'status' },
  { title: t('vouchers.actions'), key: 'actions', sortable: false },
]);

const grandTotalVouchersAmount = computed(() => {
  return filteredVouchers.value.reduce((sum, v) => sum + calculateVoucherTotal(v), 0);
});

const hasActiveFilters = computed(() => {
  return (
    filterVoucherNumber.value.length > 0 ||
    filterVoucherType.value.length > 0 ||
    filterWarehouse.value.length > 0 ||
    filterProviders.value.length > 0 ||
    filterClients.value.length > 0 ||
    filterArticles.value.length > 0 ||
    filterStatus.value.length > 0 ||
    search.value !== ''
  );
});

function resetAllFilters() {
  filterVoucherNumber.value = [];
  filterVoucherType.value = [];
  filterWarehouse.value = [];
  filterProviders.value = [];
  filterClients.value = [];
  filterArticles.value = [];
  filterStatus.value = [];
  vCalendarRange.value = getInitialMonthRange();
  search.value = '';
}

const filteredVouchers = computed(() => {
  return vouchersStore.vouchers.filter(v => {
    if (dateRange.value.start && dateRange.value.end) {
      if (v.voucher_date < dateRange.value.start || v.voucher_date > dateRange.value.end) {
        return false;
      }
    }
    if (filterVoucherNumber.value.length > 0 && !filterVoucherNumber.value.includes(v.voucher_number)) {
      return false;
    }
    if (filterVoucherType.value.length > 0 && !filterVoucherType.value.includes(v.voucher_type_id)) {
      return false;
    }
    if (filterWarehouse.value.length > 0 && !filterWarehouse.value.includes(v.fish_warehouse_id)) {
      return false;
    }
    if (filterProviders.value.length > 0) {
      const vProvIds = (v.providers || []).map(p => p.id);
      if (!filterProviders.value.some(id => vProvIds.includes(id))) return false;
    }
    if (filterClients.value.length > 0) {
      const vClientIds = (v.clients || []).map(c => c.id);
      if (!filterClients.value.some(id => vClientIds.includes(id))) return false;
    }
    if (filterArticles.value.length > 0) {
      const vArticles = getVoucherArticles(v);
      const fishIds = vArticles.map(a => a.freezing_fish_id || a.freezing_fish?.id || a.freezingFish?.id).filter(Boolean);
      if (!filterArticles.value.some(id => fishIds.includes(id))) return false;
    }
    if (filterStatus.value.length > 0 && !filterStatus.value.includes(v.status)) {
      return false;
    }
    if (search.value) {
      const q = search.value.toLowerCase();
      return (
        v.voucher_number.toLowerCase().includes(q) ||
        (v.truck_licence && v.truck_licence.toLowerCase().includes(q))
      );
    }
    return true;
  });
});

function getVoucherColor(effect) {
  if (effect === 'stock_in') return 'success';
  if (effect === 'stock_out') return 'error';
  return 'info';
}

// 1. RECEPTION DIALOG STATE
const receptionDialog = ref(false);
const receptionValid = ref(false);
const receptionData = ref({
  voucher_number: '',
  voucher_date: new Date().toISOString().slice(0, 10),
  fish_warehouse_id: null,
  provider_ids: [],
  truck_licence: '',
  articles: [],
});

function openReceptionModal() {
  const typeId = settingsStore.voucherTypes.find(t => t.code === 'reception')?.id;
  receptionData.value = {
    voucher_type_id: typeId,
    voucher_number: 'REC-' + Date.now().toString().slice(-6),
    voucher_date: new Date().toISOString().slice(0, 10),
    fish_warehouse_id: settingsStore.fishWarehouses[0]?.id || null,
    provider_ids: [],
    truck_licence: '',
    articles: [
      {
        freezing_fish_id: null,
        quantity: 1500,
        container_id: settingsStore.containers[0]?.id || null,
        calculated_boxes: 62.5,
        consumables: [],
      }
    ],
  };
  receptionDialog.value = true;
}

function addReceptionArticle() {
  receptionData.value.articles.push({
    freezing_fish_id: null,
    quantity: 0,
    container_id: null,
    calculated_boxes: 0,
    consumables: [],
  });
}

function removeReceptionArticle(idx) {
  receptionData.value.articles.splice(idx, 1);
}

function addConsumableToArticle(art) {
  art.consumables.push({
    consumable_type_id: null,
    quantity: 1,
    unit: 'pcs',
  });
}

function updateBoxes(art) {
  if (art.container_id && art.quantity > 0) {
    const cnt = settingsStore.containers.find(c => c.id === art.container_id);
    if (cnt && cnt.capacity > 0) {
      art.calculated_boxes = Math.round((art.quantity / cnt.capacity) * 100) / 100;
      return;
    }
  }
  art.calculated_boxes = 0;
}

async function submitReception() {
  if (!receptionValid.value) return;
  const typeId = settingsStore.voucherTypes.find(t => t.code === 'reception')?.id;
  await vouchersStore.createVoucher({
    ...receptionData.value,
    voucher_type_id: typeId,
  });
  receptionDialog.value = false;
}

// 2. STOCK-OUT / SALES DIALOG STATE
const stockOutDialog = ref(false);
const stockOutValid = ref(false);
const availableStockLots = ref([]);
const stockOutData = ref({
  voucher_number: '',
  voucher_date: new Date().toISOString().slice(0, 10),
  fish_warehouse_id: null,
  client_ids: [],
  articles: [],
});

function openStockOutModal() {
  const typeId = settingsStore.voucherTypes.find(t => t.code === 'stock_out')?.id;
  stockOutData.value = {
    voucher_type_id: typeId,
    voucher_number: 'SO-' + Date.now().toString().slice(-6),
    voucher_date: new Date().toISOString().slice(0, 10),
    fish_warehouse_id: settingsStore.fishWarehouses[0]?.id || null,
    client_ids: [],
    articles: [
      {
        fish_stock_id: null,
        freezing_fish_id: null,
        quantity: 0,
        unit_price: 0,
        max_qty: 0,
      }
    ],
  };
  stockOutDialog.value = true;
  loadAvailableStockLots();
}

async function loadAvailableStockLots() {
  if (!stockOutData.value.fish_warehouse_id) return;
  await stockStore.fetchActiveFishStocks({
    fish_warehouse_id: stockOutData.value.fish_warehouse_id,
    date_lte: stockOutData.value.voucher_date,
  });
  availableStockLots.value = stockStore.activeFishStocks;
}

function getLotTitle(item) {
  return `${item.freezing_fish?.name || 'Fish'} | Rec: ${item.reception_date} | Rem: ${item.remaining_quantity} kg`;
}

function onStockLotSelect(art) {
  const lot = availableStockLots.value.find(l => l.id === art.fish_stock_id);
  if (lot) {
    art.freezing_fish_id = lot.freezing_fish_id;
    art.container_id = lot.container_id;
    art.max_qty = lot.remaining_quantity;
    art.quantity = Math.min(art.quantity || 100, lot.remaining_quantity);
  }
}

function addSalesLine() {
  stockOutData.value.articles.push({
    fish_stock_id: null,
    freezing_fish_id: null,
    quantity: 0,
    unit_price: 0,
    max_qty: 0,
  });
}

async function submitStockOut() {
  if (!stockOutValid.value) return;
  const typeId = settingsStore.voucherTypes.find(t => t.code === 'stock_out')?.id;
  await vouchersStore.createVoucher({
    ...stockOutData.value,
    voucher_type_id: typeId,
  });
  stockOutDialog.value = false;
}

// 3. CONSUMABLE RECEIPT DIALOG STATE
const consumableDialog = ref(false);
const consumableValid = ref(false);
const consumableData = ref({
  voucher_number: '',
  voucher_date: new Date().toISOString().slice(0, 10),
  consumables: [],
});

function openConsumableModal() {
  const typeId = settingsStore.voucherTypes.find(t => t.code === 'consumable_receipt')?.id;
  consumableData.value = {
    voucher_type_id: typeId,
    voucher_number: 'CR-' + Date.now().toString().slice(-6),
    voucher_date: new Date().toISOString().slice(0, 10),
    consumables: [
      { consumable_type_id: settingsStore.consumableTypes[0]?.id || null, quantity: 100, unit: 'pcs' }
    ],
  };
  consumableDialog.value = true;
}

function addConsumableRow() {
  consumableData.value.consumables.push({
    consumable_type_id: null,
    quantity: 0,
    unit: 'pcs',
  });
}

async function submitConsumableReceipt() {
  if (!consumableValid.value) return;
  const typeId = settingsStore.voucherTypes.find(t => t.code === 'consumable_receipt')?.id;
  await vouchersStore.createVoucher({
    ...consumableData.value,
    voucher_type_id: typeId,
  });
  consumableDialog.value = false;
}

onMounted(() => {
  vouchersStore.fetchVouchers();
  settingsStore.fetchAllSettings();
});
</script>

<style scoped>
.custom-inline-vcalendar {
  width: 100% !important;
  max-width: 100% !important;
}

.custom-inline-vcalendar :deep(.vc-container) {
  width: 100% !important;
  border: none !important;
  background-color: transparent !important;
}

.custom-inline-vcalendar :deep(.vc-pane-layout) {
  width: 100% !important;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
}

.custom-inline-vcalendar :deep(.vc-pane) {
  width: 100% !important;
}

/* Sky Blue Range Selection Highlights */
.custom-inline-vcalendar :deep(.vc-highlight-bg-light) {
  background-color: #bfdbfe !important;
}

.custom-inline-vcalendar :deep(.vc-highlight-bg-dark) {
  background-color: #1e3a8a !important;
}

.custom-inline-vcalendar :deep(.vc-highlight-bg-solid) {
  background-color: #2563eb !important;
}
</style>
