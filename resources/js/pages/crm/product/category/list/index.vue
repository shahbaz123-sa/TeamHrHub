<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue'
import { formatLongText, stripHtml } from '@/utils/helpers/str'
import AddUpdateDrawer from '@/views/crm/product/category/AddUpdateDrawer.vue'
import { onMounted, ref, watch } from 'vue'

const headers = [
  {
    title: 'Category',
    key: 'name',
  },
  {
    title: 'Slug',
    key: 'slug'
  },
  {
    title: 'Parent',
    key: 'parent.name'
  },
  {
    title: 'Active',
    key: 'is_active'
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

const itemsPerPage = ref(10)
const page = ref(1)
const searchQuery = ref('')
const parentCategory = ref()
const subCategory = ref()
const selectedStatus = ref()

const categories = ref([])
const totalCategories = ref(0)
const loading = ref(false)

const isCategoryDrawerVisible = ref(false)
const editingCategory = ref(null)

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const accessToken = useCookie('accessToken').value

const fetchCategories = async () => {
  loading.value = true
  const { data, meta } = await $api('/product/categories', {
    query: {
      q: searchQuery.value,
      page: page.value,
      per_page: itemsPerPage.value,
      parent_id: parentCategory.value,
      id: subCategory.value,
      status: selectedStatus.value
    },
    method: "GET",
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  categories.value = data
  totalCategories.value = meta.total || 0
  loading.value = false
}

const loadingParentCategories = ref(false)
const parentCategories = ref([])
const fetchParentCategories = async (search = '') => {
  loadingParentCategories.value = true

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
  
  parentCategories.value = await data.value.data.map(category => ({ id: category.id, name: formatLongText(category.name) }))
  loadingParentCategories.value = false
}

const subCategories = ref([])
const loadingSubCategories = ref(false)
const fetchSubCategories = async (parentCategoryId, search = '') => {
  if (!parentCategoryId) {
    subCategories.value = []
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

const openDrawer = (category = null) => {
  editingCategory.value = category;
  isCategoryDrawerVisible.value = true
}

const askDelete = (id) => {
  deleteTargetId.value = id;
  isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
  loading.value = true
  try {
    await $api(`/product/categories/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchCategories()
    $toast.success('Product category deleted!')
  } catch (e) {
    $toast.error('Failed to delete category')
  } finally {
    isDeleteDialogOpen.value = false;
    deleteSubmitting.value = false;
    loading.value = false
    deleteTargetId.value = null;
  }
}

const saveCategory = async (formData, id) => {
  loading.value = true
  try {

    if(id) {
      formData.append('_method', 'PUT')
      await $api(`/product/categories/${id}`, {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    } else {
      await $api('/product/categories', {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    }
    fetchCategories()
    $toast.success('Category saved successfully')
    isCategoryDrawerVisible.value = false
  } catch (err) {
    let message = "Something went wrong!"
    if (err.response && err.response.status === 201) {
        fetchCategories()
        $toast.success('Category saved successfully')
        isCategoryDrawerVisible.value = false
        return
    }
    
    if (err.response && err.response.status === 422) {
        message = Object.values(err.response?._data?.errors).join("\n")
    }

    $toast.error(message)
  } finally {
    loading.value = false
  }
}

watch([
  searchQuery,
  page,
  parentCategory,
  subCategory,
  itemsPerPage,
  selectedStatus
], () => {
  fetchCategories()
})

watch(parentCategory, (newCategory) => {
  fetchSubCategories(newCategory)
})

onMounted(() => {
  fetchCategories()
  fetchParentCategories()
})

</script>

<template>
  <div>
  
  <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="3" sm="12">
            <AppAutocomplete
              v-model="parentCategory"
              :items="parentCategories"
              autocomplete
              :loading="loadingParentCategories"
              @update:search="fetchParentCategories"
              placeholder="Choose Parent"
              clearable
            />
          </VCol>
          <VCol cols="12" md="3" sm="12">
            <AppAutocomplete
              v-model="subCategory"
              placeholder="Sub Category"
              :items="subCategories"
              autocomplete
              :loading="loadingSubCategories"
              @update:search="(search) => fetchSubCategories(parentCategory, search)"
              clearable
              :disabled="!parentCategory"
            />
          </VCol>
          <VCol cols="12" md="3" sm="12">
            <AppAutocomplete
              v-model="selectedStatus"
              :items="[{id: 1, name: 'Active'}, {id: 0, name: 'Inactive'}]"
              autocomplete
              placeholder="Choose Status"
              clearable
            />
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardText>
        <div class="d-flex justify-sm-space-between flex-wrap gap-y-4 gap-x-6 justify-start">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search Category"
            style="max-inline-size: 290px; min-inline-size: 290px;"
            clearable
          />

          <div class="d-flex align-center flex-wrap gap-4">
            <AppSelect
              v-model="itemsPerPage"
              :items="[
                { value: 5, title: '5' },
                { value: 10, title: '10' },
                { value: 20, title: '20' },
                { value: 50, title: '50' },
                { value: -1, title: 'All' },
              ]"
              style="max-inline-size: 100px; min-inline-size: 100px;"
            />
            <VBtn
              v-if="hasPermission('product_category.create')"
              prepend-icon="tabler-plus"
              @click="openDrawer()"
            >
              Add Category
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDivider />

      <div class="category-table">
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :headers="headers"
          :items="categories"
          :items-length="totalCategories"
          :search="searchQuery"
          :loading="loading"
          loading-text="Loading categories..."
          class="text-no-wrap"
        >

          <template #item.actions="{ item }">
            <IconBtn v-if="hasPermission('product_category.update')" @click="openDrawer(item)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn v-if="hasPermission('product_category.delete')" @click="askDelete(item.id)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
          </template>

          <template #item.name="{ item }">
            <div class="d-flex gap-x-3 align-center">
              <DocumentImageViewer v-if="item.image" :type="'avatar'" :src="item?.image" :pdf-title="item.name" />
              <div>
                <h6 class="text-h6">
                  {{ item.name }}
                </h6>
                <div class="text-body-2">
                  {{ formatLongText(stripHtml(item.description)) }}
                </div>
              </div>
            </div>
          </template>

          <template #item.is_active="{ item }">
            <VChip :color="item.is_active ? 'success' : 'error'" size="small">
              {{ item.is_active ? 'Active' : 'Inactive' }}
            </VChip>
          </template>

          <template #bottom>
            <TablePagination
              v-model:page="page"
              :items-per-page="itemsPerPage"
              :total-items="totalCategories"
            />
          </template>
        </VDataTableServer>
      </div>
    </VCard>
    
    <AddUpdateDrawer
      v-model:is-drawer-open="isCategoryDrawerVisible"
      v-model:editing-category="editingCategory"
      @save="saveCategory"
    />

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
