<script setup>
import { humanize } from '@/utils/helpers/str'
import { onMounted } from 'vue'

const headers = [
  { title: 'Reference No', key: 'reference_no' },
  { title: 'Business', key: 'user.email' },
  { title: 'Commodity', key: 'item.product.name' },
  { title: 'Quantity', key: 'item.quantity' },
  { title: 'Req Date', key: 'req_date' },
  { title: 'Status', key: 'status' },
]

const filters = ref({
  q: '',
  status: null,
})

const statuses = ref([
  { title: 'Pending', value: 'pending' },
  { title: 'Quotation Sent', value: 'quotation_sent' },
  { title: 'Completed', value: 'completed' },
])

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const rfqs = ref([])
const totalRfqs = ref(0)
const dataTableLoading = ref(false)

const widgetData = ref([])

const fetchWidgetData = async () => {
  const { data } = await useApi(createUrl('/rfqs/widgets/status-counts'))
  const stats = data.value.data ?? {}
  widgetData.value = [
    { title: 'Clients', value: stats.clients ?? 0, icon: 'tabler-user' },
    { title: 'RFQ', value: stats.rfq ?? 0, icon: 'tabler-file' },
    { title: 'RFQ to Order', value: stats.rfq_to_order ?? 0, icon: 'tabler-checks' },
    { title: 'Pending', value: stats.pending ?? 0, icon: 'tabler-clock' },
  ]
}

const fetchRfqs = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/rfqs', {
    query: {
      ...filters.value,
      page,
      per_page: itemsPerPage,
      sort_by: sortBy,
      order: orderBy,
    },
  }))

  rfqs.value = data.value?.data ?? []
  totalRfqs.value = data.value?.meta?.total || 0
  dataTableLoading.value = false
  await fetchWidgetData()
}

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const resolveStatus = (status) => {
  const statusMap = {
    pending: { text: 'Pending', color: 'warning' },
    quotation_received: { text: 'Quotation Received', color: 'warning' },
    quotation_sent: { text: 'Quotation Sent', color: 'success' },
    completed: { text: 'Completed', color: 'success' },
  }
  return statusMap[status] || { text: status, color: 'secondary' }
}

const filterKey = computed(() => ({
  ...filters.value,
  page: page.value,
  itemsPerPage: itemsPerPage.value,
  sortBy: sortBy.value
}))

watch([filterKey], () => {
  if (dataTableLoading.value) return
  fetchRfqs()
}, { deep: true })

onMounted(() => {
  fetchRfqs()
})
</script>

<template>
  <div>
    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <template v-for="(data, id) in widgetData" :key="id">
            <VCol
              cols="12"
              sm="6"
              md="3"
              class="px-6"
              :class="id > 0 ? 'border-s-md' : ''"
            >
              <div class="d-flex justify-space-between">
                <div>
                  <h4 class="text-h4">{{ data.value }}</h4>
                  <div class="text-body-1">{{ data.title }}</div>
                </div>
                <VAvatar variant="tonal" rounded size="42">
                  <VIcon :icon="data.icon" size="26" class="text-high-emphasis" />
                </VAvatar>
              </div>
            </VCol>
          </template>
        </VRow>
      </VCardText>
    </VCard>

    <VCard title="Filters" class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" sm="3">
            <AppTextField v-model="filters.q" placeholder="Search RFQ" />
          </VCol>
          <VCol cols="12" sm="3">
            <AppSelect v-model="filters.status" :items="statuses" placeholder="Status" clearable />
          </VCol>
          <VCol cols="12" sm="6">
            <div class="d-flex flex-wrap gap-2 float-right">
              <AppSelect
                v-model="itemsPerPage"
                :items="[5, 10, 20, 25, 50]"
                style="max-inline-size: 80px;"
              />
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider class="mt-4" />

      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="rfqs"
        :items-length="totalRfqs"
        :loading="dataTableLoading"
        :loading-text="'Loading data...'"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.reference_no="{ item }">
          <RouterLink
            :to="{ name: 'crm-rfq-details-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block"
          >
            {{ item.reference_no }}
          </RouterLink>
        </template>

        <template #item.item.product.name="{ item }">
          <div>
            {{ item.item?.product?.name ?? humanize(item.item?.product_name) ?? '-' }}
          </div>
        </template>

        <template #item.user.email="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item?.user?.type === 'B2B' ? item?.user?.company?.company_name : item?.user?.username }}
              </h6>
              <div class="text-sm">
                {{ item?.user?.email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip :color="resolveStatus(item.status).color" size="small">
            {{ resolveStatus(item.status).text }}
          </VChip>
        </template>

        <template #item.actions></template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalRfqs" />
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
