<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Payroll' }, { title: 'Salary & Compensation' }]"
    />
    <VCard>
      <VCardText>
        <!-- Filters row 1 -->
        <VRow>
          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="filters.department_id"
              :items="departments"
              label=""
              placeholder="Select department"
              clearable
              item-title="title"
              item-value="value"
              no-data-text="No department found"
            />
          </VCol>

          <VCol cols="12" md="4">
            <VAutocomplete
              v-model="filters.designation_id"
              :items="designations"
              label=""
              placeholder="Select designation"
              clearable
              item-title="title"
              item-value="value"
              no-data-text="No designation found"
            />
          </VCol>

          <VCol cols="12" md="4">
            <AppSelect
              v-model="filters.employment_type_id"
              :items="employmentTypes"
              label=""
              placeholder="Employment type"
              clearable
            />
          </VCol>
        </VRow>

        <!-- Filters row 2 -->
        <VRow>
          <VCol cols="12" md="4">
            <AppSelect
              v-model="filters.employment_status_id"
              :items="employmentStatuses"
              label=""
              placeholder="Employee status"
              clearable
            />
          </VCol>

          <VCol cols="12" md="4">
            <AppDateTimePicker
              v-model="filters.joiningDateRange"
              label=""
              :config="{ mode: 'range' }"
              @update:model-value="onJoiningDateRangeChange"
              clearable
            />
          </VCol>

          <VCol cols="12" md="4">
            <VRow no-gutters>
              <VCol cols="6" class="pe-1">
                <AppTextField
                  v-model="filters.salary_min"
                  type="number"
                  label=""
                  placeholder="Min salary"
                  clearable
                />
              </VCol>
              <VCol cols="6" class="ps-1">
                <AppTextField
                  v-model="filters.salary_max"
                  type="number"
                  label=""
                  placeholder="Max salary"
                  clearable
                />
              </VCol>
            </VRow>
          </VCol>
        </VRow>

        <!-- Actions row -->
        <VRow>
          <VCol cols="12" md="4">
            <AppTextField
              v-model="filters.q"
              placeholder="Search employee (code, CNIC, salary, phone, email)"
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
            <VBtn color="success" :loading="isExporting" :disabled="isExporting">
              <VIcon start icon="tabler-file-export" />
              Export
            </VBtn>
            <VMenu activator="parent">
              <VList>
                <VListItem
                  title="PDF Export"
                  prepend-icon="tabler-file-type-pdf"
                  @click="exportPDF"
                />
                <VListItem
                  title="Excel Export"
                  prepend-icon="tabler-file-spreadsheet"
                  @click="exportExcel"
                />
              </VList>
            </VMenu>
          </VCol>

          <VSpacer />

          <VCol cols="auto">
            <AppSelect
              v-model="itemsPerPage"
              :items="[
                { value: 5, title: '5' },
                { value: 10, title: '10' },
                { value: 20, title: '20' },
                { value: 50, title: '50' },
                { value: -1, title: 'All' },
              ]"
              style="inline-size: 7rem;"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider class="mt-2" />

      <!-- Payroll Table -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :headers="headers"
        :items="payrollData"
        :items-length="totalEmployees"
        :loading="loading"
        loading-text="Loading data..."
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.employee="{ item }">
          <div
            class="d-flex align-center gap-x-4 cursor-pointer"
            @click.stop="goToEmployeeDetails(item.id)"
          >
            <VAvatar size="34" variant="tonal" :color="'primary'">
              <span>{{ item.name.charAt(0) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.name }}
              </h6>
              <div class="text-sm">
                {{ item.official_email || item.personal_email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.employee_code="{ item }">
          <div class="text-sm">
            {{ item.employee_code }}
          </div>
        </template>

        <template #item.payroll_type="{ item }">
          <div>
            {{ item.payroll_type }}
          </div>
        </template>

        <template #item.salary="{ item }">
          <div class="text-high-emphasis text-body-1">
            Rs {{ formatCurrency(item.salary) }}
          </div>
        </template>

        <template #item.net_salary="{ item }">
          <div class="text-high-emphasis text-body-1">
            Rs {{ formatCurrency(item.net_salary) }}
          </div>
        </template>

        <template #item.is_tax_applicable="{ item }">
          <VChip
            size="small"
            variant="tonal"
            :color="item.is_tax_applicable ? 'success' : 'warning'"
          >
            {{ item.is_tax_applicable ? 'Enabled' : 'Disabled' }}
          </VChip>
        </template>

        <template #item.tax_slab_name="{ item }">
          <span class="text-high-emphasis">
            {{ item.tax_slab_name || '—' }}
          </span>
        </template>

        <template #item.tax_amount="{ item }">
          <span class="text-high-emphasis">
            {{ item.is_tax_applicable ? `Rs ${formatCurrency(item.tax_amount || 0)}` : '—' }}
          </span>
        </template>

        <template v-if="hasPermission('employee_salary.read')" #item.actions="{ item }">
          <VBtn
            :icon="hasPermission('employee_salary.create') ? 'tabler-eye-edit' : 'tabler-eye'"
            variant="text"
            color="default"
            size="small"
            @click="viewEmployee(item)"
            :title="`View salary details for ${item.name}`"
          />
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalEmployees" />
        </template>
      </VDataTableServer>
    </VCard>
  </section>
</template>

<script setup>
import axios from 'axios'
import { ref, watch, onMounted } from 'vue'
import { createUrl } from '@/@core/composable/createUrl'
import { hasPermission } from '@/utils/permission'
import { useRouter } from 'vue-router'

// Shared UI components (used directly in template)
import AppTextField from '@/@core/components/app-form-elements/AppTextField.vue'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'
import AppDateTimePicker from '@/@core/components/app-form-elements/AppDateTimePicker.vue'
import TablePagination from '@/@core/components/TablePagination.vue'

const router = useRouter()

const loading = ref(false)
const isExporting = ref(false)

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const payrollData = ref([])
const totalEmployees = ref(0)
const selectedRows = ref([])

const accessToken = useCookie('accessToken')

const filters = ref({
  q: '',
  department_id: null,
  designation_id: null,
  employment_type_id: null,
  employment_status_id: 1,
  salary_min: null,
  salary_max: null,
  joiningDateRange: null,
  joining_start_date: null,
  joining_end_date: null,
})

// Filter options
const departments = ref([])
const designations = ref([])
const employmentTypes = ref([])
const employmentStatuses = ref([])

// Headers for the data table
const headers = [
  { title: 'Employee', key: 'employee', sortable: true },
  { title: 'Employee Code', key: 'employee_code', sortable: true },
  { title: 'Payroll Type', key: 'payroll_type', sortable: true },
  { title: 'Tax Enabled', key: 'is_tax_applicable', sortable: false },
  { title: 'Tax Slab', key: 'tax_slab_name', sortable: false },
  { title: 'Salary', key: 'salary', sortable: true },
  { title: 'Tax Amount', key: 'tax_amount', sortable: false },
  { title: 'Net Salary', key: 'net_salary', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false },
]

const onJoiningDateRangeChange = value => {
  if (!value) {
    filters.value.joining_start_date = null
    filters.value.joining_end_date = null
    return
  }

  if (Array.isArray(value) && value.length === 2) {
    ;[filters.value.joining_start_date, filters.value.joining_end_date] = value
    return
  }

  if (typeof value === 'string' && value.includes('to')) {
    ;[filters.value.joining_start_date, filters.value.joining_end_date] = value
      .split(' to ')
      .map(d => d.trim())
    return
  }

  // single date fallback
  filters.value.joining_start_date = value
  filters.value.joining_end_date = value
}

const buildQuery = () => ({
  q: filters.value.q,
  per_page: itemsPerPage.value === -1 ? 1000 : itemsPerPage.value,
  page: page.value,
  department_id: filters.value.department_id,
  designation_id: filters.value.designation_id,
  employment_type_id: filters.value.employment_type_id,
  employment_status_id: filters.value.employment_status_id,
  salary_min: filters.value.salary_min,
  salary_max: filters.value.salary_max,
  joining_start_date: filters.value.joining_start_date,
  joining_end_date: filters.value.joining_end_date,
  sortBy: sortBy.value,
  orderBy: orderBy.value,
})

// Fetch payroll data
const fetchPayrollData = async () => {
  loading.value = true
  try {
    const { data, error } = await useApi(
      createUrl('/payroll', {
        query: buildQuery(),
      }),
      {
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      },
    )

    if (error.value) throw error.value

    payrollData.value = data.value?.data || []
    totalEmployees.value = data.value?.meta?.total || 0
  } catch (err) {
    console.error('Error fetching payroll data:', err)
    payrollData.value = []
    totalEmployees.value = 0
    $toast.error('Failed to fetch payroll data')
  } finally {
    loading.value = false
  }
}

const fetchDepartments = async () => {
  try {
    const { data } = await $api('/departments?context=filters', {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    departments.value = (data || []).map(dept => ({ title: dept.name, value: dept.id }))
  } catch (_) {}
}

const fetchDesignations = async () => {
  try {
    const { data } = await $api('/designations?context=filters', {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    // apiResource returns {data:[...]}
    const list = Array.isArray(data) ? data : data?.data
    designations.value = (list || []).map(d => ({ title: d.title, value: d.id }))
  } catch (_) {}
}

const fetchEmploymentTypes = async () => {
  try {
    const { data } = await $api('/employment-types?context=filters', {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    const list = Array.isArray(data) ? data : data?.data
    employmentTypes.value = (list || []).map(t => ({ title: t.name, value: t.id }))
  } catch (_) {}
}

const fetchEmploymentStatuses = async () => {
  try {
    const { data } = await $api('/employment-statuses?context=filters', {
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    const list = Array.isArray(data) ? data : data?.data
    employmentStatuses.value = (list || []).map(s => ({ title: s.name, value: s.id }))
  } catch (_) {}
}

// Update table options
const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchPayrollData()
}

const resetFilters = () => {
  filters.value = {
    q: '',
    department_id: null,
    designation_id: null,
    employment_type_id: null,
    employment_status_id: 1,
    salary_min: null,
    salary_max: null,
    joiningDateRange: null,
    joining_start_date: null,
    joining_end_date: null,
  }
  page.value = 1
  fetchPayrollData()
}

const exportExcel = async () => {
  isExporting.value = true
  loading.value = true
  try {
    const params = { ...buildQuery(), per_page: -1, page: 1 }

    const response = await axios.get('/api/payrollExport/excel', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })

    const blob = new Blob([response.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    let filename = 'payroll_report'
    if (filters.value.joining_start_date && filters.value.joining_end_date)
      filename += `_${filters.value.joining_start_date}_to_${filters.value.joining_end_date}`

    link.setAttribute('download', `${filename}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error exporting payroll Excel:', error)
    $toast.error('Failed to export Excel')
  } finally {
    isExporting.value = false
    loading.value = false
  }
}

const exportPDF = async () => {
  isExporting.value = true
  loading.value = true
  try {
    const params = { ...buildQuery(), per_page: -1, page: 1 }

    const response = await axios.get('/api/payrollExport/pdf', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url

    let filename = 'payroll_report'
    if (filters.value.joining_start_date && filters.value.joining_end_date)
      filename += `_${filters.value.joining_start_date}_to_${filters.value.joining_end_date}`

    link.setAttribute('download', `${filename}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error exporting payroll PDF:', error)
    $toast.error('Failed to export PDF')
  } finally {
    isExporting.value = false
    loading.value = false
  }
}

// Format currency
const formatCurrency = amount => new Intl.NumberFormat('en-IN').format(amount)

// View employee details
const viewEmployee = item => {
  try {
    if (!item.id) {
      $toast.error('Employee ID not found')
      return
    }

    const routeExists = router.resolve({
      name: 'hrm-payroll-set-salary-id',
      params: { id: item.id },
    })

    if (routeExists.matched.length === 0) {
      $toast.error('Salary details route not found')
      return
    }

    router.push({
      name: 'hrm-payroll-set-salary-id',
      params: { id: item.id },
    })
  } catch (error) {
    console.error('Error navigating to set-salary page:', error)
    $toast.error('Failed to navigate to salary page')
  }
}

const goToEmployeeDetails = id => {
  if (!id) {
    $toast.error('Employee ID not available')
    return
  }

  router.push(`/hrm/employee/details/${id}`)
}

watch(
  [filters, itemsPerPage, page],
  () => {
    fetchPayrollData()
  },
  { deep: true },
)

onMounted(() => {
  fetchPayrollData()
  fetchDepartments()
  fetchDesignations()
  fetchEmploymentTypes()
  fetchEmploymentStatuses()
})
</script>
