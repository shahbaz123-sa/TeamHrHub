<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue';
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue';
import { formatLongText } from '@/utils/helpers/str';
import AddUpdateDrawer from '@/views/crm/product/graph-price/AddUpdateDrawer.vue';
import { onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const filters = ref({
  category: null,
  product: null,
  brand: null,
  unit: null,
  start_date: null,
  end_date: null,
  q: '',
  itemsPerPage: 10,
  page: 1
})

const items = ref([])
const totalItems = ref(0)
const loading = ref(false)

const isDrawerVisible = ref(false)
const editingItem = ref(null)

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const accessToken = useCookie('accessToken').value

var allCategories = []
var allBrands = []
var allProducts = []
var allUoms = []
const loadFilters = async () => {

  loadingCategories.value = true
  loadingProducts.value = true
  loadingBrands.value = true
  loadingUoms.value = true

  const res = await $api('/product/graph-prices/filters', {
    query: {},
    headers: { Authorization: `Bearer ${accessToken}` }
  })

  allCategories = await (res?.categories ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))
  allBrands = await (res?.brands ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))
  allProducts = await (res?.products ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))
  allUoms = await (res?.units ?? []).map(c => ({id: c, name: formatLongText(c, 30)}))

  await fetchCategories()
  await fetchProducts()
  await fetchBrands()
  await fetchUoms()

  loadingCategories.value = false
  loadingProducts.value = false
  loadingBrands.value = false
  loadingUoms.value = false
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

const products = ref([])
const loadingProducts = ref(false)
const fetchProducts = async (search = '') => {
  loadingProducts.value = true
  if(search)
  {
    products.value = await allProducts.filter(p => p.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    products.value = await [...allProducts]
  }
  loadingProducts.value = false
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

const uoms = ref([])
const loadingUoms = ref(false)
const fetchUoms = async (search = '') => {
  loadingUoms.value = true
  if(search)
  {
    uoms.value = await allUoms.filter(p => p.name.toLowerCase().includes(search.trim().toLowerCase()))
  } else {
    uoms.value = await [...allUoms]
  }
  loadingUoms.value = false
}

const router = useRouter()

const triggerImport = () => {
  router.push('/crm/product/graph-price/import')
}

const headers = [
  { title: 'Category', key: 'category_name' },
  { title: 'Product', key: 'product_name' },
  { title: 'Brand', key: 'brand_name' },
  { title: 'Datetime', key: 'datetime' },
  { title: 'Price', key: 'price' },
  { title: 'Market', key: 'market' },
  { title: 'Currency', key: 'currency' },
  { title: 'Unit', key: 'unit_name' },
  { title: 'Uploaded By', key: 'uploader.name' },
]

const fetchItems = async () => {
  loading.value = true
  const query = {
    q: filters.value.q,
    page: filters.value.page,
    per_page: filters.value.itemsPerPage,
    category_name: filters.value.category?.id ?? filters.value.category,
    product_name: filters.value.product?.id ?? filters.value.product,
    brand_name: filters.value.brand?.id ?? filters.value.brand,
    unit_name: filters.value.unit?.id ?? filters.value.unit,
    start_date: filters.value.start_date,
    end_date: filters.value.end_date,
  }

  const { data, meta } = await $api('/product/graph-prices', {
    query,
    method: "GET",
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  items.value = data
  totalItems.value = meta.total || 0
  loading.value = false
}

const saveItem = async (formData, id) => {
  loading.value = true
  try {
    if(id) {
      formData.append('_method', 'PUT')
      await $api(`/product/graph-prices/${id}`, {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    } else {
      await $api('/product/graph-prices', {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    }
    fetchItems()
    $toast.success('Graph price saved successfully')
    isDrawerVisible.value = false
  } catch (err) {
    let message = "Something went wrong!"
    if (err && err.status === 201) {
        fetchItems()
        $toast.success('Graph price saved successfully')
        isDrawerVisible.value = false
        return;
    }
    if (err && err.status === 422) {
        message = Object.values(err?._data?.errors).join("\n")
    }
    $toast.error(message)
  } finally {
    loading.value = false
  }
}

const askDelete = (id) => {
  deleteTargetId.value = id;
  isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
  loading.value = true
  try {
    await $api(`/product/graph-prices/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchItems()
    $toast.success('Graph price deleted!')
  } catch (e) {
    $toast.error('Failed to delete')
  } finally {
    isDeleteDialogOpen.value = false;
    deleteSubmitting.value = false;
    loading.value = false
    deleteTargetId.value = null;
  }
}

watch([
  filters,
], () => {
  fetchItems()
}, { deep: true })

onMounted(() => {
  fetchItems()
  loadFilters()
})
</script>

<template>
  <div>
    <!-- Filters -->
    <VCard title="Filters" class="mb-6">
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
              v-model="filters.product"
              placeholder="Product"
              :items="products"
              autocomplete
              :loading="loadingProducts"
              @update:search="fetchProducts"
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
              v-model="filters.unit"
              placeholder="Unit"
              :items="uoms"
              autocomplete
              :loading="loadingUoms"
              @update:search="fetchUoms"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" sm="3">
            <AppDateTimePicker v-model="filters.start_date" label="Start Date" clearable clear-icon="tabler-x" />
          </VCol>
          <VCol cols="12" sm="3">
            <AppDateTimePicker v-model="filters.end_date" label="End Date" clearable clear-icon="tabler-x" />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText>
        <VRow>
          <VCol cols="12" sm="3">
            <AppTextField v-model="filters.q" placeholder="Search graph prices" />
          </VCol>
          <VCol cols="12" sm="6" />
          <VCol cols="12" sm="3">
            <div class="d-flex flex-wrap align-center">
              <VSpacer style="min-inline-size: 40px;" />
              <AppSelect v-model="filters.itemsPerPage" :items="[5, 10, 20, 25, 50]" style="max-inline-size: 80px;" />
              <VBtn color="primary" prepend-icon="tabler-upload" small variant="outlined" @click="triggerImport">Import Prices</VBtn>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />
      
      <VDataTableServer
        v-model:items-per-page="filters.itemsPerPage"
        v-model:page="filters.page"
        :headers="headers"
        :items="items"
        :items-length="totalItems"
        :loading="loading"
        loading-text="Loading data..."
        class="text-no-wrap"
      >
        <template #bottom>
          <TablePagination v-model:page="filters.page" :items-per-page="filters.itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>
    </VCard>

    <AddUpdateDrawer v-model:is-drawer-open="isDrawerVisible" v-model:editing-graph-price="editingItem" @save="saveItem" />

    <ConfirmationDialog v-model="isDeleteDialogOpen" title="Are you sure" description="This action can not be undone. Do you want to continue?" cancel-text="No" confirm-text="Yes" :loading="deleteSubmitting" @confirm="confirmDelete" />
  </div>
</template>
