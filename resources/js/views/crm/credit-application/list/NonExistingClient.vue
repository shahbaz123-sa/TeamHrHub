<script setup>
import { onMounted } from 'vue'

const headers = [
  { title: 'Name', key: 'full_name' },
  { title: 'Phone', key: 'phone' },
  { title: 'Company Type', key: 'company_type' },
  { title: 'Industry Type', key: 'industry_type' },
  { title: 'Commodity', key: 'commodity' },
  { title: 'Quantity', key: 'quantity' },
  { title: 'Credit Term', key: 'credit_term' },
  { title: 'Date', key: 'date' },
]

const filters = ref({
  q: '',
  status: null,
})

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const creditApplications = ref([])
const totalApplications = ref(0)
const dataTableLoading = ref(false)

const widgetData = ref([])

const fetchWidgetData = async () => {
  const { data } = await useApi(createUrl('/credit-applications/widgets/status-counts?for=non-existing-clients'))
  const stats = data.value.data ?? {}
  widgetData.value = [
    { title: 'Total Requests', value: stats.clients ?? 0, icon: 'tabler-user' },
  ]
}

const getWidgetBorderClass = id => {
  if(widgetData.value.length == 1) return 'border-e-md'
  else if(id > 0) return 'border-s-md'
}

const fetchCreditApplications = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/credit-application/form-submissions', {
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
              :class="getWidgetBorderClass(id)"
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
          <VCol cols="12" sm="9">
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
        <template #item.full_name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <RouterLink
                :to="{ name: 'crm-credit-application-form-submission-details-id', params: { id: item.id } }"
                class="text-link font-weight-medium d-inline-block"
              >
                <div class="text-base">
                  {{ item?.full_name }}
                </div>
                <div class="text-sm">
                  {{ item?.email }}
                </div>
              </RouterLink>
            </div>
          </div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalApplications" />
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
