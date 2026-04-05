<template>
  <VDialog :model-value="modelValue" max-width="1000" @update:model-value="$emit('update:modelValue', $event), items = [], total = 0">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <div>
          Assignment History
          <div class="text-caption text-medium-emphasis" v-if="asset">
            {{ asset.name }} <span v-if="asset.serial_no">({{ asset.serial_no }})</span>
          </div>
        </div>
        <IconBtn @click="$emit('update:modelValue', false), items = [], total = 0">
          <VIcon icon="tabler-x" />
        </IconBtn>
      </VCardTitle>

      <VCardText>
        <VRow class="mb-2">
          <VCol cols="12" sm="4">
            <AppTextField v-model="q" placeholder="Search employee" />
          </VCol>
          <VCol cols="12" sm="4">
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
          <VCol cols="12" sm="4" class="d-flex gap-2 align-center justify-end">
            <VBtn color="success" variant="outlined" :loading="isExporting" :disabled="isExporting || !asset?.id" @click="exportPdf">
              <VIcon start icon="tabler-file-type-pdf" />
              Export PDF
            </VBtn>
          </VCol>
        </VRow>

        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :headers="headers"
          :items="items"
          :items-length="total"
          :loading="loading"
          loading-text="Loading data..."
          class="text-no-wrap"
          @update:options="updateOptions"
        >
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
      </VCardText>

      <VDivider />
      <VCardActions>
        <VSpacer />
        <VBtn variant="text" @click="$emit('update:modelValue', false), items = [], total = 0">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import axios from 'axios'
import { ref, watch } from 'vue'
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  asset: { type: Object, default: null },
})

defineEmits(['update:modelValue'])

const headers = [
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

const itemsPerPage = ref(10)
const page = ref(1)

const fetchHistories = async () => {
  if (!props.asset?.id) return
  loading.value = true
  try {
    const response = await $api(`/assets/${props.asset.id}/assignment-histories`, {
      method: 'GET',
      params: {
        q: q.value || undefined,
        returned: returned.value === null ? undefined : returned.value,
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
  } finally {
    loading.value = false
  }
}

const updateOptions = (options) => {
  if (typeof options.page !== 'undefined') page.value = options.page
  if (typeof options.itemsPerPage !== 'undefined') itemsPerPage.value = options.itemsPerPage
  fetchHistories()
}

const exportPdf = async () => {
  if (!props.asset?.id) return
  isExporting.value = true
  try {
    const response = await axios.get(`/api/assets/${props.asset.id}/assignment-historiesExport/pdf`, {
      params: {
        q: q.value || undefined,
        returned: returned.value === null ? undefined : returned.value,
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
    link.setAttribute('download', `asset_${props.asset.id}_assignment_history_${ts}.pdf`)

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

watch([q, returned], () => {
  page.value = 1
  fetchHistories()
})
</script>

