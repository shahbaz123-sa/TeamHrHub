<script setup>
import { onMounted } from 'vue'

const headers = [
  { title: 'Supplier Name', key: 'name' },
  { title: 'Phone', key: 'phone' },
  { title: 'Address', key: 'address' },
  { title: 'Brand', key: 'brand' },
  { title: 'Commodity', key: 'product_category' },
  { title: 'Date', key: 'date' },
]

const filters = ref({
  q: '',
  type: null,
})

const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const suppliers = ref([])
const totalSuppliers = ref(0)
const dataTableLoading = ref(false)

const fetchSuppliers = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/suppliers', {
    query: {
      ...filters.value,
      page: page.value,
      per_page: itemsPerPage.value,
      sort_by: sortBy.value,
      order: orderBy.value,
    },
  }))

  suppliers.value = data.value?.data ?? []
  totalSuppliers.value = data.value?.meta?.total || 0
  dataTableLoading.value = false
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
  fetchSuppliers()
}, { deep: true })

onMounted(() => {
  fetchSuppliers()
})
</script>

<template>
  <div>
    <VCard title="Filters" class="mb-6">
      <VCardText>
        <VRow>
          <VCol cols="12" sm="3">
            <AppTextField v-model="filters.q" placeholder="Search Suppliers" />
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
        :items="suppliers"
        :items-length="totalSuppliers"
        :loading="dataTableLoading"
        :loading-text="'Loading data...'"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <RouterLink
                :to="{ name: 'crm-supplier-details-id', params: { id: item.id } }"
                class="text-link font-weight-medium d-inline-block"
              >
                <div class="text-base">
                  {{ item?.name }}
                </div>
                <div class="text-sm">
                  {{ item?.email }}
                </div>
              </RouterLink>
            </div>
          </div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalSuppliers" />
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>
