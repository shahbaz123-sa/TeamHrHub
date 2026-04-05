<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue'
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue'
import { formatLongText, stripHtml } from '@/utils/helpers/str'
import { hasPermission } from '@/utils/permission'
import { onMounted, watch, computed } from 'vue'

const headers = [
  {
    title: 'SKU',
    key: 'sku',
  },
  {
    title: 'Slug',
    key: 'slug',
  },
  {
    title: 'Product',
    key: 'name',
  },
  {
    title: 'Brand',
    key: 'brand.name',
  },
  {
    title: 'Category',
    key: 'category.name',
  },
  {
    title: 'Type',
    key: 'type',
  },
  {
    title: 'Uom',
    key: 'uom.name',
  },
  {
    title: 'Base Price',
    key: 'price',
  },
  {
    title: 'Stock',
    key: 'stock',
    sortable: false,
  },
  {
    title: 'QTY',
    key: 'quantity',
  },
  {
    title: 'Min Qty',
    key: 'min_quantity',
  },
  {
    title: 'Max Qty',
    key: 'max_quantity',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const products = ref([])
const totalProducts = ref(0)
const dataTableLoading = ref(false)

const parentCategory = ref(null)
const filters = ref({
  product_parent: null,
  status: null,
  sub_category: null,
  brand: null,
  stock: null,
  type: null,
  q: '',
})

const status = ref([
  {
    title: 'Publish',
    value: 'publish',
  },
  {
    title: 'Inactive',
    value: 'inactive',
  },
])

const stockStatus = ref([
  {
    title: 'In Stock',
    value: 'instock',
  },
  {
    title: 'Out of Stock',
    value: 'outofstock',
  },
])

const types = ref([
  {
    title: 'Simple',
    value: 'simple',
  },
  {
    title: 'Variable',
    value: 'variable',
  },
])

const accessToken = useCookie('accessToken').value

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const resolveStatus = statusMsg => {
  if (statusMsg === 'publish')
    return {
      text: 'Publish',
      color: 'success',
    }
  if (statusMsg === 'inactive')
    return {
      text: 'Inactive',
      color: 'error',
    }
}

const fetchProducts = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/products', {
    query: {
      ...filters.value,
      page,
      per_page: itemsPerPage,
      sort_by: sortBy,
      order_by: orderBy,
    },
  }))

  products.value = data.value?.data ?? []
  totalProducts.value = data.value?.meta?.total || 0

  dataTableLoading.value = false
}

const categories = ref([])
const loadingCategories = ref(false)
const fetchCategories = async (search = '') => {
  loadingCategories.value = true

  const { data } = await useApi(
    createUrl("/product/category/parents", {
      query: {
        q: search,
        per_page: -1,
        sort_by: 'name',
        order_by: 'asc'
      }
    }),
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    }
  );
  
  categories.value = await data.value.data.map(category => ({ id: category.id, name: formatLongText(category.name) }))
  loadingCategories.value = false
}

const subCategories = ref([])
const loadingSubCategories = ref(false)
const fetchSubCategories = async (parentCategoryId, search = '') => {
  if (!parentCategoryId) {
    subCategories.value = []
    filters.value.sub_category = null
    return
  }

  loadingSubCategories.value = true
  try {
    const { data } = await useApi(
      createUrl("/product/categories", {
        query: {
          q: search,
          parent_id: parentCategoryId,
          per_page: -1,
          sort_by: 'name',
          order_by: 'asc'
        }
      }),
      {
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      }
    );
    
    subCategories.value = await data.value.data.map(category => ({ id: category.id, name: formatLongText(category.name) }))
  } catch (error) {
    console.error('Failed to fetch sub-categories', error)
    subCategories.value = []
  } finally {
    loadingSubCategories.value = false
  }
}

const brands = ref([])
const loadingBrands = ref(false)
const fetchBrands = async (search = '') => {
  loadingBrands.value = true
  const { data } = await useApi(
    createUrl("/product/brands", {
      query: {
        q: search,
        per_page: -1,
        status: 1,
        sort_by: 'name',
        order_by: 'asc'
      }
    }),
    {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    }
  );
  brands.value = await data.value.data.map(brand => ({ id: brand.id, name: formatLongText(brand.name, 25) }))
  loadingBrands.value = false
}

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const askDelete = (id) => {
  deleteTargetId.value = id;
  isDeleteDialogOpen.value = true;
}

const confirmDelete = async () => {
  deleteSubmitting.value = true
  try {
    await $api(`/products/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchProducts()
    $toast.success('Product deleted!')
  } catch (e) {
    $toast.error('Failed to delete')
  } finally {
    isDeleteDialogOpen.value = false;
    deleteSubmitting.value = false;
    deleteTargetId.value = null;
  }
}

const filterKey = computed(() => ({
  ...filters.value,
  page: page.value,
  itemsPerPage: itemsPerPage.value,
  sortBy: sortBy.value,
}))

watch([filterKey], () => {
  if(dataTableLoading.value) return
  fetchProducts()
}, {deep: true})

watch(parentCategory, (newCategory) => {
  fetchSubCategories(newCategory)
})

onMounted(() => {
  fetchProducts()
  fetchCategories()
  fetchBrands()
})
</script>

<template>
  <div>

    <!-- 👉 products -->
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          
          <!-- 👉 Select Category -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppAutocomplete
              v-model="parentCategory"
              placeholder="Category"
              :items="categories"
              autocomplete
              :loading="loadingCategories"
              @update:search="fetchCategories"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

          <!-- 👉 Select Sub Category -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppAutocomplete
              v-model="filters.sub_category"
              placeholder="Sub Category"
              :items="subCategories"
              autocomplete
              :loading="loadingSubCategories"
              @update:search="(search) => fetchSubCategories(parentCategory, search)"
              clearable
              clear-icon="tabler-x"
              :disabled="!parentCategory"
            />
          </VCol>
          
          <!-- 👉 Select Brand -->
          <VCol
            cols="12"
            sm="3"
          >
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

          <!-- 👉 Select Status -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="filters.status"
              placeholder="Status"
              :items="status"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <!-- 👉 Select Type -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="filters.type"
              placeholder="Type"
              :items="types"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

          <!-- 👉 Select Stock Status -->
          <VCol
            cols="12"
            sm="2"
          >
            <AppSelect
              v-model="filters.stock"
              placeholder="Stock"
              :items="stockStatus"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText>
        <VRow>
          <VCol
            cols="12"
            sm="3"
          >
            <AppTextField
              v-model="filters.q"
              placeholder="Search Product"
            />
          </VCol>
          <VCol cols="12" sm="6" />
          <VCol
            cols="12"
            sm="3"
          >
          <div class="d-flex flex-wrap align-center">
              <VSpacer style="min-inline-size: 40px;" />
              <AppSelect
                v-model="itemsPerPage"
                :items="[5, 10, 20, 25, 50]"
                style="max-inline-size: 80px;"
              />
              <!-- 👉 Export button -->
              <!-- <VBtn
                variant="tonal"
                color="secondary"
                prepend-icon="tabler-upload"
              >
                Export
              </VBtn> -->

              <VBtn
                color="primary"
                prepend-icon="tabler-plus"
                :to="{ name: 'crm-product-add' }"
              >
                Add Product
              </VBtn>
            </div>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider class="mt-4" />

      <!-- 👉 Datatable  -->
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="products"
        :items-length="totalProducts"
        :loading="dataTableLoading"
        :loading-text="'Loading products...'"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <!-- product  -->
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <DocumentImageViewer v-if="item.image" :type="'avatar'" :src="item?.image?.src" :pdf-title="item.name" />
            <div class="d-flex flex-column">
              <span class="text-body-1 font-weight-medium text-high-emphasis">{{ item.name }}</span>
              <span class="text-body-2">{{ formatLongText(stripHtml(item.short_description)) }}</span>
            </div>
          </div>
        </template>

        <!-- category -->
        <template #item.category="{ item }">
          <span class="text-body-1 text-high-emphasis">{{ item.category }}</span>
        </template>

        <!-- stock -->
        <template #item.stock="{ item }">
          <VChip
            v-bind="item.stock_status === 'instock'
              ? { text: 'In Stock', color: 'success' }
              : { text: 'Out of Stock', color: 'error' }"
            density="default"
            label
            size="small"
          />
        </template>

        <!-- status -->
        <template #item.status="{ item }">
          <VChip
            v-bind="resolveStatus(item.status)"
            density="default"
            label
            size="small"
          />
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('product.read')" :to="{
            name: 'crm-product-details-id',
            params: { id: item.id },
          }">
            <VIcon icon="tabler-eye" />
          </IconBtn>
          <IconBtn v-if="hasPermission('product.update')" :to="{
            name: 'crm-product-edit-id',
            params: { id: item.id },
          }">
            <VIcon icon="tabler-edit" />
          </IconBtn>

          <IconBtn v-if="hasPermission('product.delete')" @click="askDelete(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>

        </template>

        <!-- pagination -->
        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalProducts"
          />
        </template>
      </VDataTableServer>
    </VCard>

    <ConfirmationDialog
      v-model="isDeleteDialogOpen"
      title="Are you sure"
      description="This action can not be undone. Do you want to continue?"
      cancel-text="No"
      confirm-text="Yes"
      :loading="deleteSubmitting"
      @confirm="confirmDelete"
    />

  </div>
</template>
