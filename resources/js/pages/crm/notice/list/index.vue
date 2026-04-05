<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue';
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue';
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue';
import { hasPermission } from '@/utils/permission';
import AddUpdateDrawer from '@/views/crm/notice/AddUpdateDrawer.vue';
import { onMounted, ref, watch } from 'vue';

const searchQuery = ref('')
const itemsPerPage = ref(10)
const page = ref(1)
const selectedStatus = ref()
const selectedType = ref()

const items = ref([])
const totalItems = ref(0)
const loading = ref(false)

const isDrawerVisible = ref(false)
const editingItem = ref(null)

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const accessToken = useCookie('accessToken').value

const headers = [
  { title: 'Title', key: 'title' },
  { title: 'Type', key: 'type.title' },
  { title: 'Year', key: 'year' },
  { title: 'PDF', key: 'pdf_attachment' },
  { title: 'Excel', key: 'excel_attachment' },
  { title: 'Active', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
]

const types = ref([])

const fetchTypes = async () => {
  try {
    const { data } = await $api('/notice/types', {
      query: {
        per_page: -1,
      },
      method: 'GET',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    types.value = data.map(t => ({ id: t.id, name: t.title }))
  } catch (e) {
    // ignore
  }
}

const fetchItems = async () => {
  loading.value = true
  const { data, meta } = await $api('/notices', {
    query: {
      q: searchQuery.value,
      page: page.value,
      per_page: itemsPerPage.value,
      status: selectedStatus.value,
      type: selectedType.value,
    },
    method: "GET",
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  items.value = data
  totalItems.value = meta.total || 0
  loading.value = false
}

const openDrawer = (item = null) => {
  editingItem.value = item;
  isDrawerVisible.value = true
}

const saveItem = async (formData, id) => {
  loading.value = true
  try {
    if(id) {
      formData.append('_method', 'PUT')
      await $api(`/notices/${id}`, {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    } else {
      await $api('/notices', {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    }
    fetchItems()
    $toast.success('Notice saved successfully')
    isDrawerVisible.value = false
  } catch (err) {
    let message = "Something went wrong!"
    if (err && err.status === 201) {
        fetchItems()
        $toast.success('Notice saved successfully')
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
    await $api(`/notices/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchItems()
    $toast.success('Notice deleted!')
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
  searchQuery,
  page,
  itemsPerPage,
  selectedStatus,
  selectedType
], () => {
  fetchItems()
})

onMounted(async () => {
  fetchTypes()
  fetchItems()
})
</script>

<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="3">
            <AppAutocomplete
              v-model="selectedType"
              :items="types"
              autocomplete
              placeholder="Choose Type"
              clearable
            />
          </VCol>
          
          <VCol cols="3">
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
            placeholder="Search Notice"
            style="max-inline-size: 280px; min-inline-size: 280px;"
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
              v-if="hasPermission('notice.create')"
              prepend-icon="tabler-plus"
              @click="openDrawer()"
            >
              Add Notice
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDivider />
  
      <VDataTableServer
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="items"
        :items-length="totalItems"
        :loading="loading"
        loading-text="Loading data..."
        class="text-no-wrap"
      >
        <template #item.pdf_attachment="{ item }">
          <div class="d-flex align-center gap-2">
            <DocumentImageViewer v-if="item.pdf_attachment" :src="item.pdf_attachment" :pdf-title="item.title">
              <template #icon></template>
            </DocumentImageViewer>
            <VBtn size="small" v-if="item.pdf_attachment" :href="item.pdf_attachment" icon download :title="'Download'">
              <VIcon icon="tabler-download" />
            </VBtn>
            <span v-if="!item.pdf_attachment">-</span>
          </div>
        </template>

        <template #item.excel_attachment="{ item }">
          <div class="d-flex align-center gap-2">
            <VBtn size="small" v-if="item.excel_attachment" :href="item.excel_attachment" icon download :title="'Download'">
              <VIcon icon="tabler-download" />
            </VBtn>
            <span v-if="!item.excel_attachment">-</span>
          </div>
        </template>

        <template #item.year="{ item }">
          {{ item.year }}
        </template>
        
        <template #item.is_active="{ item }">
          <VChip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </VChip>
        </template>
        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('notice.update')" @click="openDrawer(item)">
            <VIcon icon="tabler-edit" />
          </IconBtn>
          <IconBtn v-if="hasPermission('notice.delete')" @click="askDelete(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalItems" />
        </template>
      </VDataTableServer>
    </VCard>

    <AddUpdateDrawer
      v-model:is-drawer-open="isDrawerVisible"
      v-model:editing-notice="editingItem"
      @save="saveItem"
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
