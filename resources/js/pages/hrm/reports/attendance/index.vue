<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0"
      :items="[{ title: 'HRM' }, { title: 'Reports' }, { title: 'Employees Attendance' }]"
    />
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <AppSelect
              v-model="filters.department_id"
              label=""
              placeholder="Select Department"
              :items="departments"
              item-title="name"
              item-value="id"
              clearable
            />
          </VCol>
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
              clearable
            />
          </VCol>
          <VSpacer />
        </VRow>
        <VRow>
          <VCol cols="12" md="4">
            <AppTextField
              v-model="filters.q"
              label=""
              placeholder="Search by name, code, email"
              clearable
            />
          </VCol>
          <VCol cols="auto">
            <VBtn color="secondary" variant="outlined" @click="resetFilters">
              <VIcon start icon="tabler-refresh" />
              Reset
            </VBtn>
          </VCol>
          <VCol cols="auto">
            <VBtn color="success" :loading="isExporting" :disabled="isExporting || !tableRows.length">
              <VIcon start icon="tabler-file-export" />
              Export
            </VBtn>
            <VMenu activator="parent">
              <VList>
                <VListItem title="Export PDF" prepend-icon="tabler-file-type-pdf" @click="exportPDF" />
                <VListItem title="Export Excel" prepend-icon="tabler-file-spreadsheet" @click="exportExcel" />
              </VList>
            </VMenu>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VCardText>
        <VRow v-if="dateColumns.length && tableRows.length">
          <VCol cols="12" md="2">
            <VSheet class="summary-card summary-success">
              <div class="summary-header">
                <span>On Time</span>
                <VIcon icon="tabler-clock-check" color="success" size="20" />
              </div>
              <div class="summary-count text-success">{{ presentCount }}</div>
              <div class="summary-caption">{{ presentPercent }}% of total</div>
            </VSheet>
          </VCol>
          <VCol cols="12" md="2">
            <VSheet class="summary-card summary-warning">
              <div class="summary-header">
                <span>Late</span>
                <VIcon icon="tabler-clock-exclamation" color="warning" size="20" />
              </div>
              <div class="summary-count text-warning">{{ lateCount }}</div>
              <div class="summary-caption">{{ latePercent }}% of total</div>
            </VSheet>
          </VCol>
          <VCol cols="12" md="2">
            <VSheet class="summary-card summary-error">
              <div class="summary-header">
                <span>Absent</span>
                <VIcon icon="tabler-calendar-x" color="error" size="20" />
              </div>
              <div class="summary-count text-error">{{ absentCount }}</div>
              <div class="summary-caption">{{ absentPercent }}% of total</div>
            </VSheet>
          </VCol>
          <VCol cols="12" md="2">
            <VSheet class="summary-card summary-info">
              <div class="summary-header">
                <span>Short Leave</span>
                <VIcon icon="tabler-alarm" color="info" size="20" />
              </div>
              <div class="summary-count text-info">{{ shortLeavesCount }}</div>
              <div class="summary-caption">{{ shortLeavesPercent }}% of total</div>
            </VSheet>
          </VCol>
          <VCol cols="12" md="2">
            <VSheet class="summary-card summary-info">
              <div class="summary-header">
                <span>Half Leave</span>
                <VIcon icon="tabler-percentage-50" color="info" size="20" />
              </div>
              <div class="summary-count text-info">{{ halfLeavesCount }}</div>
              <div class="summary-caption">{{ halfLeavesPercent }}% of total</div>
            </VSheet>
          </VCol>
          <VCol cols="12" md="2">
            <VSheet class="summary-card summary-info">
              <div class="summary-header">
                <span>Leave</span>
                <VIcon icon="tabler-calendar" color="info" size="20" />
              </div>
              <div class="summary-count text-info">{{ leavesCount }}</div>
              <div class="summary-caption">{{ leavesPercent }}% of total</div>
            </VSheet>
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />
      <VDataTable
        :headers="tableHeaders"
        :items="tableRows"
        :loading="loading"
        :items-per-page="-1"
        loading-text="Loading data..."
        class="text-no-wrap compact-table"
        fixed-header
      >
        <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" :color="!item.profile_picture ? 'primary' : undefined"
                     :variant="!item.profile_picture ? 'tonal' : undefined">
              <VImg
                v-if="item.profile_picture"
                :src="item.profile_picture"
                cover
              />
              <span v-else>{{ item.name?.charAt(0)?.toUpperCase() }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.name }}
              </h6>
              <div class="text-sm">
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>
        <template v-for="date in dateColumns" :key="date" #[`item.${date}`]="{ item }">
          <VChip
            clickable
            :color="getStatusColor(item.attendance[date])"
            :size="$vuetify.display.mobile ? 'x-small' : 'small'"
            class="status-chip hover-row"
            @click="goToAttendance(item.id, date, item.attendance[date], item.employee_code)"
          >
            <span>{{ item.attendance[date] }}</span>
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
      </VDataTable>
    </VCard>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect';
import 'flatpickr/dist/plugins/monthSelect/style.css';
import { useToast } from 'vue-toast-notification';
import AppDateTimePicker from '@core/components/app-form-elements/AppDateTimePicker.vue';
import AppSelect from '@core/components/app-form-elements/AppSelect.vue';
import AppTextField from '@core/components/app-form-elements/AppTextField.vue';

const router = useRouter()
const $toast = useToast();
const accessToken = useCookie('accessToken');
const loading = ref(false);
const isExporting = ref(false);
const filters = ref({
  month: new Date().toISOString().slice(0, 7), // YYYY-MM
  department_id: null,
  q: '',
});
const minMonth = '2025-01-01';
const maxMonth = dayjs().endOf('month').toDate();
const reportData = ref({ dates: [], employees: [] });
const departments = ref([]);

const dateColumns = computed(() => reportData.value.dates);

const tableHeaders = computed(() => [
  { title: 'Employee Name', key: 'employee', align: 'left', width: 320, class: 'employee-col' },
  ...dateColumns.value.map(date => ({ title: dayjs(date).format('DD'), key: date, align: 'center', sortable: false })),
]);

const tableRows = computed(() => reportData.value.employees);

// Summary counts
const presentCount = computed(() =>
  tableRows.value.reduce((sum, emp) => sum + dateColumns.value.reduce((s, d) => s + (emp.attendance?.[d] === 'P' ? 1 : 0), 0), 0)
);
const absentCount = computed(() =>
  tableRows.value.reduce((sum, emp) => sum + dateColumns.value.reduce((s, d) => s + (emp.attendance?.[d] === 'A' ? 1 : 0), 0), 0)
);
const lateCount = computed(() =>
  tableRows.value.reduce((sum, emp) => sum + dateColumns.value.reduce((s, d) => s + (emp.attendance?.[d] === 'LT' ? 1 : 0), 0), 0)
);
const shortLeavesCount = computed(() =>
  tableRows.value.reduce((sum, emp) => sum + dateColumns.value.reduce((s, d) => s + (['SL'].includes(emp.attendance?.[d]) ? 1 : 0), 0), 0)
);
const halfLeavesCount = computed(() =>
  tableRows.value.reduce((sum, emp) => sum + dateColumns.value.reduce((s, d) => s + (['HL'].includes(emp.attendance?.[d]) ? 1 : 0), 0), 0)
);
const leavesCount = computed(() =>
  tableRows.value.reduce((sum, emp) => sum + dateColumns.value.reduce((s, d) => s + (['L'].includes(emp.attendance?.[d]) ? 1 : 0), 0), 0)
);
const totalCells = computed(() => (presentCount.value + absentCount.value + lateCount.value + shortLeavesCount.value + halfLeavesCount.value + leavesCount.value));
const percent = (n) => {
  const total = totalCells.value || 0;
  if (!total) return 0;
  return Number(((n / total) * 100).toFixed(1));
};
const presentPercent = computed(() => percent(presentCount.value));
const absentPercent = computed(() => percent(absentCount.value));
const latePercent = computed(() => percent(lateCount.value));
const shortLeavesPercent = computed(() => percent(shortLeavesCount.value));
const halfLeavesPercent = computed(() => percent(halfLeavesCount.value));
const leavesPercent = computed(() => percent(leavesCount.value));

let searchDebounce = null;

watch(filters, (newFilters, oldFilters) => {
  if (newFilters.q !== oldFilters.q) {
    if (searchDebounce) {
      clearTimeout(searchDebounce);
    }
    searchDebounce = setTimeout(() => {
      fetchReport();
    }, 500);
  } else {
    fetchReport();
  }
}, { deep: true });

const fetchReport = async () => {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/reports/employee-daily-attendance', {
      params: {
        ...filters.value,
      },
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    if (data.success) {
      reportData.value = data.data;
      if (!data.data.employees.length) $toast.info('No data found for the selected month');
    } else {
      throw new Error(data.message || 'Failed to fetch report');
    }
  } catch (error) {
    $toast.error(error.response?.data?.message || 'Failed to generate report');
    reportData.value = { dates: [], employees: [] };
  } finally {
    loading.value = false;
  }
};

const goToAttendance = (employeeId, date, status, code) => {
  router.push({
    path: '/hrm/attendance/list',
    query: {
      employee_id: employeeId,
      start_date: dayjs(date).format('YYYY-MM-DD'),
      end_date: dayjs(date).format('YYYY-MM-DD'),
      status: statusMap[status] ?? status,
      q: code
    },
  })
}

const statusMap = {
  LT: 'late',
  P: 'present',
  A: 'absent',
  H: 'holiday',
  HL: 'half-leave',
  SL: 'short-leave',
  L: 'leave',
  NM: 'not-marked',
  SA: 'shift-awaiting',
}

const resetFilters = () => {
  filters.value = {
    month: new Date().toISOString().slice(0, 7),
    department_id: null,
    q: '',
  };
};
const exportPDF = async () => {
  isExporting.value = true;
  try {
    const response = await axios.get('/api/reports/employee-daily-attendance/export-pdf', {
      params: {
        ...filters.value,
      },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `employee_daily_attendance_${filters.value.month}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    $toast.success('PDF exported successfully');
  } catch (error) {
    $toast.error('Failed to export PDF');
  } finally {
    isExporting.value = false;
  }
};
const exportExcel = async () => {
  isExporting.value = true;
  try {
    const response = await axios.get('/api/reports/employee-daily-attendance/export-excel', {
      params: {
        ...filters.value,
      },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `employee_daily_attendance_${filters.value.month}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    $toast.success('Excel exported successfully');
  } catch (error) {
    $toast.error('Failed to export Excel');
  } finally {
    isExporting.value = false;
  }
};
const getStatusColor = (status) => {
  switch (status) {
    case "P":
      return "success";
    case "A":
      return "error";
    case "LT":
      return "warning";
    case "HL":
      return "info";
    case "SL":
      return "info";
    case "L":
      return "info";
    case "NM":
      return "primary";
    case "H":
      return "secondary";
    default:
      return "secondary";
  }
};
const fetchDepartments = async () => {
  try {
    const { data } = await axios.get('/api/departments?context=filters', {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    departments.value = data.data;
  } catch (error) {
    $toast.error('Failed to fetch departments');
  }
};

onMounted(() => {
  fetchReport();
  fetchDepartments();
});
</script>

<style scoped>
:deep(.compact-table th),
:deep(.compact-table td) {
  padding-left: 4px !important;
  padding-right: 4px !important;
  min-width: 42px !important;
  max-width: 48px !important;
  background: #fff;
}

:deep(.compact-table th:first-child) {
  width: 320px !important;
  max-width: 340px !important;
  min-width: 300px !important;
  position: sticky !important;
  left: 0;
  z-index: 4 !important;
}
:deep(.compact-table td:first-child) {
  width: 320px !important;
  max-width: 340px !important;
  min-width: 300px !important;
  text-align: left !important;
  position: sticky !important;
  left: 0;
  z-index: 1 !important;
}

:deep(.compact-table th) {
  z-index: 3 !important;
}

:deep(.compact-table td:first-child .d-flex.flex-column) {
  max-width: 320px;
}
:deep(.compact-table td:first-child h6),
:deep(.compact-table td:first-child .text-sm) {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.summary-card {
  border-radius: 12px;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  position: relative;
  overflow: hidden;
}
.summary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 600;
}
.summary-count {
  font-size: 20px;
  line-height: 28px;
  font-weight: 700;
}
.summary-caption {
  font-size: 12px;
  opacity: 0.8;
}
.summary-success {
  background: linear-gradient(135deg, #e9f8ee 0%, #f6fffa 100%);
}
.summary-error {
  background: linear-gradient(135deg, #ffe9ee 0%, #fff6f8 100%);
}
.summary-warning {
  background: linear-gradient(135deg, #fff4e0 0%, #fffbf2 100%);
}
.summary-info {
  background: linear-gradient(135deg, #DFF3F7 0%, #DFF3F7 100%);
}
.text-success { color: #22c55e; }
.text-error { color: #ef4444; }
.text-warning { color: #f59e0b; }
.text-info { color: #7c3aed; }
</style>

<style>
  .flatpickr-calendar .flatpickr-monthSelect-month.selected {
    background-color: rgb(var(--v-theme-primary, 115, 103, 240)) !important;
    border-color: rgb(var(--v-theme-primary, 115, 103, 240)) !important;
  }
</style>
