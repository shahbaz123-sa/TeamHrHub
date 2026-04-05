<script setup>
import { onMounted } from 'vue'

const headers = [
  { title: 'Reference No', key: 'reference_no' },
  { title: 'Business', key: 'company.company_name' },
  { title: 'Commodity', key: 'category.name' },
  { title: 'Req Credit Amount', key: 'formatted_req_credit_limit' },
  { title: 'Approved Credit Amount', key: 'formatted_app_credit_limit' },
  { title: 'Used Credit Amount', key: 'formatted_used_credit_limit' },
  { title: 'Date', key: 'date' },
  { title: 'Status', key: 'status' },
]

const filters = ref({
  q: '',
  status: null,
})

const statuses = ref([
  { title: 'Pending', value: 'PENDING' },
  { title: 'Under Review', value: 'UNDER_REVIEW' },
  { title: 'Approved', value: 'APPROVED' },
  { title: 'Rejected', value: 'REJECTED' },
])

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const creditApplications = ref([])
const totalApplications = ref(0)
const dataTableLoading = ref(false)

const widgetData = ref([])

const fetchWidgetData = async () => {
  const { data } = await useApi(createUrl('/credit-applications/widgets/status-counts'))
  const stats = data.value.data ?? {}
  widgetData.value = [
    { title: 'Total Requests', value: stats.clients ?? 0, icon: 'tabler-user' },
    { title: 'Total Requested Credit', value: `PKR ${(stats.total_requested_credit)}`, icon: 'tabler-cash' },
    { title: 'Total Approved Credit', value: `PKR ${(stats.total_approved_credit)}`, icon: 'tabler-cash' },
    { title: 'Pending', value: stats.pending ?? 0, icon: 'tabler-clock' },
  ]
}

const fetchCreditApplications = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/credit-applications', {
    query: {
      ...filters.value,
      page,
      per_page: itemsPerPage,
      sort_by: sortBy,
      order: orderBy,
    },
  }))

  creditApplications.value = data.value?.data ?? []
  totalApplications.value = data.value?.meta?.total || 0
  dataTableLoading.value = false
  await fetchWidgetData()
}

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const resolveStatus = (status) => {
  const statusMap = {
    PENDING: { text: 'Pending', color: 'warning' },
    UNDER_REVIEW: { text: 'Under Review', color: 'warning' },
    APPROVED: { text: 'Approved', color: 'success' },
    REJECTED: { text: 'Rejected', color: 'error' },
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
  fetchCreditApplications()
}, { deep: true })

onMounted(() => {
  fetchCreditApplications()
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
            <AppTextField v-model="filters.q" placeholder="Search Credit Application" />
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
        :items="creditApplications"
        :items-length="totalApplications"
        :loading="dataTableLoading"
        :loading-text="'Loading data...'"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.reference_no="{ item }">
          <RouterLink
            :to="{ name: 'crm-company-id-credit-application', params: { id: item.company?.id } }"
            class="text-link font-weight-medium d-inline-block"
          >
            {{ item.reference_no ?? '-' }}
          </RouterLink>
        </template>
        
        <template #item.company.company_name="{ item }">
          <h6 class="text-h6">
            {{ item.company?.company_name ?? '-' }}
          </h6>
          <div class="text-sm text-muted">
            {{ item.customer?.email ?? '' }}
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip :color="resolveStatus(item.status).color" size="small">
            {{ resolveStatus(item.status).text }}
          </VChip>
        </template>

        <template #item.actions></template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalApplications" />
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
