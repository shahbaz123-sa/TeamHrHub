<script setup>
import { hasPermission } from '@/utils/permission'
import { ref, watch, onMounted, nextTick, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'vue-toast-notification'
import 'vue-toast-notification/dist/theme-sugar.css'
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";
import useShowExtraFilters from '@/composables/useShowExtraFilters'

const $toast = useToast()

const searchQuery = ref("")
const selectedDepartment = ref()
const departmentSearch = ref('')
const selectedEmploymentType = ref()
const selectedEmploymentStatus = ref()
const readyToFetchEmployees = ref(false)
const sortBy = ref()
const orderBy = ref()
const itemsPerPage = ref(10)
const page = ref(1)
const totalEmployees = ref(0)
const selectedRows = ref([])
const employees = ref([])
const employmentTypes = ref([])
const employmentStatuses = ref([])
const departments = ref([])
const employeesData = ref({ data: [], meta: { total: 0 } })
const loading = ref(false)
const isExporting = ref(false)
const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
// const showExtraFilters = useShowExtraFilters()
const deleteTargetId = ref(null)

const authUser = useCookie('userData').value
const accessToken = useCookie("accessToken").value
const route = useRoute()

const showTerminationColumn = computed(() => Number(selectedEmploymentStatus.value) !== 1)


const updateOptions = (options) => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  if(readyToFetchEmployees.value){
    fetchEmployees()
  }
}

// Headers
const headers = computed(() => {
  const baseHeaders = [
    {
      title: "Employee",
      key: "employee",
    },
    {
      title: "Employee Code",
      key: "employee_code",
    },
    {
      title: "Department",
      key: "department.name",
    },
    {
      title: "Designation",
      key: "designation.title",
    },
    {
      title: "Joining Date",
      key: "date_of_joining",
    },
  ]

  if (showTerminationColumn.value) {
    baseHeaders.push({
      title: "Termination/Resignation Date",
      key: "termination_effective_date",
    })
  }

  baseHeaders.push({
      title: "Reporting To",
      key: "reporting_to",
    },
    {
      title: "Employment Type",
      key: "employment_type",
    },
    {
      title: "Branch",
      key: "branch.name",
    },
    {
      title: "Status",
      key: "employment_status",
    },
    {
      title: "Actions",
      key: "actions",
      sortable: false,
  })

  return baseHeaders
})

// Fetch employees
const fetchEmployees = async () => {
  loading.value = true
  try {
    const { data, error } = await useApi(
      createUrl("/employees", {
        query: {
          q: searchQuery.value,
          department_id: selectedDepartment.value,
          employment_type_id: selectedEmploymentType.value,
          employment_status_id: selectedEmploymentStatus.value,
          per_page: itemsPerPage.value,
          page: page.value,
          sortBy: sortBy.value,
          orderBy: orderBy.value,
        },
      }),
      {
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      }
    )

    if (error.value) {
      throw error.value
    }

    employeesData.value = data.value
    employees.value = employeesData.value?.data || []
    totalEmployees.value = employeesData.value?.meta?.total || 0
  } catch (err) {
    employees.value = []
    totalEmployees.value = 0
  } finally {
    loading.value = false
  }
}

const fetchEmploymentTypes = async () => {
  try {
    const { data } = await $api("/employment-types", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    })
    employmentTypes.value = data.map((item) => ({
      value: item.id,
      title: item.name,
    }))
  } catch (error) {
    $toast.error("Failed to load employment types")
  }
}

const fetchEmploymentStatuses = async () => {
  try {
    const { data } = await $api("/employment-statuses", {
      method: "GET",
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    })
    employmentStatuses.value = data.map((item) => ({
      value: item.id,
      title: item.name,
    }))
    readyToFetchEmployees.value = route.query.department ? false : true;
    selectedEmploymentStatus.value = data[1].id;
  } catch (error) {
    $toast.error("Failed to load employment statuses")
  }
}

// Watch for filter changes
watch(
  [
    searchQuery,
    selectedDepartment,
    selectedEmploymentType,
    selectedEmploymentStatus,
  ],
  () => {
    if(readyToFetchEmployees.value){
      fetchEmployees()
    }
  },
  { deep: true }
)

// Initial fetch when component mounts
onMounted(async () => {
  await fetchEmploymentStatuses();
  // await fetchEmployees();
  await fetchEmploymentTypes();
  fetchDepartments();
})

// Fetch departments for filter dropdown
const fetchDepartments = async () => {
  try {
    const { data } = await $api("/departments?context=filters", {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    })
    departments.value = data.map((dept) => ({
      title: dept.name,
      value: dept.id,
    }))

    if(route.query.department){
      selectedDepartment.value = Number(route.query.department);
      readyToFetchEmployees.value = true;
    }
  } catch (error) {
  }
}

const resolveEmploymentStatusVariant = (status) => {
  const statusLowerCase = status.toLowerCase()
  if (statusLowerCase === "active") return "success"
  if (statusLowerCase === "on_leave") return "warning"
  if (statusLowerCase === "suspended") return "error"
  if (statusLowerCase === "terminated") return "error"

  return "primary"
}

const askDelete = id => {
  deleteTargetId.value = id
  isDeleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!deleteTargetId.value) {
    isDeleteDialogOpen.value = false
    return
  }
  deleteSubmitting.value = true
  try {
    await $api(`/employees/${deleteTargetId.value}`, {
      method: "DELETE",
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    })
    isDeleteDialogOpen.value = false
    deleteTargetId.value = null
    await fetchEmployees()
    $toast.success("Employee deleted successfully.")
  } finally {
    deleteSubmitting.value = false
  }
}

const resetFilters = async () => {
  searchQuery.value = ""
  selectedDepartment.value = null
  departmentSearch.value = ''
  selectedEmploymentType.value = null
  selectedEmploymentStatus.value = null
  itemsPerPage.value = 10
  page.value = 1
  // wait UI update so inputs (like autocomplete) clear visually
  await nextTick()
  fetchEmployees()
}

const buildExportParams = () => {
  return {
    q: searchQuery.value || undefined,
    department_id: selectedDepartment.value || undefined,
    employment_type_id: selectedEmploymentType.value || undefined,
    employment_status_id: selectedEmploymentStatus.value || undefined,
    per_page: itemsPerPage.value,
    page: page.value,
    ...(sortBy.value && { sortBy: sortBy.value }),
    ...(orderBy.value && { orderBy: orderBy.value }),
  }
}

const exportExcel = async () => {
  isExporting.value = true
  loading.value = true
  try {
    const params = buildExportParams()
    const response = await axios.get('/api/employeeExport/excel', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    let filename = 'employees_report'
    if (searchQuery.value) filename += `_${searchQuery.value}`
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`
    if (selectedEmploymentType.value) filename += `_type_${selectedEmploymentType.value}`
    if (selectedEmploymentStatus.value) filename += `_status_${selectedEmploymentStatus.value}`
    const ts = new Date().toISOString().replace(/[:.]/g, '-')
    filename += `_${ts}`

    link.setAttribute('download', `${filename}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    $toast.success('Excel exported successfully.')
  } catch (error) {
    console.error('Error exporting employees Excel:', error)
    $toast.error('Failed to export Excel.')
  } finally {
    isExporting.value = false
    loading.value = false
  }
}

const exportPDF = async () => {
  isExporting.value = true
  loading.value = true
  try {
    const params = buildExportParams()
    const response = await axios.get('/api/employeeExport/pdf', {
      params,
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    let filename = 'employees_report'
    if (searchQuery.value) filename += `_${searchQuery.value}`
    if (selectedDepartment.value) filename += `_dept_${selectedDepartment.value}`
    if (selectedEmploymentType.value) filename += `_type_${selectedEmploymentType.value}`
    if (selectedEmploymentStatus.value) filename += `_status_${selectedEmploymentStatus.value}`
    const ts = new Date().toISOString().replace(/[:.]/g, '-')
    filename += `_${ts}`

    link.setAttribute('download', `${filename}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    $toast.success('PDF exported successfully.')
  } catch (error) {
    console.error('Error exporting employees PDF:', error)
    $toast.error('Failed to export PDF.')
  } finally {
    isExporting.value = false
    loading.value = false
  }
}

const canAdd = () => hasPermission('employee.create')
const canView = employee => hasRole('Super Admin') || hasRole('Hr') || authUser.employee_id === employee.id || hasPermission('employee.read')
const canEdit = employee => authUser.employee_id !== employee.id && hasPermission('employee.update')
const canDelete = employee => authUser.employee_id !== employee.id && hasPermission('employee.delete')
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Employee' }]"
    />
    <!-- 👉 Employee List -->
    <VCard>
      <VCardText>
        <VRow>
          <!-- 👉 Select Department -->
          <VCol cols="12" sm="4">
            <VAutocomplete
              v-model="selectedDepartment"
              v-model:search="departmentSearch"
              :items="departments"
              label=""
              item-title="title"
              item-value="value"
              placeholder="Select Department"
              clearable
              no-data-text="No department found"
            />
          </VCol>
          <!-- 👉 Select Employment Type -->
          <VCol cols="12" sm="3">
            <AppSelect v-model="selectedEmploymentType" placeholder="Employment Type" :items="employmentTypes" clearable
                       clear-icon="tabler-x" />
          </VCol>
          <!-- 👉 Select Status -->
          <VCol cols="12" sm="2">
            <AppSelect v-model="selectedEmploymentStatus" placeholder="Status" :items="employmentStatuses" clearable
              clear-icon="tabler-x" />
          </VCol>
          <!-- Add Employee Button -->
          <VCol cols="12" md="3" class="text-right">
            <VBtn v-if="canAdd()" prepend-icon="tabler-plus" :to="{ name: 'hrm-employee-add' }">
              Add Employee
            </VBtn>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="4">
            <!-- 👉 Search  -->
            <AppTextField v-model="searchQuery" placeholder="Search Employee" />
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
            <VMenu
              activator="parent"
            >
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
          <VCol cols="auto">
            <AppSelect v-model="itemsPerPage" :items="[
            { value: 5, title: '5' },
            { value: 10, title: '10' },
            { value: 20, title: '20' },
            { value: 50, title: '50' },
            { value: -1, title: 'All' },
          ]" style="inline-size: 7rem;" />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- 👉 Datatable  -->
      <VDataTableServer v-model:items-per-page="itemsPerPage" v-model:model-value="selectedRows" v-model:page="page"
        :headers="headers" :items="employees" :items-length="totalEmployees" :loading="loading"
        loading-text="Loading data..." class="text-no-wrap" @update:options="updateOptions">
        <!-- Employee -->
        <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar
              size="34"
              :color="!item.profile_picture ? 'primary' : undefined"
              :variant="!item.profile_picture ? 'tonal' : undefined"
            >
              <DocumentImageViewer v-if="item.profile_picture" :type="'avatar'" :src="item?.profile_picture" :pdf-title="item.name" />
              <span v-else>{{ item.name.charAt(0) }}</span>
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

        <!-- 👉 Employee Code -->
        <template #item.employee_code="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.employee_code }}
          </div>
        </template>

        <!-- 👉 Department -->
        <template #item.department.name="{ item }">
          <div class="text-capitalize text-high-emphasis text-body-1">
            {{ item.department?.name || "N/A" }}
          </div>
        </template>

        <!-- 👉 Designation -->
        <template #item.designation.title="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.designation?.title || "N/A" }}
          </div>
        </template>

        <!-- 👉 Branch -->
        <template #item.branch.name="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.branch?.name || "N/A" }}
          </div>
        </template>

        <!-- 👉 Employment Type -->
        <template #item.employment_type="{ item }">
          <div class="text-capitalize text-high-emphasis text-body-1">
            {{ item.employment_type?.name }}
          </div>
        </template>

        <!-- Status -->
        <template #item.employment_status="{ item }">
          <VChip v-if="item.employment_status" :color="resolveEmploymentStatusVariant(item.employment_status?.name)"
            size="small" label class="text-capitalize">
            {{ item.employment_status?.name }}
          </VChip>
        </template>

        <!-- 👉 Reporting To -->
        <template #item.reporting_to="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.reporting_to?.name || " " }}
          </div>
        </template>

        <!-- Termination/Resignation Date -->
        <template #item.termination_effective_date="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.termination_effective_date || "-" }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn v-if="canView(item)" :to="{
            name: 'hrm-employee-details-id',
            params: { id: item.id },
          }">
            <VIcon icon="tabler-eye" />
          </IconBtn>
          <IconBtn v-if="canEdit(item)" :to="{
            name: 'hrm-employee-edit-id',
            params: { id: item.id },
          }">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn v-if="canDelete(item)" @click="askDelete(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>

        <!-- pagination -->
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalEmployees" />
        </template>
      </VDataTableServer>
    </VCard>
    <!-- Delete Confirmation Dialog -->
    <ConfirmationDialog v-model="isDeleteDialogOpen" title="Are you sure"
      description="This action can not be undone. Do you want to continue?" cancel-text="No" confirm-text="Yes"
      :loading="deleteSubmitting" @confirm="confirmDelete" />
  </section>
</template>

<style scoped lang="scss">
.text-capitalize {
  text-transform: capitalize;
}

.v-card-title {
  background-color: rgba(var(--v-theme-primary), 0.05);
  border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  font-weight: 600;
  padding-block: 1rem;
  padding-inline: 1.5rem;
}

.v-data-table-header {
  background-color: rgba(var(--v-theme-primary), 0.03);
}

.v-data-table-footer {
  border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
