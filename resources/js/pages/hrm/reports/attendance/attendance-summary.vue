<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[{ title: 'HRM' }, { title: 'Reports' }, { title: 'Attendance Summary' }]"
    />

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="2">
            <AppDateTimePicker
              v-model="filters.month"
              label=""
              :config="{
                minDate: minMonth,
                maxDate: maxMonth,
                plugins: [
                  new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: 'Y-m',
                    altFormat: 'F Y',
                    altInput: true,
                  }),
                ],
              }"
              @update:model-value="onMonthChange"
              clearable
            />
          </VCol>
          <VCol cols="auto">
            <VBtn
              color="secondary"
              variant="outlined"
              class="flex-grow-1 flex-md-grow-0"
              @click="resetFilters"
            >
              <VIcon start icon="tabler-refresh" />
              <span class="d-none d-md-inline">Reset</span>
              <span class="d-md-none">Reset</span>
            </VBtn>
          </VCol>
          <VCol cols="auto">
            <VBtn
              color="success"
              :loading="isExporting"
              :disabled="isExporting || !reportData.length"
            >
              <VIcon start icon="tabler-file-export" />
              Export
            </VBtn>
            <VMenu activator="parent">
              <VList>
                <VListItem
                  title="Export PDF"
                  prepend-icon="tabler-file-type-pdf"
                  @click="exportPDF"
                />
                <VListItem
                  title="Export Excel"
                  prepend-icon="tabler-file-spreadsheet"
                  @click="exportExcel"
                />
              </VList>
            </VMenu>
          </VCol>
          <VSpacer />
        </VRow>
      </VCardText>

      <VDivider />
      <!-- Data Table -->
      <VDataTable
        :headers="headers"
        :items="reportData"
        :loading="loading"
        :items-per-page="-1"
        loading-text="Loading data..."
        class="text-no-wrap compact-table"
      >
        <template #item.date="{ item }">
          {{ formatDate(item.date) }}
        </template>

        <template #item.on_time_percentage="{ item }">
          <VChip
            :color="getPercentageColor(item.on_time_percentage)"
            size="small"
          >
            {{ item.on_time_percentage ? parseFloat(item.on_time_percentage).toFixed(1) + '%' : '0.0%' }}
          </VChip>
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <div class="text-h6 mb-2">No Data Available</div>
          </div>
        </template>
        <!-- pagination -->
        <template #bottom>
          <div class="d-flex justify-end pt-2">
            <!-- Pagination removed -->
          </div>
        </template>

        <!-- Custom header slots -->
        <template #header.total_employees>
          <span v-html="headers.find(h => h.key === 'total_employees').title" />
        </template>
        <template #header.present_or_late_count>
          <span v-html="headers.find(h => h.key === 'present_or_late_count').title" />
        </template>
        <template #header.half_leaves>
          <span v-html="headers.find(h => h.key === 'half_leaves').title" />
        </template>
        <template #header.short_leaves>
          <span v-html="headers.find(h => h.key === 'short_leaves').title" />
        </template>
        <template #header.on_time_percentage>
          <span v-html="headers.find(h => h.key === 'on_time_percentage').title" />
        </template>
      </VDataTable>
    </VCard>
  </section>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect';
import 'flatpickr/dist/plugins/monthSelect/style.css';
import { useToast } from 'vue-toast-notification';

const $toast = useToast();
const accessToken = useCookie('accessToken');
const loading = ref(false);
const isExporting = ref(false);
const reportData = ref([]);
const orderBy = ref('asc');
const minMonth = '2025-01-01';
const maxMonth = dayjs().endOf('month').toDate();

const filters = ref({
  month: new Date().toISOString().slice(0, 7),
});

const headers = [
  { title: 'Date', key: 'date', align: 'center' },
  { title: 'Total<br>Employees', key: 'total_employees', align: 'center' },
  { title: 'Total<br>Present', key: 'present_or_late_count', align: 'center' },
  { title: 'On Time', key: 'present_count', align: 'center' },
  { title: 'Late', key: 'late_count', align: 'center' },
  { title: 'Short<br>Leaves', key: 'short_leaves', align: 'center' },
  { title: 'Half<br>Leaves', key: 'half_leaves', align: 'center' },
  { title: 'Leaves', key: 'full_leaves', align: 'center' },
  { title: 'Absent', key: 'absent_count', align: 'center' },
  { title: 'On-Time<br>%', key: 'on_time_percentage', align: 'center' },
];

// Ensure page resets to 1 on sort/filter changes (Leaves behavior)
watch([orderBy], () => {
})

// Helper to build query params
const buildParams = () => {
  return {
    month: filters.value.month,
  }
}

// Methods
const onMonthChange = (value) => {
  filters.value.month = value;
  fetchReport();
};

const fetchReport = async () => {
  loading.value = true;
  try {
    const params = buildParams()

    const { data } = await axios.get('/api/reports/attendance-summary', {
      params,
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });

    if (data.success) {
      reportData.value = data?.data || [];
      // totalItems.value = data?.meta?.total || 0; // Pagination removed
      if (!reportData.value.length) {
        $toast.info('No data found for the selected criteria');
      }
    } else {
      throw new Error(data.message || 'Failed to fetch report');
    }
  } catch (error) {
    console.error('Error fetching report:', error);
    $toast.error(error.response?.data?.message || 'Failed to generate report');
    reportData.value = [];
  } finally {
    loading.value = false;
  }
};

const exportPDF = async () => {
  isExporting.value = true;
  try {
    const params = {
      month: filters.value.month,
    }

    const response = await axios.get('/api/reports/attendance-summary/export-pdf', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `attendance_summary_${filters.value.month}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $toast.success('PDF exported successfully');
  } catch (error) {
    console.error('Error exporting PDF:', error);
    $toast.error('Failed to export PDF');
  } finally {
    isExporting.value = false;
  }
};

const exportExcel = async () => {
  isExporting.value = true;
  try {
    const params = {
      month: filters.value.month,
    }

    const response = await axios.get('/api/reports/attendance-summary/export-excel', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `attendance_summary_${filters.value.month}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $toast.success('Excel exported successfully');
  } catch (error) {
    console.error('Error exporting Excel:', error);
    $toast.error('Failed to export Excel');
  } finally {
    isExporting.value = false;
  }
};

const resetFilters = () => {
  filters.value = {
    month: new Date().toISOString().slice(0, 7),
  };
  reportData.value = [];
};

const formatDate = dateString => {
  const date = new Date(dateString)
  const weekday = date.toLocaleDateString('en-US', { weekday: 'short' })
  const day = String(date.getDate()).padStart(2, '0')
  const month = date.toLocaleDateString('en-US', { month: 'short' })
  const year = date.getFullYear()
  return `${weekday}, ${day}-${month}-${year}`
}

const getPercentageColor = (percentage) => {
  if (!percentage) return 'grey';
  if (percentage >= 90) return 'success';
  if (percentage >= 75) return 'info';
  if (percentage >= 60) return 'warning';
  return 'error';
};

// Lifecycle
onMounted(() => {
  fetchReport();
});
</script>

<style scoped>
.text-h4 {
  font-weight: 600;
}
.filters-row {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}
.filter-item {
  flex: 1;
  min-width: 200px;
}
.actions {
  margin-left: auto;
}
.compact-table >>> th,
.compact-table >>> td {
  padding-left: 6px !important;
  padding-right: 6px !important;
  min-width: 60px !important;
  max-width: 110px !important;
  font-size: 13px !important;
}
.compact-table >>> th:first-child,
.compact-table >>> td:first-child {
  min-width: 90px !important;
  max-width: 120px !important;
}
.compact-table >>> th:last-child,
.compact-table >>> td:last-child {
  min-width: 80px !important;
  max-width: 100px !important;
}

.compact-table >>> tbody tr:nth-child(even) {
  background-color: rgba(213, 93, 54, 0.02); /* light orange */
}
</style>
