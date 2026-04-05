<template>
  <section>
    <VBreadcrumbs class="px-0 pb-2 pt-0" :items="[{ title: 'HRM' }, { title: 'Reports' }, { title: 'Monthly Attendance Summary' } ]" />
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
              @update:model-value="onMonthSelect"
            />
          </VCol>
          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="filters.department_id"
              :items="departments"
              label=""
              item-title="name"
              item-value="id"
              placeholder="Select department"
              clearable
              no-data-text="No department found"
            />
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="4">
            <AppTextField v-model="filters.searchQuery" placeholder="Search" />
          </VCol>
          <VCol cols="auto">
            <VBtn color="secondary" variant="outlined" @click="resetFilters">
              <VIcon start icon="tabler-refresh" /> Reset
            </VBtn>
          </VCol>
          <VCol cols="auto">
            <VBtn color="success" :loading="isExporting" :disabled="isExporting || !tableRows?.length">
              <VIcon start icon="tabler-file-export" /> Export
            </VBtn>
            <VMenu activator="parent">
              <VList>
                <VListItem title="Export PDF" prepend-icon="tabler-file-type-pdf" @click="exportPDF" />
                <VListItem title="Export Excel" prepend-icon="tabler-file-spreadsheet" @click="exportExcel" />
              </VList>
            </VMenu>
          </VCol>
          <VSpacer />
        </VRow>
      </VCardText>
      <VDivider />
      <VDataTable
        :headers="tableHeaders"
        :items="tableRows"
        :loading="loading"
        :items-per-page="-1"
        loading-text="Loading data..."
        class="text-no-wrap compact-table custom-table"
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
                <VTooltip
                  activator="parent"
                  location="top"
                >
                  {{ item.name }}
                </VTooltip>
                {{ truncate(item.name) }}</h6>
              <div class="text-sm">
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <div class="text-h6 mb-2">No Data Available</div>
          </div>
        </template>
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
import {ref, onMounted, watch, computed} from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { useToast } from 'vue-toast-notification';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect';
import 'flatpickr/dist/plugins/monthSelect/style.css';

const $toast = useToast();
const accessToken = useCookie('accessToken');
const loading = ref(false);
const isExporting = ref(false);
const report = ref({ dates: [], employees: [] });
const departments = ref([]);

// Compute current month
const today = dayjs();
const defaultStart = today.startOf('month').format('YYYY-MM-DD');
const defaultEnd = today.endOf('month').format('YYYY-MM-DD');

// Main table headers — use the same employee key as weekly/employee-monthly for the avatar cell slot
const tableHeaders = computed(() => [
  { title: 'Sr.#', key: 'sr', align: 'center', sortable: false },
  { title: 'Employee Name', key: 'employee', align: 'left' },
  { title: 'Employee Code', key: 'employee_code', align: 'center' },
  { title: 'Department', key: 'department', align: 'center' },
  { title: 'Designation', key: 'designation', align: 'center' },
  { title: 'Total\nWorking Days', key: 'total_working_days', align: 'center' },
  { title: 'On Time', key: 'present', align: 'center' },
  { title: 'WFH', key: 'wfh', align: 'center' },
  { title: 'Short Leave', key: 'short_leave', align: 'center' },
  { title: 'Half Leave', key: 'half_day', align: 'center' },
  { title: 'Late\nArrivals', key: 'late_arrivals', align: 'center' },
  { title: 'Total\nPresent', key: 'total_present', align: 'center' },
  { title: 'Leave', key: 'leave', align: 'center' },
  { title: 'Absent', key: 'absent', align: 'center' },
  { title: 'Didn\'t\nMark', key: 'not_marked', align: 'center' },
  { title: 'Allocated\nHours', key: 'allocated_hours', align: 'center' },
  { title: 'Worked\nHours', key: 'worked_hours', align: 'center' },
]);

const filters = ref({
  dateRange: defaultStart + ' to ' + defaultEnd,
  start_date: defaultStart,
  end_date: defaultEnd,
  searchQuery: '',
  department_id: null,
});

const formatHoursMH = (minutes) => {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  return `${h}h ${m}m`;
};

// Month bounds (optional)
const minMonth = '2025-01-01';
const maxMonth = dayjs().format('YYYY-MM');

// Extend filters to hold month string
filters.value.month = dayjs().format('YYYY-MM');

const resetFilters = () => {
  filters.value = {
    dateRange: defaultStart + ' to ' + defaultEnd,
    start_date: defaultStart,
    end_date: defaultEnd,
    searchQuery: '',
    department_id: null,
  };
}

const truncate = (text) => {
  return text && text.length > 25
    ? text.slice(0, 22) + '...'
    : text;
};

const onMonthSelect = (value) => {
  // value may be Date or 'YYYY-MM' from flatpickr month plugin
  let monthStr = null;
  if (value instanceof Date) monthStr = dayjs(value).format('YYYY-MM');
  else if (typeof value === 'string') monthStr = value.slice(0, 7);
  if (!monthStr) return;
  filters.value.month = monthStr;
  // Derive start/end from month
  const start = dayjs(monthStr + '-01').startOf('month').format('YYYY-MM-DD');
  const end = dayjs(monthStr + '-01').endOf('month').format('YYYY-MM-DD');
  filters.value.start_date = start;
  filters.value.end_date = end;
  filters.value.dateRange = start + ' to ' + end;
};

// Update fetchReport to prefer filters.month
const fetchReport = async () => {
  const month = (filters.value.month || filters.value.start_date || '').slice(0, 7);
  if (!month) return;
  loading.value = true;
  try {
    const params = {
      month,
      department_id: filters.value.department_id,
      q: filters.value.searchQuery,
    };
    const { data } = await axios.get('/api/reports/attendance-monthly', {
      params,
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    if (data.success) {
      report.value = data.data || { rows: [], dates: [] };
      if (!report.value.rows?.length) $toast.info('No data found for selected month');
    } else {
      throw new Error(data.message || 'Failed to fetch monthly report');
    }
  } catch (err) {
    console.error('FetchReport error', err);
    $toast.error(err.response?.data?.message || err.message || 'Failed to fetch monthly report');
    report.value = { rows: [], dates: [] };
  } finally {
    loading.value = false;
  }
};

const fetchDepartments = async () => {
  try {
    const headers = {};
    if (accessToken && accessToken.value) headers.Authorization = `Bearer ${accessToken.value}`;
    const res = await axios.get('/api/departments?context=filters', { headers });
    const payload = res?.data ?? {};
    // payload can be { data: [...] } or [...]
    let list = payload.data ?? payload;
    if (!Array.isArray(list)) list = [];
    // Normalize to { id, name }
    departments.value = list.map(d => ({ id: d.id, name: d.name }));
  } catch (err) {
    console.error('fetchDepartments error', err, err?.response?.data);
    const serverMsg = err?.response?.data?.message || err?.response?.data || null;
    $toast.error('Failed to fetch departments' + (serverMsg ? ': ' + serverMsg : ''));
    departments.value = [];
  }
};

const exportPDF = async () => {
  // Derive month (YYYY-MM) from start_date
  const month = (filters.value.start_date || '').slice(0, 7);
  if (!month) { $toast.warning('Select month'); return; }
  isExporting.value = true;
  try {
    const response = await axios.get('/api/reports/attendance-monthly/export-pdf', {
      params: { month, department_id: filters.value.department_id, q: filters.value.searchQuery },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `attendance_monthly_${month}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    $toast.success('PDF exported');
  } catch (err) {
    console.error('export pdf', err);
    $toast.error('Failed to export PDF');
  } finally { isExporting.value = false; }
};

const exportExcel = async () => {
  const month = (filters.value.start_date || '').slice(0, 7);
  if (!month) { $toast.warning('Select month'); return; }
  isExporting.value = true;
  try {
    const response = await axios.get('/api/reports/attendance-monthly/export-excel', {
      params: { month, department_id: filters.value.department_id, q: filters.value.searchQuery },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `attendance_monthly_${month}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    $toast.success('Excel exported');
  } catch (err) {
    console.error('export excel', err);
    $toast.error('Failed to export Excel');
  } finally { isExporting.value = false; }
};

// Replace the broken watcher with reactive sources + debounce
const debounce = (fn, delay = 300) => {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
};

watch(
  [() => filters.value.department_id, () => filters.value.searchQuery, () => filters.value.month],
  debounce(() => { fetchReport(); }, 250),
  { deep: true }
);

onMounted(() => {
  fetchReport();
  fetchDepartments();
});

// Normalize backend rows into VDataTable rows and expose name/email for the avatar+text slot
const tableRows = computed(() => {
  const rows = report.value?.rows || [];
  return rows.map((r, idx) => ({
    sr: idx + 1,
    // keep the displayed column value under the same key as header
    employee: r.name || '',
    // fields used by the avatar/name slot
    name: r.name || '',
    email:  r.official_email || r.personal_email || '-',
    employee_code: r.employee_code || '',
    profile_picture: r.profile_picture || null,

    department: r.department || '-',
    designation: r.designation || '-',
    total_working_days: Number(r.total_working_days ?? 0),
    present: Number(r.present ?? 0),
    leave: Number(r.leave ?? 0),
    wfh: Number(r.wfh ?? 0),
    absent: Number(r.absent ?? 0),
    late_arrivals: Number(r.late_arrivals ?? 0),
    not_marked: Number(r.not_marked ?? 0),
    half_day: Number(r.half_day ?? 0),
    total_present: Number(r.total_present ?? 0),
    short_leave: Number(r.short_leave ?? 0),
    allocated_hours: formatHoursMH(r.allocated_minutes ?? 0),
    worked_hours: formatHoursMH(r.worked_minutes ?? 0),

    id: r.id || r.employee_id || undefined,
  }));
});
</script>

<style scoped>
.compact-table >>> th:first-child {
  width: 50px !important;
}

.custom-table >>> th {
  white-space: pre-line !important;
  line-height: 1.1 !important;
}

.department-table >>> th {
  white-space: pre-line !important;
  line-height: 1.1 !important;
}

/* Color every even row lightly to enhance readability */
.custom-table >>> tbody tr:nth-child(even) {
  background-color: rgba(213, 93, 54, 0.02); /* light orange */
}
</style>
<style>
.flatpickr-calendar .flatpickr-monthSelect-month.selected {
  background-color: rgb(var(--v-theme-primary, 115, 103, 240)) !important;
  border-color: rgb(var(--v-theme-primary, 115, 103, 240)) !important;
}
</style>
