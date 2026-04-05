<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue';
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue';
import { hasPermission } from '@/utils/permission';
import AddUpdateDrawer from '@/views/crm/product/uom/AddUpdateDrawer.vue';
import { onMounted, ref, watch } from 'vue';

const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const selectedStatus = ref()

const uoms = ref([])
const totalUoms = ref(0)
const loading = ref(false)

const isUomDrawerVisible = ref(false)
const editingUom = ref(null)

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const accessToken = useCookie('accessToken').value

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Slug', key: 'slug' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const fetchUoms = async () => {
  loading.value = true
  const { data, meta } = await $api('/product/uoms', {
    query: {
      q: searchQuery.value,
      page: page.value,
      per_page: itemsPerPage.value,
      status: selectedStatus.value,
    },
    method: "GET",
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  uoms.value = data
  totalUoms.value = meta.total || 0
  loading.value = false
}

const openDrawer = (uom = null) => {
  editingUom.value = uom;
  isUomDrawerVisible.value = true
}

const saveUom = async (formData, id) => {
  loading.value = true
  try {

    if(id) {
      formData.append('_method', 'PUT')
      await $api(`/product/uoms/${id}`, {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    } else {
      await $api('/product/uoms', {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    }
    fetchUoms()
    $toast.success('Uom saved successfully')
    isUomDrawerVisible.value = false
  } catch (err) {
    let message = "Something went wrong!"
    // Handle validation errors
    if (err.response && err.response.status === 201) {
        fetchUoms()
        $toast.success('Uom saved successfully')
        isUomDrawerVisible.value = false
        return;
    }
    
    if (err.response && err.response.status === 422) {
        message = Object.values(err.response?._data?.errors).join("\n")
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
    await $api(`/product/uoms/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchUoms()
    $toast.success('Product uom deleted!')
  } catch (e) {
    $toast.error('Failed to delete uom')
  } finally {
    isDeleteDialogOpen.value = false;
    deleteSubmitting.value = false;
    loading.value = false
    deleteTargetId.value = null;
  }
}

watch([
  searchQuery,
  page,
  itemsPerPage,
  selectedStatus
], () => {
  fetchUoms()
})

onMounted(fetchUoms)
</script>

<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
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
            placeholder="Search Uom"
            style="max-inline-size: 290px; min-inline-size: 290px;"
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
              v-if="hasPermission('product_uom.create')"
              prepend-icon="tabler-plus"
              @click="openDrawer()"
            >
              Add Uom
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDivider />
  
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="uoms"
        :items-length="totalUoms"
        :loading="loading"
        loading-text="Loading data..."
        class="text-no-wrap"
      >      
        <template #item.is_active="{ item }">
          <VChip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('product_uom.update')" @click="openDrawer(item)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn v-if="hasPermission('product_uom.delete')" @click="askDelete(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalUoms" />
        </template>
      </VDataTableServer>
    </VCard>
  
    <AddUpdateDrawer
      v-model:is-drawer-open="isUomDrawerVisible"
      v-model:editing-uom="editingUom"
      @save="saveUom"
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
