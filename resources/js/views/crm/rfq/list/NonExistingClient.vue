<script setup>
import { onMounted } from 'vue'

const headers = [
  { title: 'Email', key: 'email' },
  { title: 'Commodity', key: 'commodity' },
  { title: 'Product', key: 'product_required' },
  { title: 'Quantity', key: 'quantity' },
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
const rfqs = ref([])
const totalRfqs = ref(0)
const dataTableLoading = ref(false)

const widgetData = ref([])

const fetchWidgetData = async () => {
  const { data } = await useApi(createUrl('/rfqs/widgets/status-counts?for=non-existing-clients'))
  const stats = data.value.data ?? {}
  widgetData.value = [
    { title: 'RFQ', value: stats.rfq ?? 0, icon: 'tabler-file' },
  ]
}

const getWidgetBorderClass = id => {
  if(widgetData.value.length == 1) return 'border-e-md'
  else if(id > 0) return 'border-s-md'
}

const fetchRfqs = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/rfq/form-submissions', {
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
            <AppTextField v-model="filters.q" placeholder="Search RFQ" />
          </VCol>
          <VCol cols="12" sm="3">
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
        <template #item.email="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <RouterLink
                :to="{ name: 'crm-rfq-form-submission-details-id', params: { id: item.id } }"
                class="text-link font-weight-medium d-inline-block"
              >
                  {{ item?.email }}
              </RouterLink>
            </div>
          </div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalRfqs" />
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
