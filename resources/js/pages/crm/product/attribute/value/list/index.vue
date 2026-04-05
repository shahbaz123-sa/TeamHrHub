<script setup>
import AppAutocomplete from '@/components/AppAutocomplete.vue'
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue'
import AddUpdateDrawer from '@/views/crm/product/attribute/value/AddUpdateDrawer.vue'
import { onMounted, ref } from 'vue'

const headers = [
  {
    title: 'Attribute',
    key: 'attribute.name'
  },  
  {
    title: 'Value',
    key: 'name',
  },
  {
    title: 'Slug',
    key: 'slug'
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
const selectedStatus = ref()

const attributeValues = ref([])
const totalAttributeValues = ref(0)
const loading = ref(false)

const selectedAttribute = ref(null)
const attributes = ref([])
const drawerAttributes = ref([])
const loadingAttributes = ref(false)

const isAttributeValueDrawerVisible = ref(false)
const editingAttributeValue = ref(null)

const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const accessToken = useCookie('accessToken').value

const fetchAttributeValues = async () => {
  loading.value = true
  const { data, meta } = await $api('/product/attribute/values', {
    query: {
      q: searchQuery.value,
      attribute_id: selectedAttribute.value,
      page: page.value,
      per_page: itemsPerPage.value,
      status: selectedStatus.value,
    },
    method: "GET",
    headers: { Authorization: `Bearer ${accessToken}` }
  })
  attributeValues.value = data
  totalAttributeValues.value = meta.total || 0
  loading.value = false
}

const openDrawer = (attributeValue = null) => {
  editingAttributeValue.value = attributeValue;
  isAttributeValueDrawerVisible.value = true
}

const askDelete = (id) => {
  deleteTargetId.value = id;
  isDeleteDialogOpen.value = true;
};

const confirmDelete = async () => {
  loading.value = true
  try {
    await $api(`/product/attribute/values/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchAttributeValues()
    $toast.success('Product attribute value deleted!')
  } catch (e) {
    $toast.error('Failed to delete attribute value')
  } finally {
    isDeleteDialogOpen.value = false;
    deleteSubmitting.value = false;
    loading.value = false
    deleteTargetId.value = null;
  }
}

const fetchAttributes = async (search = '', forDrawer = false) => {
  loadingAttributes.value = !forDrawer

  const { data } = await useApi(
    createUrl("/product/attributes", {
      query: {
        q: search,
        per_page: -1,
        for_attachment: 1,
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
  
  if(forDrawer) {
    drawerAttributes.value = await data.value.data.map(attribute => ({ id: attribute.id, name: attribute.name }))
  } else {
    attributes.value = await data.value.data.map(attribute => ({ id: attribute.id, name: attribute.name }))
  }

  loadingAttributes.value = false
}

const saveAttributeValue = async (formData, id) => {
  loading.value = true
  try {

    if(id) {
      formData.append('_method', 'PUT')
      await $api(`/product/attribute/values/${id}`, {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    } else {
      await $api('/product/attribute/values', {
        method: 'POST',
        body: formData,
        headers: { Authorization: `Bearer ${accessToken}` }
      })
    }
    fetchAttributeValues()
    $toast.success('Attribute value saved successfully')
    isAttributeValueDrawerVisible.value = false
  } catch (err) {
    let message = "Something went wrong!"
    // Handle validation errors
    if (err.response && err.response.status === 201) {
        fetchAttributeValues()
        $toast.success('Attribute value saved successfully')
        isAttributeValueDrawerVisible.value = false
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
  itemsPerPage,
  selectedStatus,
  selectedAttribute
], () => {
  fetchAttributeValues()
})

onMounted(fetchAttributeValues)
onMounted(fetchAttributes)

</script>

<template>
  <div>
    <VCard>
      <VCardText>
        <VRow>
          <VCol cols="12" md="3" sm="12">
            <AppAutocomplete
              v-model="selectedAttribute"
              :items="attributes"
              :loading="loadingAttributes"
              @update:search="fetchAttributes"
              placeholder="Choose Attribute"
              autocomplete
              clearable
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

          <div class="d-flex gap-x-5">
            <AppTextField
              v-model="searchQuery"
              placeholder="Search Attribute Value"
              style="max-inline-size: 290px; min-inline-size: 290px;"
            />
          </div>

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
              v-if="hasPermission('product_attribute_value.create')"
              prepend-icon="tabler-plus"
              @click="openDrawer()"
            >
              Add Attribute Value
            </VBtn>
          </div>
        </div>
      </VCardText>

      <VDivider />

      <div class="attribute_value-table">
        <VDataTableServer
          v-model:items-per-page="itemsPerPage"
          v-model:page="page"
          :headers="headers"
          :items="attributeValues"
          :items-length="totalAttributeValues"
          :search="searchQuery"
          :loading="loading"
          loading-text="Loading attribute values..."
          class="text-no-wrap"
        >

          <template #item.actions="{ item }">
            <IconBtn v-if="hasPermission('product_attribute_value.update')" @click="openDrawer(item)">
              <VIcon icon="tabler-edit" />
            </IconBtn>
            <IconBtn v-if="hasPermission('product_attribute_value.delete')" @click="askDelete(item.id)">
              <VIcon icon="tabler-trash" />
            </IconBtn>
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
              :total-items="totalAttributeValues"
            />
          </template>
        </VDataTableServer>
      </div>
    </VCard>

    <AddUpdateDrawer
      v-model:is-drawer-open="isAttributeValueDrawerVisible"
      v-model:editing-attribute-value="editingAttributeValue"
      :fetching-attributes="fetchAttributes"
      :attributes="drawerAttributes"
      @save="saveAttributeValue"
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
