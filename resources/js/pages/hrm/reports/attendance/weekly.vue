<template>
  <section>
    <VBreadcrumbs class="px-0 pb-2 pt-0" :items="[{ title: 'HRM' }, { title: 'Reports' }, { title: 'Weekly Attendance Report' } ]" />
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <AppDateTimePicker
              v-model="filters.dateRange"
              label=""
              :config="{ mode: 'range', locale: { firstDayOfWeek: 1 } }"
              @update:model-value="onDateRangeChange"
              clearable
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
            <VBtn color="success" :loading="isExporting" :disabled="isExporting || !tableRows.length">
              <VIcon start icon="tabler-file-export" /> Export
            </VBtn>
            <VMenu activator="parent">
              <VList>
                <VListItem title="Export PDF" prepend-icon="tabler-file-type-pdf" @click="exportPDF" />
<!--                <VListItem title="Export Excel" prepend-icon="tabler-file-spreadsheet" @click="exportExcel" />-->
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
              <div class="text-sm">{{ item.email || item.official_email || '' }}</div>
            </div>
          </div>
        </template>

        <template v-for="date in dateColumns" :key="date" #[`item.${date}`]="{ item }">
          <span>
            <span style="font-size:13px; margin-top:3px;">
              <span v-if="item.attendance?.[date]?.check_in">{{ formatTime(item.attendance[date].check_in) }}</span>
              <span v-if="!item.attendance?.[date]?.check_in && !item.attendance?.[date]?.check_out"> - </span>
              <span v-if="item.attendance?.[date]?.check_out"> - {{ formatTime(item.attendance[date].check_out) }}</span>
            </span>
            <br />
            <strong :style="{ color: getStatusColor(item.attendance?.[date]?.status || '-') }">
              {{ item.attendance?.[date]?.status?.replace('-', ' ').replace(/\b\w/g, (c) => c.toUpperCase()) ?? '-' }}
            </strong>
          </span>
        </template>

        <template #item.total_present="{ item }">
          <span>{{ item.total_present ?? 0 }}</span>
        </template>
        <template #item.total_absent="{ item }">
          <span>{{ item.total_absent ?? 0 }}</span>
        </template>
        <template #item.total_leaves="{ item }">
          <span>{{ item.total_leaves ?? 0 }}</span>
        </template>
        <template #item.total_working_hours="{ item }">
          <span>{{ formatHoursHM(item.total_working_hours ?? 0) }}</span>
        </template>
        <template #item.late_hours="{ item }">
          <span>{{ formatHoursHM(item.late_hours ?? 0) }}</span>
        </template>
        <template #item.extra_working_hours="{ item }">
          <span>{{ formatHoursHM(item.extra_working_hours ?? 0) }}</span>
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
      <VDivider />
      <VCardText class="pt-4">
        <h5>Department-wise Summary</h5>
        <VDataTable
          :headers="deptTableHeaders"
          :items="serverDepartmentSummary"
          :loading="loading"
          :items-per-page="-1"
          loading-text="Loading data..."
          class="text-no-wrap compact-table department-table"
          fixed-header
        >
          <template #item.on_time_percentage="{ item }">
            <span>{{ item.on_time_percentage ?? '0.0' }}%</span>
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
      </VCardText>
    </VCard>
  </section>
</template>

<script setup>
import {ref, computed, onMounted, watch} from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import { useToast } from 'vue-toast-notification';
import { useRouter } from 'vue-router';

const router = useRouter();
const $toast = useToast();
const accessToken = useCookie('accessToken');
const loading = ref(false);
const isExporting = ref(false);
const report = ref({ dates: [], employees: [] });
const departments = ref([]);
const serverDepartmentSummary = ref([]); // will hold department-wise counts from backend
const serverGrandTotals = ref(null);

// Compute current week Monday (start) and Sunday (end)
const today = dayjs();
const daysSinceMonday = (today.day() + 6) % 7; // 0 if Monday, 6 if Sunday
const weekStartMonday = today.subtract(daysSinceMonday, 'day');
const weekEndSunday = weekStartMonday.add(6, 'day');

const defaultStart = weekStartMonday.format('YYYY-MM-DD');
const defaultEnd = weekEndSunday.format('YYYY-MM-DD');

const filters = ref({
  dateRange: defaultStart + ' to ' + defaultEnd,
  start_date: defaultStart,
  end_date: defaultEnd,
  searchQuery: '',
  department_id: null,
});

const resetFilters = () => {
  filters.value = {
    dateRange: defaultStart + ' to ' + defaultEnd,
    start_date: defaultStart,
    end_date: defaultEnd,
    searchQuery: '',
    department_id: null,
  };
}

const getStatusColor = (status) => {
  switch (status) {
    case "present":
      return "#28c76f";
    case "absent":
      return "#dc3545";
    case "late":
      return "#FF9F43";
    case "half-leave":
      return "#00BAD1";
    case "short-leave":
      return "#00BAD1";
    case "holiday":
      return "#808390";
    case "shift-awaiting":
      return "#808390";
    case "leave":
      return "#00BAD1";
    case "not-marked":
      return "#D55D36";
    default:
      return "#dc3545";
  }
};

const truncate = (text) => {
  return text && text.length > 20
    ? text.slice(0, 17) + '...'
    : text;
};

const formatTime = (value) => {
  return value ? dayjs(value).format('hh:mm A') : ''
}

// Format decimal hours to "Xh Ym"
const formatHoursHM = (hours) => {
  const totalMinutes = Math.round(Number(hours || 0) * 60);
  const h = Math.floor(totalMinutes / 60);
  const m = totalMinutes % 60;
  return `${h}h ${m}m`;
};

const onDateRangeChange = (value) => {
  if (!value) return;
  if (Array.isArray(value) && value.length === 2) {
    filters.value.start_date = value[0];
    filters.value.end_date = value[1];
    filters.value.dateRange = value[0] + ' to ' + value[1];
  } else if (typeof value === 'string' && value?.includes(' to ')) {
    const parts = value.split(' to ').map(p => p.trim());
    filters.value.start_date = parts[0];
    filters.value.end_date = parts[1] || parts[0];
    filters.value.dateRange = filters.value.start_date + ' to ' + filters.value.end_date;
  }
  fetchReport();
};

const dateColumns = computed(() => report.value.dates || []);

const deptTableHeaders = [
  { title: 'Department', key: 'department', align: 'left' },
  { title: 'Total\nEmployees', key: 'total_employees', align: 'center' },
  { title: 'Total\nPresent', key: 'present_count', align: 'center' },
  { title: 'On\nTime', key: 'on_time_pct', align: 'center' },
  { title: 'Late', key: 'late_count', align: 'center' },
  { title: 'Absent', key: 'absent_count', align: 'center' },
  { title: 'Leaves', key: 'leaves', align: 'center' },
  { title: 'Half\nLeaves', key: 'half_leaves', align: 'center' },
  { title: 'Short\nLeaves', key: 'short_leaves', align: 'center' },
  { title: 'On-Time\n%', key: 'on_time_percentage', align: 'center' },
];

const tableHeaders = computed(() => [
  { title: 'Employee Name', key: 'employee', align: 'left' },
  ...dateColumns.value.map(date => ({ title: `${dayjs(date).format('dddd')}\n${dayjs(date).format('DD-MM-YYYY')}`, key: date, align: 'center', sortable: true })),
  { title: 'Present', key: 'total_present', align: 'center' },
  { title: 'Absent', key: 'total_absent', align: 'center' },
  { title: 'Leaves', key: 'total_leaves', align: 'center' },
  { title: 'Half Leaves', key: 'half_leaves', align: 'center' },
  { title: 'Short Leaves', key: 'short_leaves', align: 'center' },
  { title: 'Working\nHours', key: 'total_working_hours', align: 'center' },
  { title: 'Late\nHours', key: 'late_hours', align: 'center' },
  { title: 'Extra\nHours', key: 'extra_working_hours', align: 'center' },
]);
const tableRows = computed(() => report.value.employees || []);

const fetchReport = async () => {
  if (!filters.value.start_date || !filters.value.end_date) return;
  loading.value = true;
  try {
    const params = {
      start_date: filters.value.start_date,
      end_date: filters.value.end_date,
      department_id: filters.value.department_id,
      q: filters.value.searchQuery,
    };
    const { data } = await axios.get('/api/reports/attendance-weekly', {
      params,
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    if (data.success) {
      report.value = data.data || { dates: [], employees: [] };
      if (!report.value.employees.length) $toast.info('No data found for selected range');

      // Try to pick up department-wise counts if backend provided them in the same response
      // Common keys checked: department_summary, departments_summary, dept_summary, departmentSummary, departments
      const responseBody = (data && data.data) ? data.data : data;
      const possibleKeys = ['department_summary', 'departments_summary', 'dept_summary', 'departmentSummary', 'departments'];
      let found = null;
      for (const k of possibleKeys) {
        if (responseBody && responseBody[k]) { found = responseBody[k]; break; }
      }
      if (found && Array.isArray(found)) {
        serverDepartmentSummary.value = found;
        serverGrandTotals.value = responseBody.grand ?? responseBody.total ?? null;
      } else {
        // no server-provided summary; keep serverDepartmentSummary empty and let frontend computed summary be used in template
        serverDepartmentSummary.value = [];
        serverGrandTotals.value = null;
      }

    } else {
      throw new Error(data.message || 'Failed to fetch report');
    }
  } catch (err) {
    console.error('fetchReport error', err);
    $toast.error(err.response?.data?.message || err.message || 'Failed to fetch report');
    report.value = { dates: [], employees: [] };
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
  if (!filters.value.start_date || !filters.value.end_date) { $toast.warning('Select start and end date'); return; }
  isExporting.value = true;
  try {
    const response = await axios.get('/api/reports/attendance-weekly/export-pdf', {
      params: { start_date: filters.value.start_date, end_date: filters.value.end_date, department_id: filters.value.department_id },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `attendance_weekly_${filters.value.start_date}_${filters.value.end_date}.pdf`);
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
  if (!filters.value.start_date || !filters.value.end_date) { $toast.warning('Select start and end date'); return; }
  isExporting.value = true;
  try {
    const response = await axios.get('/api/reports/employee-daily-attendance/export-excel', {
      params: { start_date: filters.value.start_date, end_date: filters.value.end_date, department_id: filters.value.department_id },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    });
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `employee_daily_${filters.value.start_date}_${filters.value.end_date}.xlsx`);
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

const goToAttendance = (employeeId, date, status, code) => {
  router.push({
    path: '/hrm/attendance/list',
    query: {
      employee_id: employeeId,
      start_date: dayjs(date).format('YYYY-MM-DD'),
      end_date: dayjs(date).format('YYYY-MM-DD'),
      status: status,
      q: code,
    },
  });
};

// Replace the broken watcher with reactive sources + debounce
const debounce = (fn, delay = 300) => {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
};

watch(
  [() => filters.value.department_id, () => filters.value.searchQuery],
  debounce(() => { fetchReport(); }, 250),
  { deep: true }
);

onMounted(() => {
  fetchReport();
  fetchDepartments();
});
</script>

<style scoped>
.custom-table >>> th {
  min-width: 130px !important;
}

.custom-table >>> th {
  white-space: pre-line !important;
  line-height: 1.1 !important;
}

.custom-table >>> tbody tr:nth-child(even) {
  background-color: rgba(213, 93, 54, 0.02); /* light orange */
}

.department-table >>> th {
  white-space: pre-line !important;
  line-height: 1.1 !important;
}
</style>
