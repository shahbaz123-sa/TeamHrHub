<template>
  <section>
    <VBreadcrumbs class="px-0 pb-2 pt-0" :items="[{ title: 'Payroll' }, { title: 'Salary Generation' }]" />

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="2">
            <AppDateTimePicker
              v-model="filters.month"
              label=""
              :config="monthPickerConfig"
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

          <VCol cols="12" md="3">
            <VAutocomplete
              v-model="filters.employment_status_id"
              :items="employmentStatuses"
              label=""
              item-title="title"
              item-value="value"
              placeholder="Employment Status"
              clearable
              no-data-text="No status found"
            />
          </VCol>

          <VSpacer />

          <VCol cols="auto" class="d-flex gap-2">
            <VBtn
              v-if="canHrApprove"
              color="info"
              variant="tonal"
              :disabled="!selectedEmployeeIds.length || approving"
              :loading="approving && approveScope === 'hr'"
              @click="approveSelected('hr', true)"
            >
              <VIcon start icon="tabler-check" /> HR Approve
            </VBtn>

            <VBtn
              v-if="canHrApprove"
              color="warning"
              variant="tonal"
              :disabled="!selectedEmployeeIds.length || approving"
              :loading="approving && approveScope === 'hr_un'"
              @click="approveSelected('hr', false)"
            >
              <VIcon start icon="tabler-x" /> HR Unapprove
            </VBtn>

            <VBtn
              v-if="canCeoApprove"
              color="success"
              variant="tonal"
              :disabled="!selectedEmployeeIds.length || approving"
              :loading="approving && approveScope === 'ceo'"
              @click="approveSelected('ceo', true)"
            >
              <VIcon start icon="tabler-check" /> CEO Approve
            </VBtn>

            <VBtn
              v-if="canCeoApprove"
              color="warning"
              variant="tonal"
              :disabled="!selectedEmployeeIds.length || approving"
              :loading="approving && approveScope === 'ceo_un'"
              @click="approveSelected('ceo', false)"
            >
              <VIcon start icon="tabler-x" /> CEO Unapprove
            </VBtn>

            <VBtn v-if="hasPermission('payroll_generation.create')" color="primary" @click="openGenerateModal">
              <VIcon start icon="tabler-settings" />
              Generate Payroll
            </VBtn>
          </VCol>
        </VRow>

        <VRow>
          <VCol cols="12" md="4">
            <AppTextField v-model="filters.q" placeholder="Search (name, code, email, phone)" />
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
                <VListItem title="Export Excel" prepend-icon="tabler-file-spreadsheet" @click="exportExcel" />
              </VList>
            </VMenu>
          </VCol>

          <VSpacer />
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTable
        v-model="selectedEmployeeIds"
        :headers="tableHeaders"
        :items="tableRows"
        :loading="loading"
        :items-per-page="-1"
        loading-text="Loading data..."
        class="text-no-wrap compact-table custom-table"
        fixed-header
        :show-select="canSelect"
        item-value="employee_id"
      >
        <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" :color="'primary'" variant="tonal">
              <span>{{ item.name?.charAt(0)?.toUpperCase() }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">{{ item.name }}</h6>
              <div class="text-sm">{{ item.employee_code || '' }}</div>
            </div>
          </div>
        </template>

        <template #item.salary="{ item }">
          <span>PKR {{ item.salary.toLocaleString() }}</span>
        </template>

        <template #item.allowances="{ item }">
          <span>{{ item.allowances.toLocaleString() }}</span>
        </template>

        <template #item.tax_amount="{ item }">
          <span>{{ item.tax_amount.toLocaleString() }}</span>
        </template>

        <template #item.gross_salary="{ item }">
          <span>{{ item.gross_salary.toLocaleString() }}</span>
        </template>

        <template #item.salary_month="{ item }">
          <span>{{ item.salary_month }}</span>
        </template>

        <template #item.created_at="{ item }">
          <span>{{ item.created_at }}</span>
        </template>

        <template #item.hr_approved="{ item }">
          <VChip
            size="small"
            :color="item.hr_approved === 'Approved' ? 'success' : 'warning'"
            variant="tonal"
          >
            {{ item.hr_approved }}
          </VChip>
        </template>

        <template #item.ceo_approved="{ item }">
          <VChip
            size="small"
            :color="item.ceo_approved === 'Approved' ? 'success' : 'warning'"
            variant="tonal"
          >
            {{ item.ceo_approved }}
          </VChip>
        </template>

        <template #item.status="{ item }">
          <VChip
            size="small"
            :color="statusColor(item.statusRaw || item.status)"
            variant="tonal"
          >
            {{ item.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
          </VChip>
        </template>

        <template #item.attendance_deduction="{ item }">
          <span>{{ item.attendance_deduction.toLocaleString() }}</span>
        </template>

        <template #bottom>
          <div class="d-flex justify-end pt-2" />
        </template>

        <template #no-data>
          <div class="text-center pa-8">
            <div class="text-h6 mb-2">No generated payroll found</div>
            <div class="text-body-2">Use <b>Generate Payroll</b> to create records for the selected month.</div>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <!-- Generate Payroll Modal -->
    <VDialog v-model="isGenerateModalOpen" max-width="760">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between">
          <span>Generate Payroll</span>
          <VBtn icon variant="text" @click="isGenerateModalOpen = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>

        <VDivider />

        <VCardText>
          <VRow>
            <VCol cols="12" md="4">
              <AppDateTimePicker
                v-model="generateForm.month"
                label="Month"
                :config="monthPickerConfig"
                @update:model-value="onGenerateMonthSelect"
              />
            </VCol>

            <VCol cols="12" md="8">
              <VRadioGroup v-model="generateForm.scope" inline>
                <VRadio label="All Company" value="company" />
                <VRadio label="Department" value="department" />
                <VRadio label="Employees" value="employees" />
              </VRadioGroup>
            </VCol>
          </VRow>

          <VRow v-if="generateForm.scope === 'department'">
            <VCol cols="12">
              <VAutocomplete
                v-model="generateForm.department_id"
                :items="departments"
                item-title="name"
                item-value="id"
                placeholder="Select department"
                clearable
              />
            </VCol>
          </VRow>

          <VRow v-if="generateForm.scope === 'employees'">
            <VCol cols="12">
              <VAutocomplete
                v-model="generateForm.employee_ids"
                :items="employeeOptions"
                item-title="title"
                item-value="value"
                placeholder="Select employees"
                multiple
                chips
                clearable
                :loading="employeesLoading"
              />
            </VCol>
          </VRow>

          <VRow>
            <VCol cols="12">
              <VCheckbox v-model="generateForm.overwrite" label="Overwrite payroll if already generated for selected month" />
            </VCol>
          </VRow>
        </VCardText>

        <VDivider />

        <VCardActions class="justify-end">
          <VBtn variant="outlined" color="secondary" @click="isGenerateModalOpen = false">Cancel</VBtn>
          <VBtn color="primary" :loading="isGenerating" :disabled="isGenerating" @click="generatePayroll">
            Generate
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </section>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { useToast } from 'vue-toast-notification'
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect'
import 'flatpickr/dist/plugins/monthSelect/style.css'

import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import AppTextField from '@/@core/components/app-form-elements/AppTextField.vue'

const $toast = useToast()
const accessToken = useCookie('accessToken')
const userData = useCookie('userData')

const loading = ref(false)
const isExporting = ref(false)
const isGenerateModalOpen = ref(false)
const isGenerating = ref(false)
const employeesLoading = ref(false)
const departments = ref([])
const employmentStatuses = ref([])
const employeeOptions = ref([])
const report = ref({ rows: [] })

const minMonth = '2025-01-01'
const maxMonth = dayjs().format('YYYY-MM')

const monthPickerConfig = {
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
}

const filters = ref({
  month: dayjs().format('YYYY-MM'),
  department_id: null,
  employment_status_id: null,
  q: '',
})

const generateForm = ref({
  month: dayjs().format('YYYY-MM'),
  scope: 'company',
  department_id: null,
  employee_ids: [],
  overwrite: false,
})

const selectedEmployeeIds = ref([])
const approving = ref(false)
const approveScope = ref(null)

const roleNames = computed(() => (userData.value?.roles || []).map(r => r?.name).filter(Boolean))

const hasAnyRoleInsensitive = (needles = []) => {
  const roles = roleNames.value.map(r => String(r))
  const roleSet = new Set(roles.map(r => r.toLowerCase()))
  return needles.some(n => roleSet.has(String(n).toLowerCase()))
}

const canHrApprove = computed(() => hasAnyRoleInsensitive(['HR', 'Hr', 'hR', 'hr']) && hasPermission('approve_payroll.update'))
const canCeoApprove = computed(() => hasAnyRoleInsensitive(['CEO', 'Ceo', 'ceO', 'ceo'])  && hasPermission('approve_payroll.update'))

// console.log('canHrApprove', hasAnyRoleInsensitive(['HR', 'Hr', 'hR', 'hr']), hasPermission('approve_payroll.update'), canHrApprove.value);
const canSelect = computed(() => canHrApprove.value || canCeoApprove.value)

const resetFilters = () => {
  filters.value = {
    month: dayjs().format('YYYY-MM'),
    department_id: null,
    employment_status_id: null,
    q: '',
  }
}

const onMonthSelect = value => {
  let monthStr = null
  if (value instanceof Date) monthStr = dayjs(value).format('YYYY-MM')
  else if (typeof value === 'string') monthStr = value.slice(0, 7)
  if (!monthStr) return
  filters.value.month = monthStr
}

const openGenerateModal = () => {
  generateForm.value.month = (filters.value.month || dayjs().format('YYYY-MM')).slice(0, 7)
  generateForm.value.scope = 'company'
  generateForm.value.department_id = filters.value.department_id
  generateForm.value.employee_ids = []
  generateForm.value.overwrite = false
  isGenerateModalOpen.value = true
}

const onGenerateMonthSelect = value => {
  let monthStr = null
  if (value instanceof Date) monthStr = dayjs(value).format('YYYY-MM')
  else if (typeof value === 'string') monthStr = value.slice(0, 7)
  if (!monthStr) return
  generateForm.value.month = monthStr
}

const fetchDepartments = async () => {
  try {
    const headers = {}
    if (accessToken?.value) headers.Authorization = `Bearer ${accessToken.value}`

    const res = await axios.get('/api/departments?context=filters', { headers })
    const payload = res?.data ?? {}
    const list = Array.isArray(payload) ? payload : payload.data ?? payload

    departments.value = (Array.isArray(list) ? list : []).map(d => ({ id: d.id, name: d.name }))
  } catch (err) {
    console.error('fetchDepartments error', err)
    $toast.error('Failed to fetch departments')
    departments.value = []
  }
}

const fetchEmploymentStatuses = async () => {
  try {
    const res = await axios.get('/api/employment-statuses', {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    const list = Array.isArray(res.data) ? res.data : res.data?.data ?? []
    employmentStatuses.value = list.map(s => ({ title: s.name, value: s.id }));
    filters.value.employment_status_id = list[1].id;
  } catch (err) {
    console.error('fetchEmploymentStatuses error', err)
    employmentStatuses.value = []
  }
}

const fetchEmployeeOptions = async () => {
  // lightweight list: reuse employee-by-rules endpoint
  employeesLoading.value = true
  try {
    const { data } = await axios.get('/api/employee-by-rules', {
      params: { per_page: 200, q: '' },
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })

    const list = data?.data || data || []
    const rows = Array.isArray(list) ? list : list.data || []

    employeeOptions.value = (rows || []).map(e => ({
      title: `${e.name} (${e.employee_code || ''})`,
      value: e.id,
    }))
  } catch (err) {
    console.error('fetchEmployeeOptions error', err)
    employeeOptions.value = []
  } finally {
    employeesLoading.value = false
  }
}

const fetchReport = async () => {
  const month = (filters.value.month || '').slice(0, 7)
  if (!month) return

  loading.value = true
  try {
    const { data } = await axios.get('/api/payroll/salary-generation', {
      params: {
        month,
        department_id: filters.value.department_id,
        employment_status_id: filters.value.employment_status_id,
        q: filters.value.q,
      },
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })

    if (!data?.success) throw new Error(data?.message || 'Failed to fetch salary generation report')

    report.value = data.data || { rows: [] }

    if (!report.value.rows?.length) $toast.info('No data found for selected month')
  } catch (err) {
    console.error('fetchReport error', err)
    $toast.error(err.response?.data?.message || err.message || 'Failed to fetch report')
    report.value = { rows: [] }
  } finally {
    loading.value = false
  }
}

const exportPDF = async () => {
  const month = (filters.value.month || '').slice(0, 7)
  if (!month) {
    $toast.warning('Select month')
    return
  }

  isExporting.value = true
  try {
    const response = await axios.get('/api/payroll/salary-generation/export-pdf', {
      params: { month, department_id: filters.value.department_id, employment_status_id: filters.value.employment_status_id, q: filters.value.q },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `salary_generation_${month}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('exportPDF error', err)
    $toast.error('Failed to export PDF')
  } finally {
    isExporting.value = false
  }
}

const exportExcel = async () => {
  const month = (filters.value.month || '').slice(0, 7)
  if (!month) {
    $toast.warning('Select month')
    return
  }

  isExporting.value = true
  try {
    const response = await axios.get('/api/payroll/salary-generation/export-excel', {
      params: { month, department_id: filters.value.department_id, employment_status_id: filters.value.employment_status_id, q: filters.value.q },
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `salary_generation_${month}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('exportExcel error', err)
    $toast.error('Failed to export Excel')
  } finally {
    isExporting.value = false
  }
}

const debounce = (fn, delay = 300) => {
  let t
  return (...args) => {
    clearTimeout(t)
    t = setTimeout(() => fn(...args), delay)
  }
}

watch(
  [() => filters.value.department_id, () => filters.value.employment_status_id, () => filters.value.q, () => filters.value.month],
  debounce(() => {
    fetchReport()
  }, 250),
)

watch(
  () => generateForm.value.scope,
  scope => {
    if (scope === 'employees' && !employeeOptions.value.length) fetchEmployeeOptions()
  },
)

onMounted(() => {
  fetchDepartments()
  fetchEmploymentStatuses()
  // fetchReport()
})

const generatePayroll = async () => {
  const month = (generateForm.value.month || '').slice(0, 7)
  if (!month) {
    $toast.warning('Select month')
    return
  }

  if (generateForm.value.scope === 'department' && !generateForm.value.department_id) {
    $toast.warning('Select department')
    return
  }

  if (generateForm.value.scope === 'employees' && (!generateForm.value.employee_ids || !generateForm.value.employee_ids.length)) {
    $toast.warning('Select at least one employee')
    return
  }

  isGenerating.value = true
  try {
    const payload = {
      month,
      scope: generateForm.value.scope,
      department_id: generateForm.value.scope === 'department' ? generateForm.value.department_id : null,
      employee_ids: generateForm.value.scope === 'employees' ? generateForm.value.employee_ids : null,
      overwrite: !!generateForm.value.overwrite,
    }

    const { data } = await axios.post('/api/payroll/salary-generation/generate', payload, {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })

    if (!data?.success) throw new Error(data?.message || 'Failed to generate payroll')

    $toast.success(`Payroll generated. Created: ${data.meta?.created ?? 0}, Updated: ${data.meta?.updated ?? 0}, Skipped: ${data.meta?.skipped ?? 0}`)

    // Switch filters month to generated month and refresh
    filters.value.month = month
    isGenerateModalOpen.value = false
    await fetchReport()
  } catch (err) {
    console.error('generatePayroll error', err)
    $toast.error(err.response?.data?.message || err.message || 'Failed to generate payroll')
  } finally {
    isGenerating.value = false
  }
}

const approveSelected = async (scope, approved) => {
  const month = (filters.value.month || '').slice(0, 7)
  if (!month) {
    $toast.warning('Select month')
    return
  }

  if (!selectedEmployeeIds.value.length) {
    $toast.warning('Select employees')
    return
  }

  approving.value = true
  approveScope.value = scope + (approved ? '' : '_un')

  try {
    const { data } = await axios.post(
      '/api/payroll/salary-generation/approve',
      {
        month,
        scope,
        employee_ids: selectedEmployeeIds.value,
        approved: !!approved,
      },
      { headers: { Authorization: `Bearer ${accessToken.value}` } },
    )

    if (!data?.success) throw new Error(data?.message || 'Failed to update approval')

    $toast.success(data?.message || 'Updated')
    await fetchReport()
  } catch (err) {
    console.error('approveSelected error', err)
    $toast.error(err.response?.data?.message || err.message || 'Failed to update approval')
  } finally {
    approving.value = false
    approveScope.value = null
  }
}

const formatHoursMH = minutes => {
  const m = Number(minutes ?? 0)
  const h = Math.floor(m / 60)
  const mm = m % 60
  return `${h}h ${mm}m`
}

const statusColor = status => {
  const s = String(status || '').toLowerCase()
  if (s === 'approved') return 'success'
  if (s === 'hr_approved' || s === 'hr-approved') return 'info'
  if (s === 'generated') return 'warning'
  if (s === 'rejected') return 'error'
  return 'secondary'
}

const formatMonthLabel = monthLike => {
  const str = (monthLike || '').toString().slice(0, 7)
  if (!str) return '-'
  const dt = dayjs(`${str}-01`)
  return dt.isValid() ? dt.format('MMMM-YYYY') : str
}

const tableHeaders = computed(() => [
  { title: 'Sr.#', key: 'sr', align: 'center', sortable: false },
  { title: 'Employee Name\n& code', key: 'employee', align: 'left' },
  { title: 'Department', key: 'department', align: 'center' },
  { title: 'Designation', key: 'designation', align: 'center' },
  { title: 'Salary Month', key: 'salary_month', align: 'center' },
  { title: 'Total\nWorking Days', key: 'total_working_days', align: 'center' },
  { title: 'On Time', key: 'present', align: 'center' },
  { title: 'WFH', key: 'wfh', align: 'center' },
  { title: 'Late\nArrivals', key: 'late_arrivals', align: 'center' },
  { title: 'Total\nPresent', key: 'total_present', align: 'center' },
  { title: 'Leave', key: 'leave', align: 'center' },
  { title: 'Absent', key: 'absent', align: 'center' },
  { title: "Didn't\nMark", key: 'not_marked', align: 'center' },
  { title: 'Allocated\nHours', key: 'allocated_hours', align: 'center' },
  { title: 'Worked\nHours', key: 'worked_hours', align: 'center' },
  { title: 'Monthly Salary', key: 'salary', align: 'right' },
  { title: 'Allowances', key: 'allowances', align: 'right' },
  { title: 'Attendance Deduction', key: 'attendance_deduction', align: 'right' },
  { title: 'Tax', key: 'tax_amount', align: 'right' },
  { title: 'Gross Salary', key: 'gross_salary', align: 'right' },
  { title: 'Created\nAt', key: 'created_at', align: 'center' },
  { title: 'HR Approval', key: 'hr_approved', align: 'center' },
  { title: 'CEO Approval', key: 'ceo_approved', align: 'center' },
  { title: 'Status', key: 'status', align: 'center' },
])

const tableRows = computed(() => {
  const rows = report.value?.rows || []

  return rows.map((r, idx) => ({
    sr: idx + 1,
    employee: r.name || '',
    name: r.name || '',
    employee_code: r.employee_code || '',
    employee_id: r.employee_id || r.id || null,

    department: r.department || '-',
    designation: r.designation || '-',

    total_working_days: Number(r.total_working_days ?? 0),
    present: Number(r.present ?? 0),
    wfh: Number(r.wfh ?? 0),
    late_arrivals: Number(r.late_arrivals ?? 0),
    short_leave: Number(r.short_leave ?? 0),
    half_day: Number(r.half_day ?? 0),

    total_present: Number(r.total_present ?? 0),
    leave: Number(r.leave ?? 0),
    absent: Number(r.absent ?? 0),
    not_marked: Number(r.not_marked ?? 0),

    allocated_hours: formatHoursMH(r.allocated_minutes ?? 0),
    worked_hours: formatHoursMH(r.worked_minutes ?? 0),

    salary: Number(r.salary ?? 0),
    allowances: Number(r.allowances ?? 0),
    tax_amount: Number(r.tax_amount ?? 0),
    gross_salary: Number(r.gross_salary ?? ((Number(r.salary ?? 0) + Number(r.allowances ?? 0)))),

    salary_month: formatMonthLabel(r.month || r.salary_month || filters.value.month),
    created_at: r.created_at ? dayjs(r.created_at).format('YYYY-MM-DD HH:mm') : '-',

    hr_approved: (r.hr_approved === true || r.hr_approved === 1) ? 'Approved' : 'Pending',
    ceo_approved: (r.ceo_approved === true || r.ceo_approved === 1) ? 'Approved' : 'Pending',
    status: r.status || 'generated',
    statusRaw: r.status || 'generated',

    id: r.id || r.employee_id || undefined,

    attendance_deduction: Number(r.attendance_deduction ?? 0),
  }))
})
</script>

<style scoped>
.compact-table >>> th:first-child {
  width: 50px !important;
}

.custom-table >>> th {
  white-space: pre-line !important;
  line-height: 1.1 !important;
}

.custom-table >>> tbody tr:nth-child(even) {
  background-color: rgba(213, 93, 54, 0.02);
}
</style>

<style>
.flatpickr-calendar .flatpickr-monthSelect-month.selected {
  background-color: rgb(var(--v-theme-primary, 115, 103, 240)) !important;
  border-color: rgb(var(--v-theme-primary, 115, 103, 240)) !important;
}
</style>

