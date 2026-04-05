<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import { ref } from 'vue'

const accessToken = useCookie('accessToken').value

const filters = ref({
  product: null,
  city_id: null,
  batch_id: null,
  price_date_start: new Date(),
  price_date_end: null,
  q: '',
  itemsPerPage: 10,
  page: 1
})

const headers = [
  { title: 'Batch', key: 'batch.batch' },
  { title: 'Price Date', key: 'batch.price_date' },
  { title: 'Category', key: 'category' },
  { title: 'Sub Category', key: 'sub_category' },
  { title: 'Brand', key: 'brand' },
  { title: 'Sku', key: 'product_sku' },
  { title: 'Title', key: 'new_product_name' },
  { title: 'Variant', key: 'new_variant_name' },
  { title: 'City', key: 'new_city' },
  { title: 'New Price', key: 'new_delivered_price' },
  { title: 'Comments', key: 'comments' },
  { title: 'Is Graph Product', key: 'is_graph_product' },
  { title: 'Graph Category', key: 'graph_category' },
  { title: 'Graph Product', key: 'graph_product' },
  { title: 'Graph Product Unit', key: 'graph_product_unit' },
]

const batch = ref()
const products = ref([])
const totalProducts = ref(0)
const loading = ref(false)

const fetchBatchProducts = async () => {
  loading.value = true
  try {
    const { data, meta } = await $api(`/product/daily-prices/products`, {
      method: 'GET',
      query: filters.value,
    });
    products.value = data || []
    totalProducts.value = meta.total || 0
    batch.value = products.value.length > 0 ? products.value[0].batch : null
  } finally {
    loading.value = false
  }
}

var allProducts = []
var allCities = []
var allBatches = []
const loadingProducts = ref(false)
const loadingCities = ref(false)
const loadingBatches = ref(false)
const loadFilters = async () => {

  loadingProducts.value = true
  const res = await $api('/products', {
    query: {
      page: 1,
      per_page: -1,
      sort_by: 'name',
      order_by: 'asc'
    },
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  allProducts = res?.data ?? []
  await fetchProducts()
  loadingProducts.value = false
  
  loadingCities.value = true
  const cities = await $api('/product/cities', {
    query: {
      page: 1,
      per_page: -1,
      sort_by: 'name',
      order_by: 'asc'
    },
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  allCities = cities?.data ?? []
  await fetchCities()
  loadingCities.value = false
  
  loadingBatches.value = true
  const batches = await $api('/product/daily-prices', {
    query: {
      page: 1,
      itemsPerPage: -1,
    },
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  allBatches = (batches?.data ?? []).map(b => ({ id: b.id, name: b.batch }))
  await fetchBatches()
  loadingBatches.value = false
}

const filterProducts = ref([])
const fetchProducts = async (search = '') => {
  loadingProducts.value = true
  if(search)
  {
    filterProducts.value = await allProducts.filter(p => p.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    filterProducts.value = await [...allProducts]
  }
  loadingProducts.value = false
}

const filterCities = ref([])
const fetchCities = async (search = '') => {
  loadingCities.value = true
  if(search)
  {
    filterCities.value = await allCities.filter(c => c.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    filterCities.value = await [...allCities]
  }
  loadingCities.value = false
}

const filterBatches = ref([])
const fetchBatches = async (search = '') => {
  loadingBatches.value = true
  if(search)
  {
    filterBatches.value = await allBatches.filter(b => b.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    filterBatches.value = await [...allBatches]
  }
  loadingBatches.value = false
}

const getRowProps = ({ item }) => {
  if (
    isEmpty(item.product_id) ||
    isEmpty(item.city_id) ||
    isEmpty(item.new_delivered_price)
  ) {
    return {
      class: 'error-row'
    }
  }
  return {}
}

onMounted(() => {
  loadFilters()
  fetchBatchProducts()
})

watch([
  filters,
], () => {
  fetchBatchProducts()
}, { deep: true })

</script>

<template>
  <div>

    <VCard>

      <VCardText>
        <VRow>

          <VCol cols="12" sm="3">
            <VLabel>
              Batch
            </VLabel>
            <AppAutocomplete
              v-model="filters.batch_id"
              placeholder="Batch"
              :items="filterBatches"
              autocomplete
              :loading="loadingBatches"
              @update:search="fetchBatches"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

          <VCol cols="12" sm="3">
            <VLabel>
              Product
            </VLabel>
            <AppAutocomplete
              v-model="filters.product"
              placeholder="Product"
              :items="filterProducts"
              autocomplete
              :loading="loadingProducts"
              @update:search="fetchProducts"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <VCol cols="12" sm="3">
            <VLabel>
              City
            </VLabel>
            <AppAutocomplete
              v-model="filters.city_id"
              placeholder="City"
              :items="filterCities"
              autocomplete
              :loading="loadingCities"
              @update:search="fetchCities"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <VCol cols="12" sm="3">
            <VLabel>
              Price Date Start
            </VLabel>
            <AppDateTimePicker
              v-model="filters.price_date_start"
              clearable
              clear-icon="tabler-x"
              class="mb-3"
            />
          </VCol>
          
          <VCol cols="12" sm="3">
            <VLabel>
              Price Date End
            </VLabel>
            <AppDateTimePicker
              v-model="filters.price_date_end"
              clearable
              clear-icon="tabler-x"
              class="mb-3"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText>
        <VRow>
          <VCol cols="12" sm="3">
            <AppTextField v-model="filters.q" placeholder="Search" />
          </VCol>
          <VCol cols="12" sm="6" />
          <VCol cols="12" sm="3">
            <div class="float-right">
              <AppSelect v-model="filters.itemsPerPage" :items="[10, 25, 50, 100]" style="max-inline-size: 100px;" />
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
        :items="products"
        :items-length="totalProducts"
        :loading="loading"
        :row-props="getRowProps"
      >
        <template #bottom>
          <TablePagination v-model:page="filters.page" :items-per-page="filters.itemsPerPage" :total-items="totalProducts" />
        </template>
      </VDataTableServer>
    </VCard>
  </div>
</template>

<style>
.error-row {
  background-color: #ffebee !important;
  color: #b71c1c;
}
</style>
