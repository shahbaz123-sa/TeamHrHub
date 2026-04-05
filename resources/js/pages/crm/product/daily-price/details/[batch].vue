<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import { formatLongText, isEmpty } from '@/utils/helpers/str'
import { $toast } from '@/utils/toast'
import axios from 'axios'
import { ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const batchId = route.params.batch
const accessToken = useCookie('accessToken').value

const filters = ref({
  category: null,
  sub_category: null,
  brand: null,
  city: null,
  is_graph_product: null,
  q: '',
  itemsPerPage: 10,
  page: 1
})

const headers = [
  { title: 'Price Date', key: 'batch.price_date' },
  { title: 'Category', key: 'category' },
  { title: 'Sub Category', key: 'sub_category' },
  { title: 'Brand', key: 'brand' },
  { title: 'Sku', key: 'product_sku' },
  { title: 'Title', key: 'new_product_name' },
  { title: 'Variant', key: 'new_variant_name' },
  { title: 'City', key: 'new_city' },
  { title: 'New Price', key: 'new_delivered_price' },
  { title: 'Unit', key: 'product.uom.name' },
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
    const { data, meta } = await $api(`/product/daily-prices/batch/${batchId}`, {
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

var allCategories = []
var allSubCategories = []
var allBrands = []
var allCities = []
const loadFilters = async () => {

  loadingCategories.value = true
  loadingSubCategories.value = true
  loadingBrands.value = true
  loadingCities.value = true

  const res = await $api('/product/daily-prices/filters', {
    headers: { Authorization: `Bearer ${accessToken}` }
  })

  allCategories = await (res?.categories ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))
  allSubCategories = await (res?.subCategories ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))
  allBrands = await (res?.brands ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))
  allCities = await (res?.cities ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))

  await fetchCategories()
  await fetchSubCategories()
  await fetchBrands()
  await fetchCities()

  loadingCategories.value = false
  loadingSubCategories.value = false
  loadingBrands.value = false
  loadingCities.value = false
}

const categories = ref([])
const loadingCategories = ref(false)
const fetchCategories = async (search = '') => {
  loadingCategories.value = true
  if(search)
  {
    categories.value = await allCategories.filter(p => p.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    categories.value = await [...allCategories]
  }
  loadingCategories.value = false
}

const subCategories = ref([])
const loadingSubCategories = ref(false)
const fetchSubCategories = async (search = '') => {
  loadingSubCategories.value = true
  if(search)
  {
    subCategories.value = await allSubCategories.filter(p => p.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    subCategories.value = await [...allSubCategories]
  }
  loadingSubCategories.value = false
}


const brands = ref([])
const loadingBrands = ref(false)
const fetchBrands = async (search = '') => {
  loadingBrands.value = true
  if(search)
  {
    brands.value = await allBrands.filter(p => p.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    brands.value = await [...allBrands]
  }
  loadingBrands.value = false
}


const cities = ref([])
const loadingCities = ref(false)
const fetchCities = async (search = '') => {
  loadingCities.value = true
  if(search)
  {
    cities.value = await allCities.filter(p => p.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    cities.value = await [...allCities]
  }
  loadingCities.value = false
}

const approvingRejecting = ref(false)
const approveBatch = async () => {
  approvingRejecting.value = true
  const { data } = await $api(`/product/daily-prices/batch/${batchId}/approve`, {
    headers: { Authorization: `Bearer ${accessToken}` },
    method: 'POST',
    query: filters.value,
  });

  if(data) {
    batch.value = data
    $toast.success('Batch approved successfully')
  }
  else { $toast.error('Failed to approve batch') }
  approvingRejecting.value = false
}
const rejectBatch = async () => {
  approvingRejecting.value = true
  const { data } = await $api(`/product/daily-prices/batch/${batchId}/reject`, {
    headers: { Authorization: `Bearer ${accessToken}` },
    method: 'POST',
    query: filters.value,
  });

  if(data) {
    batch.value = data
    $toast.success('Batch rejected successfully')
  }
  else { $toast.error('Failed to reject batch') }
  approvingRejecting.value = false
}

const isExporting = ref(false)
const exportExcel = async () => {
  isExporting.value = true;
  try {

    const response = await axios.get(`/api/product/daily-prices/batch/${batchId}/export`, {
      responseType: 'blob',
      headers: { Authorization: `Bearer ${accessToken}` },
    });

    const blob = new Blob([response.data], {
      type: response.headers['content-type']
    });

    const url = window.URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = url;

    const disposition = response.headers['content-disposition'];
    let fileName = `daily-price-${batchId}.xlsx`;

    if (disposition && disposition.includes('filename=')) {
      fileName = disposition.split('filename=')[1].replace(/"/g, '');
    }

    link.setAttribute('download', fileName);

    document.body.appendChild(link);
    link.click();
    link.remove();

    $toast.success('Excel exported successfully');
  } catch (error) {
    console.error('Error exporting Excel:', error);
    $toast.error('Failed to export Excel');
  } finally {
    isExporting.value = false;
  }
};

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
    <div class="d-flex justify-end gap-2 mb-4" v-if="batch?.status === 'pending'">
      <VBtn
        color="success"
        @click="approveBatch"
        :loading="approvingRejecting"
      >
        Approve Batch
      </VBtn>

      <VBtn
        color="error"
        @click="rejectBatch"
        :loading="approvingRejecting"
      >
        Reject Batch
      </VBtn>
    </div>
    <VCard>

      <VCardText>
        <VRow>
          <VCol cols="12" sm="3">
            <AppAutocomplete
              v-model="filters.category"
              placeholder="Category"
              :items="categories"
              autocomplete
              :loading="loadingCategories"
              @update:search="fetchCategories"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <VCol cols="12" sm="3">
            <AppAutocomplete
              v-model="filters.sub_category"
              placeholder="Sub-Category"
              :items="subCategories"
              autocomplete
              :loading="loadingSubCategories"
              @update:search="fetchSubCategories"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

          <VCol cols="12" sm="3">
            <AppAutocomplete
              v-model="filters.brand"
              placeholder="Brand"
              :items="brands"
              autocomplete
              :loading="loadingBrands"
              @update:search="fetchBrands"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <VCol cols="12" sm="3">
            <AppAutocomplete
              v-model="filters.city"
              placeholder="Cities"
              :items="cities"
              autocomplete
              :loading="loadingCities"
              @update:search="fetchCities"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <VCol cols="12" sm="3">
            <AppAutocomplete
              v-model="filters.is_graph_product"
              placeholder="Is Graph Product"
              :items="[{id: 'true', name: 'Yes'}]"
              autocomplete
              clearable
              clear-icon="tabler-x"
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
          <VCol cols="12" sm="9">
            <div class="d-flex">
              <VSpacer style="min-inline-size: 40px;" />
              <AppSelect v-model="filters.itemsPerPage" :items="[10, 25, 50, 100]" style="flex-grow: 0 !important; max-inline-size: 100px;" class="mr-2" />
              <VBtn
                color="primary"
                prepend-icon="tabler-download"
                small
                variant="outlined"
                @click="exportExcel"
                :loading="isExporting"
              >
                Export
              </VBtn>
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

.flex-grow-1 {
  flex-grow: 0 !important;
}
</style>
