<script setup>
import axios from 'axios'
import { useToast } from 'vue-toast-notification'
import 'vue-toast-notification/dist/theme-sugar.css'
import { hasPermission } from '@/utils/permission'
import AddEditTaxSlabDrawer from '@/views/apps/hrm/payroll/tax-slab/AddEditTaxSlabDrawer.vue'
import AppTextField from '@/@core/components/app-form-elements/AppTextField.vue'

const searchQuery = ref('')
const slabs = ref([])
const loading = ref(false)
const error = ref(null)
const isExporting = ref(false)

const isDrawerVisible = ref(false)
const currentSlab = ref(null)
const isSubmitting = ref(false)
const $toast = useToast()
const accessToken = useCookie('accessToken')

const headers = [
  { title: 'Slab Name', key: 'name' },
  { title: 'Min Salary (Annual)', key: 'min_salary' },
  { title: 'Max Salary (Annual)', key: 'max_salary' },
  { title: 'Tax Rate (%)', key: 'tax_rate' },
  { title: 'Fixed Amount (Annual)', key: 'fixed_amount' },
  { title: 'Threshold (Annual)', key: 'exceeding_threshold' },
  { title: 'Updated At', key: 'updated_at' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchSlabs = async () => {
  loading.value = true
  try {
    const response = await $api(`/payroll/tax-slabs?q=${encodeURIComponent(searchQuery.value || '')}`)
    slabs.value = response.data || []
  } catch (err) {
    error.value = err
    $toast.error('Failed to fetch tax slabs')
  } finally {
    loading.value = false
  }
}

watch(
  searchQuery,
  () => {
    fetchSlabs()
  },
  { deep: true },
)

onMounted(() => {
  fetchSlabs()
})

const formatDate = dateString => {
  return dateString ? new Date(dateString).toISOString().split('T')[0] : '-'
}

const formatMoney = (val, options = {}) => {
  const n = Number(val ?? 0)
  return n.toLocaleString('en-US', { minimumFractionDigits: options.decimals ?? 0, maximumFractionDigits: options.decimals ?? 0 })
}

const openAdd = () => {
  currentSlab.value = null
  isDrawerVisible.value = true
}

const openEdit = slab => {
  currentSlab.value = { ...slab }
  isDrawerVisible.value = true
}

const handleSubmit = async formData => {
  isSubmitting.value = true
  try {
    const method = formData.id ? 'PUT' : 'POST'
    const url = formData.id ? `/payroll/tax-slabs/${formData.id}` : '/payroll/tax-slabs'

    const payload = {
      name: formData.name,
      min_salary: formData.min_salary,
      max_salary: formData.max_salary === '' ? null : formData.max_salary,
      tax_rate: formData.tax_rate,
      fixed_amount: formData.fixed_amount,
      exceeding_threshold: formData.exceeding_threshold,
    }

    await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    })

    $toast.success(`Tax slab ${formData.id ? 'updated' : 'created'} successfully`)
    await fetchSlabs()
    isDrawerVisible.value = false
  } catch (err) {
    console.log('Error', err);
    $toast.error(err?._data?.message || `Failed to ${formData.id ? 'update' : 'create'} tax slab`)
  } finally {
    isSubmitting.value = false
  }
}

const deleteSlab = async id => {
  try {
    await $api(`/payroll/tax-slabs/${id}`, { method: 'DELETE' })
    await fetchSlabs()
    $toast.success('Tax slab deleted successfully!')
  } catch (err) {
    $toast.error('Failed to delete tax slab')
  }
}

const downloadBlob = (blob, filename) => {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

const exportSlabs = async format => {
  isExporting.value = true
  try {
    const endpoint = format === 'pdf' ? '/api/payroll/tax-slabs/export/pdf' : '/api/payroll/tax-slabs/export/excel'
    const response = await axios.get(endpoint, {
      params: { q: searchQuery.value || undefined },
      responseType: 'blob',
      headers: accessToken.value ? { Authorization: `Bearer ${accessToken.value}` } : undefined,
    })

    const blob = new Blob([response.data], {
      type: format === 'pdf'
        ? 'application/pdf'
        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').replace('T', '_')
    downloadBlob(blob, `tax-slabs_${timestamp}.${format === 'pdf' ? 'pdf' : 'xlsx'}`)
    $toast.success(`Tax slabs ${format.toUpperCase()} exported successfully`)
  } catch (err) {
    console.error('Failed to export tax slabs', err)
    $toast.error('Failed to export tax slabs')
  } finally {
    isExporting.value = false
  }
}
</script>

<template>
  <section>
    <VBreadcrumbs class="px-0 pb-2 pt-0 help-center-breadcrumbs" :items="[{ title: 'Payroll' }, { title: 'Tax Slabs' }]" />

    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="4">
            <AppTextField v-model="searchQuery" placeholder="Search tax slabs" />
          </VCol>
          <VCol cols="12" md="2">
            <VBtn
              color="success"
              :loading="isExporting"
              :disabled="isExporting || !slabs.length"
              prepend-icon="tabler-file-export"
            >
              Export
            </VBtn>
            <VMenu activator="parent">
                <VList>
                  <VListItem prepend-icon="tabler-file-type-pdf" title="Export PDF" @click="exportSlabs('pdf')" />
                  <VListItem prepend-icon="tabler-file-spreadsheet" title="Export Excel" @click="exportSlabs('excel')" />
                </VList>
              </VMenu>
          </VCol>
          <VSpacer />

          <VCol cols="12" md="5" class="d-flex justify-end">
            <VBtn v-if="hasPermission('tax_slab.create')" prepend-icon="tabler-plus" @click="openAdd">
              Add New
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VAlert type="info" variant="tonal" class="ma-4">
        Tax slabs expect annual values. Monthly payroll deductions are auto-derived (see the helper line under each figure).
      </VAlert>

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading tax slabs...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || 'Failed to load tax slabs' }}
      </VAlert>

      <VDataTable v-else :headers="headers" :items="slabs" class="text-no-wrap">
        <template #item.min_salary="{ item }">
          <div class="d-flex flex-column">
            <span>PKR {{ formatMoney(item.min_salary) }}</span>
            <span class="text-caption text-medium-emphasis">Monthly ≈ {{ formatMoney(item.min_salary_monthly, { decimals: 2 }) }}</span>
          </div>
        </template>
        <template #item.max_salary="{ item }">
          <div class="d-flex flex-column">
            <span>{{ item.max_salary == null ? '—' : `PKR ${formatMoney(item.max_salary)}` }}</span>
            <span v-if="item.max_salary_monthly != null" class="text-caption text-medium-emphasis">
              Monthly ≈ {{ formatMoney(item.max_salary_monthly, { decimals: 2 }) }}
            </span>
          </div>
        </template>
        <template #item.tax_rate="{ item }">
          <span>{{ Number(item.tax_rate ?? 0).toFixed(2) }}%</span>
        </template>
        <template #item.fixed_amount="{ item }">
          <div class="d-flex flex-column">
            <span>PKR {{ formatMoney(item.fixed_amount) }}</span>
            <span class="text-caption text-medium-emphasis">Monthly ≈ {{ formatMoney(item.fixed_amount_monthly, { decimals: 2 }) }}</span>
          </div>
        </template>
        <template #item.exceeding_threshold="{ item }">
          <div class="d-flex flex-column">
            <span>PKR {{ formatMoney(item.exceeding_threshold) }}</span>
            <span class="text-caption text-medium-emphasis">Monthly ≈ {{ formatMoney(item.exceeding_threshold_monthly, { decimals: 2 }) }}</span>
          </div>
        </template>

        <template #item.created_at="{ item }">
          <div class="text-high-emphasis text-body-1">{{ formatDate(item.created_at) }}</div>
        </template>
        <template #item.updated_at="{ item }">
          <div class="text-high-emphasis text-body-1">{{ formatDate(item.updated_at) }}</div>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center gap-1">
            <VBtn
              v-if="hasPermission('tax_slab.update')"
              icon
              size="small"
              variant="text"
              color="default"
              @click="openEdit(item)"
            >
              <VIcon icon="tabler-pencil" />
            </VBtn>

            <VBtn
              v-if="hasPermission('tax_slab.delete')"
              icon
              size="small"
              variant="text"
              color="default"
              @click="deleteSlab(item.id)"
            >
              <VIcon icon="tabler-trash" />
            </VBtn>
          </div>
        </template>
      </VDataTable>
    </VCard>

    <AddEditTaxSlabDrawer
      v-model:is-drawer-open="isDrawerVisible"
      :slab="currentSlab"
      :loading="isSubmitting"
      @submit="handleSubmit"
    />
  </section>
</template>

