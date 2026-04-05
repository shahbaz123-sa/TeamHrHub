<template>
  <section>
    <VBreadcrumbs class="px-0 pb-2 pt-0 help-center-breadcrumbs" :items="[{ title: 'Assets' }, { title: 'Assets History' }]" />

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="3">
            <VAutocomplete
              v-model="selectedAsset"
              v-model:search="assetSearch"
              :items="assets"
              :loading="assetsLoading"
              item-title="name"
              item-value="id"
              placeholder="Select asset"
              clearable
              no-data-text="No asset found"
            />
          </VCol>

          <VCol cols="12" md="3">
            <VAutocomplete
              v-model="selectedEmployee"
              v-model:search="employeeSearch"
              :items="employees"
              :loading="employeesLoading"
              item-title="name"
              item-value="id"
              placeholder="Select employee"
              clearable
              no-data-text="No employee found"
            />
          </VCol>

          <VCol cols="12" md="2">
            <AppSelect
              v-model="returned"
              clearable
              placeholder="Status"
              :items="[
                { title: 'Assigned (not returned)', value: 0 },
                { title: 'Returned', value: 1 },
              ]"
            />
          </VCol>

          <VCol cols="12" md="2">
            <AppTextField v-model="startDate" type="date" placeholder="Start date" />
          </VCol>

          <VCol cols="12" md="2">
            <AppTextField v-model="endDate" type="date" placeholder="End date" />
          </VCol>
        </VRow>

        <VRow class="mt-1">
          <VCol cols="12" md="4">
            <AppTextField v-model="q" placeholder="Search asset/employee" />
          </VCol>

          <VCol cols="auto" class="d-flex align-center">
            <VBtn color="secondary" variant="outlined" @click="resetFilters">
              <VIcon start icon="tabler-refresh" />
              Reset
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

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="items"
        :items-length="total"
        loading-text="Loading data..."
        :loading="loading"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.asset="{ item }">
          <div class="d-flex flex-column">
            <div class="font-weight-medium">{{ item.asset?.name ?? '—' }}</div>
            <div class="text-sm text-medium-emphasis">{{ item.asset?.serial_no ?? '—' }}</div>
          </div>
        </template>

        <template #item.employee="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar
              size="34"
              :color="!item.employee?.profile_picture ? 'primary' : undefined"
              :variant="!item.employee?.profile_picture ? 'tonal' : undefined"
            >
              <DocumentImageViewer v-if="item.employee?.profile_picture" :type="'avatar'" :src="item?.employee?.profile_picture" :pdf-title="item.employee?.name" />
              <span v-else>{{ item.employee?.name.charAt(0) }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.employee?.name }}
              </h6>
              <div class="text-sm">
                {{ item.employee?.official_email || item.employee?.personal_email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip size="small" :color="item.returned_at ? 'secondary' : 'success'" variant="tonal">
            {{ item.returned_at ? 'Returned' : 'Assigned' }}
          </VChip>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="total" />
        </template>
      </VDataTableServer>
    </VCard>
  </section>
</template>

<script setup>
import axios from 'axios'
import { ref, watch, onMounted } from 'vue'
import AppSelect from '@/@core/components/app-form-elements/AppSelect.vue'
import AppTextField from '@/@core/components/app-form-elements/AppTextField.vue'
import TablePagination from '@/@core/components/TablePagination.vue'
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";

const headers = [
  { title: 'Asset', key: 'asset' },
  { title: 'Employee', key: 'employee' },
  { title: 'Assigned Date', key: 'assigned_date' },
  { title: 'Returned At', key: 'returned_at' },
  { title: 'Status', key: 'status', sortable: false },
]

const items = ref([])
const total = ref(0)
const loading = ref(false)
const isExporting = ref(false)

const q = ref('')
const returned = ref(null)
const startDate = ref('')
const endDate = ref('')

const itemsPerPage = ref(10)
const page = ref(1)

const assets = ref([])
const assetsLoading = ref(false)
const selectedAsset = ref(null)
const assetSearch = ref('')

const employees = ref([])
const employeesLoading = ref(false)
const selectedEmployee = ref(null)
const employeeSearch = ref('')

const fetchDropdowns = async () => {
  assetsLoading.value = true
  employeesLoading.value = true
  try {
    const [assetsResp, empResp] = await Promise.all([
      $api('/assets/unassigned/list', { method: 'GET' }).catch(() => null),
      $api('/assets/employees/list', { method: 'GET' }).catch(() => null),
    ])

    // assets/unassigned/list doesn’t include assigned assets; fallback to /assets with per_page=-1
    if (assetsResp?.success) {
      assets.value = (assetsResp.data || []).map(a => ({ id: a.id, name: `${a.name}${a.serial_no ? ` (${a.serial_no})` : ''}` }))
    } else {
      const allAssets = await $api('/assets', { method: 'GET', params: { per_page: -1 } }).catch(() => null)
      assets.value = (allAssets?.data || []).map(a => ({ id: a.id, name: `${a.name}${a.serial_no ? ` (${a.serial_no})` : ''}` }))
    }

    if (empResp?.success) {
      employees.value = (empResp.data || []).map(e => ({ id: e.id, name: e.name }))
    }
  } finally {
    assetsLoading.value = false
    employeesLoading.value = false
  }
}

const fetchHistories = async () => {
  loading.value = true
  try {
    const response = await $api('/asset-assignment-histories', {
      method: 'GET',
      params: {
        asset_id: selectedAsset.value || undefined,
        employee_id: selectedEmployee.value || undefined,
        returned: returned.value === null ? undefined : returned.value,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
        q: q.value || undefined,
        per_page: itemsPerPage.value,
        page: page.value,
      },
    })

    if (response.success) {
      items.value = response.data || []
      total.value = response.pagination?.total || 0
    } else {
      items.value = []
      total.value = 0
    }
  } catch (e) {
    items.value = []
    total.value = 0
    $toast.error('Failed to load assignment histories')
  } finally {
    loading.value = false
  }
}

const updateOptions = options => {
  if (typeof options.page !== 'undefined') page.value = options.page
  if (typeof options.itemsPerPage !== 'undefined') itemsPerPage.value = options.itemsPerPage
  fetchHistories()
}

const resetFilters = () => {
  selectedAsset.value = null
  selectedEmployee.value = null
  returned.value = null
  startDate.value = ''
  endDate.value = ''
  q.value = ''
  itemsPerPage.value = 10
  page.value = 1
  fetchHistories()
}

const exportExcel = async () => {
  isExporting.value = true
  try {
    const response = await axios.get('/api/asset-assignment-historiesExport/excel', {
      params: {
        asset_id: selectedAsset.value || undefined,
        employee_id: selectedEmployee.value || undefined,
        returned: returned.value === null ? undefined : returned.value,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
        q: q.value || undefined,
      },
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${useCookie('accessToken').value}`,
      },
    })

    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const ts = new Date().toISOString().replace(/[:.]/g, '-')
    link.setAttribute('download', `asset_assignment_histories_${ts}.xlsx`)

    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    $toast.error('Failed to export Excel')
  } finally {
    isExporting.value = false
  }
}

const exportPDF = async () => {
  isExporting.value = true
  try {
    const response = await axios.get('/api/asset-assignment-historiesExport/pdf', {
      params: {
        asset_id: selectedAsset.value || undefined,
        employee_id: selectedEmployee.value || undefined,
        returned: returned.value === null ? undefined : returned.value,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
        q: q.value || undefined,
      },
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${useCookie('accessToken').value}`,
      },
    })

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url

    const ts = new Date().toISOString().replace(/[:.]/g, '-')
    link.setAttribute('download', `asset_assignment_histories_${ts}.pdf`)

    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    $toast.error('Failed to export PDF')
  } finally {
    isExporting.value = false
  }
}

watch([q, returned, startDate, endDate, selectedAsset, selectedEmployee], () => {
  page.value = 1
  fetchHistories()
})

onMounted(async () => {
  await fetchDropdowns()
  await fetchHistories()
})
</script>
