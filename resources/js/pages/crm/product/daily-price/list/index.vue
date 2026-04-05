<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const accessToken = useCookie('accessToken').value

const filters = ref({
  q: '',
  itemsPerPage: 10,
  page: 1
})

const headers = [
  { title: 'Batch', key: 'batch' },
  { title: 'Status', key: 'status' },
  { title: 'Price Date', key: 'price_date' },
  { title: 'Created By', key: 'creater' },
  { title: 'Created At', key: 'created_at' },
  { title: 'Approved By', key: 'approver' },
  { title: 'Approved At', key: 'approved_at' },
  { title: 'Rejected By', key: 'rejecter' },
  { title: 'Rejected At', key: 'rejected_at' },
  { title: 'Action', key: 'action', sortable: false },
]

const batches = ref([])
const totalBatches = ref(0)
const loading = ref(false)

const fetchBatches = async () => {
  loading.value = true
  try {
    const { data, meta } = await $api('/product/daily-prices', {
      query: filters.value,
      method: "GET",
      headers: { Authorization: `Bearer ${accessToken}` }
    })

    batches.value = data || []
    totalBatches.value = meta.total || 0
  } finally {
    loading.value = false
  }
}

const goToImport = () => {
  router.push({ name: 'crm-product-daily-price-import' })
}

const goToPriceHistory = () => {
  router.push({ name: 'crm-product-daily-price-history-list' })
}

const resolveStatus = (status) => {
  const statusMap = {
    'in-progress': { text: 'In Progress', color: 'primary' },
    pending: { text: 'Pending', color: 'warning' },
    processing: { text: 'Processing', color: 'primary' },
    approved: { text: 'Approved', color: 'success' },
    rejected: { text: 'Rejected', color: 'error' },
    failed: { text: 'Failed', color: 'error' },
  }
  return statusMap[status] || { text: status, color: 'secondary' }
}

watch([
  filters,
], () => {
  fetchBatches()
}, { deep: true })

onMounted(() => {
  fetchBatches()
})
</script>

<template>
  <div>
    <VCard>

      <VCardText>
        <VRow>
          <VCol cols="12" sm="3">
            <AppTextField v-model="filters.q" placeholder="Search" />
          </VCol>
          <VCol cols="12" sm="9" class="text-right">
            <div class="d-flex">
              <VSpacer style="min-inline-size: 40px;" />
              <AppSelect v-model="filters.itemsPerPage" :items="[10, 25, 50, 100]" style="flex-grow: 0 !important; max-inline-size: 100px;" class="mr-2" />
              <VBtn color="primary" prepend-icon="tabler-upload" small variant="outlined" @click="goToImport">Import Prices</VBtn>
              <VBtn class="ml-2" color="primary" prepend-icon="tabler-history" small variant="outlined" @click="goToPriceHistory">Price History</VBtn>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VDataTableServer
        v-model:items-per-page="filters.itemsPerPage"
        v-model:page="filters.page"
        loading-text="Loading data..."
        class="text-no-wrap"
        :headers="headers"
        :items="batches"
        :items-length="totalBatches"
        :loading="loading"
      >
        <template #item.status="{ item }">
          <VChip :color="resolveStatus(item.status).color" size="small">
            {{ resolveStatus(item.status).text }}
          </VChip>
        </template>
        
        <template #item.action="{ item }">
          <IconBtn v-if="hasPermission('product_daily_price.read') && item.status !== 'in-progress'" :to="{
            name: 'crm-product-daily-price-details-batch',
            params: { batch: item.id },
          }">
            <VIcon icon="tabler-eye" />
          </IconBtn>
        </template>

        <template #bottom>
          <TablePagination v-model:page="filters.page" :items-per-page="filters.itemsPerPage" :total-items="totalBatches" />
        </template>

      </VDataTableServer>
    </VCard>
  </div>
</template>
