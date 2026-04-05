<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Assets' }, { title: 'Assets List' }]"
    />
    <VCard class="mb-6">
      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" sm="6">
            <VCard class="pa-2 d-flex align-center" color="#e8f5e9" variant="outlined">
              <VIcon color="success" size="32">tabler-user-check</VIcon>
              <div class="ml-3">
                <div class="text-caption">Assigned Assets</div>
                <div class="text-h6 font-weight-bold">{{ counts.assigned ?? 0 }}</div>
              </div>
            </VCard>
          </VCol>
          <VCol cols="12" sm="6">
            <VCard class="pa-2 d-flex align-center" color="#fff3e0" variant="outlined">
              <VIcon color="warning" size="32">tabler-user-off</VIcon>
              <div class="ml-3">
                <div class="text-caption">Unassigned Assets</div>
                <div class="text-h6 font-weight-bold">{{ counts.unassigned ?? 0 }}</div>
              </div>
            </VCard>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" sm="3">
            <AppSelect v-model="selectedType" placeholder="Asset Type" :items="assetTypes" clearable />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect v-model="selectedAssignType" placeholder="Assigned type" :items="assignTypes" clearable />
          </VCol>
          <VCol cols="12" sm="4">
            <VAutocomplete
              v-model="selectedEmployee"
              v-model:search="employeeSearch"
              :items="employees"
              :loading="employeesLoading"
              label=""
              item-title="name"
              item-value="id"
              placeholder="Select an employee"
              clearable
              no-data-text="No employee found"
            />
          </VCol>
          <VCol cols="12" sm="2" class="text-right">
            <VBtn v-if="hasPermission('asset.create')" prepend-icon="tabler-plus" @click="openDialog">Add Asset</VBtn>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="4" sm="4">
            <AppTextField v-model="searchQuery" placeholder="Search assets" />
          </VCol>
          <VCol cols="auto" class="d-flex align-center">
            <VBtn
              type="button"
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
          <VCol cols="auto" class="d-flex align-center">
            <VBtn type="button" color="success" :loading="isExporting" :disabled="isExporting">
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
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:model-value="selectedRows"
        v-model:page="page"
        :headers="headers"
        :items="assets"
        :items-length="totalAssets"
        :loading="loading"
        loading-text="Loading data..."
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.assigned_to="{ item }">
          <div v-if="item.employee" class="d-flex align-center gap-x-4">
            <VAvatar size="34" :color="!item.employee?.profile_picture ? 'primary' : undefined"
                     :variant="!item.employee?.profile_picture ? 'tonal' : undefined">
              <DocumentImageViewer v-if="item.employee?.profile_picture" :type="'avatar'" :src="item.employee?.profile_picture" :pdf-title="item.employee?.name" />
              <span v-else>{{ item.employee?.name.charAt(0) || '-' }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">{{ item.employee?.name || '—' }}</h6>
              <div class="text-sm">{{ item.employee?.official_email || item.employee?.personal_email || '—' }}</div>
            </div>
          </div>
          <span v-else>N/A</span>
        </template>
        <template #item.actions="{ item }">
          <IconBtn v-if="item.has_assignment_history && hasPermission('asset_assignment_history.read')" title="History" @click="openHistory(item)"><VIcon icon="tabler-history" /></IconBtn>
          <IconBtn v-else-if="!item.has_assignment_history || !hasPermission('asset_assignment_history.read')" title="History not available" disabled=""><VIcon icon="tabler-history-off" /></IconBtn>
          <IconBtn v-if="hasPermission('asset.update')" @click="editAsset(item)"><VIcon icon="tabler-pencil" /></IconBtn>
          <IconBtn v-if="!item.employee && hasPermission('asset.delete')" @click="askDelete(item)"><VIcon icon="tabler-trash" /></IconBtn>
          <IconBtn v-else-if="item.employee && hasPermission('asset.delete')" disabled><VIcon icon="tabler-trash-off" /></IconBtn>
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalAssets" />
        </template>
      </VDataTableServer>
      <ConfirmationDialog
        v-model="isDeleteDialogOpen"
        :title="'Delete Asset'"
        :description="'Are you sure you want to delete this asset? This action cannot be undone.'"
        :confirm-text="'Delete'"
        :cancel-text="'Cancel'"
        :loading="deleteLoading"
        @confirm="confirmDelete"
      />
    </VCard>

    <AddUpdateAssetDialog
      :model-value="dialogOpen"
      @update:model-value="dialogOpen = $event"
      :asset-types="assetTypes"
      :employees="employees"
      @refresh="fetchAssets"
      :asset="currentAsset"
    />

    <AssignmentHistoryDialog
      v-model="historyDialogOpen"
      :asset="historyAsset"
    />
  </section>
</template>
<script setup>
import { hasPermission } from '@/utils/permission'
import AddUpdateAssetDialog from '@/views/apps/hrm/asset/AddUpdateAssetDialog.vue'
import AssignmentHistoryDialog from '@/views/apps/hrm/asset/AssignmentHistoryDialog.vue'
import axios from 'axios'

const route = useRoute()
import { onMounted, ref, watch } from 'vue'
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";
const assets = ref([])
const assetTypes = ref([])
const employees = ref([])
const employeesLoading = ref(false)
const selectedType = ref(null)
const selectedAssignType = ref(null)
const readyToFetchAssets = ref(false)
const selectedEmployee = ref(null)
const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const isExporting = ref(false)
const totalAssets = ref(0)
const loading = ref(false)
const selectedRows = ref([])
const dialogOpen = ref(false)
const employeeSearch = ref('')

const historyDialogOpen = ref(false)
const historyAsset = ref(null)

function openHistory(asset) {
  historyAsset.value = asset
  historyDialogOpen.value = true
}

selectedAssignType.value = Number(route.query.status) || null;

const assignTypes = ref([
  { value: 1, title: "Assign" },
  { value: 2, title: "Unassign" },
]);
const headers = [
  { title: 'Type', key: 'asset_type.name' },
  { title: 'Asset Name', key: 'name' },
  { title: 'Serial No', key: 'serial_no' },
  { title: 'Purchase Date', key: 'purchase_date' },
  { title: 'Assigned to', key: 'assigned_to' },
  { title: 'Make Model', key: 'make_model' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const currentAsset = ref(null)
const isDeleteDialogOpen = ref(false)
const deleteLoading = ref(false)
const deleteTarget = ref(null)

const counts = ref({ assigned: 0, unassigned: 0 })

const openDialog = () => {
  currentAsset.value = null
  dialogOpen.value = true
}

function askDelete(asset) {
  deleteTarget.value = asset
  isDeleteDialogOpen.value = true
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleteLoading.value = true
  try {
    const response = await $api(`/assets/${deleteTarget.value.id}`, { method: 'DELETE' })
    
    if (response.success) {
      fetchAssets()
      isDeleteDialogOpen.value = false
      deleteTarget.value = null
      $toast.success(response.message || 'Asset deleted successfully!')
    } else {
      throw new Error(response.message || 'Failed to delete asset')
    }
  } catch (err) {
    $toast.error(err.message || 'Failed to delete asset!')
  } finally {
    deleteLoading.value = false
  }
}

function editAsset(asset) {
  currentAsset.value = { ...asset }
  dialogOpen.value = true
}
const fetchAssets = async (filters = {}) =>  {
  loading.value = true;
  try {
    const response = await $api('/assets', {
      method: 'GET',
      params: {
        asset_type_id: selectedType.value,
        asset_assign_type: selectedAssignType.value,
        employee_id: selectedEmployee.value,
        q: searchQuery.value,
        per_page: itemsPerPage.value,
        page: page.value,
        ...(sortBy.value && { sortBy: sortBy.value }),
        ...(orderBy.value && { orderBy: orderBy.value }),
         ...filters,
       },
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      withCredentials: true,
    })
    
    if (response.success) {
      assets.value = response.data || []
      totalAssets.value = response.pagination?.total || 0
    } else {
      throw new Error(response.message || 'Failed to load assets')
    }
  } catch (err) {
    assets.value = []
    totalAssets.value = 0
    $toast.error('Failed to load assets')
  } finally {
    loading.value = false
  }
}
async function fetchEmployees() {
  employeesLoading.value = true
  try {
    const response = await $api('/assets/employees/list', { method: 'GET' })
    if (response.success) {
      employees.value = (response.data || []).map(e => ({ name: e.name, id: e.id }))
    } else {
      employees.value = []
    }
  } catch (err) {
    employees.value = []
  } finally {
    employeesLoading.value = false
  }
}

async function fetchAssetTypes() {
  try {
    const response = await $api('/assets/asset-types/list')
    if (response.success) {
      assetTypes.value = (response.data || []).map(t => ({ title: t.name, value: t.id }))
      selectedType.value = Number(route.query.asset_type) || null;
    } else {
      assetTypes.value = []
    }
  } catch (err) {
    assetTypes.value = []
  }
}

const fetchCounts = async () => {
  try {
    const response = await $api('/assets/counts', { method: 'GET' })
    if (response.success) {
      counts.value = response.data || { assigned: 0, unassigned: 0 }
    } else {
      counts.value = { assigned: 0, unassigned: 0 }
    }
  } catch (err) {
    counts.value = { assigned: 0, unassigned: 0 }
  }
}

// Sync table options (page/itemsPerPage/sort) emitted by VDataTableServer
const updateOptions = (options) => {
   // options may include page and itemsPerPage
   if (typeof options.page !== 'undefined') page.value = options.page
   if (typeof options.itemsPerPage !== 'undefined') itemsPerPage.value = options.itemsPerPage

   // handle sort emitted by table
   const uiKey = options.sortBy?.[0]?.key
   const uiOrder = options.sortBy?.[0]?.order
   // map simple UI keys if needed (e.g., assigned_to -> employee.name) — keep as-is for now
   const sortKeyMap = {
     'assigned_to': 'employee.name',
     'asset_type.name': 'asset_types.name',
   }

   sortBy.value = uiKey ? (sortKeyMap[uiKey] ?? uiKey) : undefined
   orderBy.value = uiOrder

   fetchAssets()
}

const resetFilters = () => {
   selectedType.value = null
   selectedAssignType.value = null
   selectedEmployee.value = null
   employeeSearch.value = ''
   searchQuery.value = ''
   itemsPerPage.value = 10
   page.value = 1
   sortBy.value = null
   orderBy.value = null
   selectedRows.value = []
   fetchAssets()
}

const exportExcel = async () => {
  isExporting.value = true
  loading.value = true
  try {
    const params = {
      asset_type_id: selectedType.value || undefined,
      asset_assign_type: selectedAssignType.value || undefined,
      employee_id: selectedEmployee.value || undefined,
      q: searchQuery.value || undefined,
      per_page: -1, // export all
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    }

    const response = await axios.get('/api/assetsExport/excel', {
      params,
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${useCookie('accessToken').value}`,
      },
    })

    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    let filename = 'assets_report'
    if (selectedType.value) filename += `_type_${selectedType.value}`
    if (selectedAssignType.value) filename += `_assign_${selectedAssignType.value}`
    if (selectedEmployee.value) filename += `_emp_${selectedEmployee.value}`
    const ts = new Date().toISOString().replace(/[:.]/g, '-')
    filename += `_${ts}`

    link.setAttribute('download', `${filename}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error exporting assets Excel:', err)
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
    const params = {
      asset_type_id: selectedType.value || undefined,
      asset_assign_type: selectedAssignType.value || undefined,
      employee_id: selectedEmployee.value || undefined,
      q: searchQuery.value || undefined,
      per_page: -1,
      ...(sortBy.value && { sortBy: sortBy.value }),
      ...(orderBy.value && { orderBy: orderBy.value }),
    }

    const response = await axios.get('/api/assetsExport/pdf', {
      params,
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${useCookie('accessToken').value}`,
      },
    })

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url

    let filename = 'assets_report'
    if (selectedType.value) filename += `_type_${selectedType.value}`
    if (selectedAssignType.value) filename += `_assign_${selectedAssignType.value}`
    if (selectedEmployee.value) filename += `_emp_${selectedEmployee.value}`
    const ts = new Date().toISOString().replace(/[:.]/g, '-')
    filename += `_${ts}`

    link.setAttribute('download', `${filename}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error exporting assets PDF:', err)
    $toast.error('Failed to export PDF')
  } finally {
    isExporting.value = false
    loading.value = false
  }
}
watch([selectedType, selectedEmployee, searchQuery, selectedAssignType],() => fetchAssets(), { deep: true })
// ensure employee search cleared when selectedEmployee becomes null (keeps placeholder visible)
watch(selectedEmployee, (v) => {
  if (!v) {
    employeeSearch.value = ''
    return
  }
  // If selectedEmployee is set but not present in the loaded employees list, clear it so placeholder shows
  const exists = employees.value.find(e => e.id === v)
  if (!exists) {
    selectedEmployee.value = null
    employeeSearch.value = ''
  }
})

// also watch employees list: if selectedEmployee was present but the updated employees list no longer contains it, clear selection
watch(employees, () => {
  if (selectedEmployee.value && !employees.value.find(e => e.id === selectedEmployee.value)) {
    selectedEmployee.value = null
    employeeSearch.value = ''
  }
})

onMounted(async () => {
  await fetchAssetTypes()
  await fetchEmployees()
  await fetchCounts()
})
</script>
