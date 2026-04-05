<script setup>
import { hasPermission } from "@/utils/permission"
import AddNewAssetTypeDrawer from "@/views/apps/hrm/asset-type/AddNewAssetTypeDrawer.vue"
import { useToast } from "vue-toast-notification"
import "vue-toast-notification/dist/theme-sugar.css"

const $toast = useToast()
const searchQuery = ref("")
const assetTypes = ref([])
const loading = ref(false)
const error = ref(null)
const isFormOpen = ref(false)
const currentAssetType = ref(null)
const forView = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const accessToken = useCookie("accessToken")

// Headers
const headers = [
  { title: "Type Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
]

// Fetch asset types
const fetchAssetTypes = async () => {
  loading.value = true
  try {
    const response = await $api("/asset-types")
    assetTypes.value = response.data || []
  } catch (err) {
    error.value = err
    $toast.error("Failed to load asset types")
  } finally {
    loading.value = false
  }
}

// Watch for search changes
watch(searchQuery, fetchAssetTypes, { deep: true })

// Initial fetch
onMounted(fetchAssetTypes)

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-"
}

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true
  try {
    const method = formData.id ? "PUT" : "POST"
    const url = formData.id ? `/asset-types/${formData.id}` : "/asset-types"

    const payload = {
      name: formData.name,
      description: formData.description,
    }

    await $api(url, {
      method,
      body: JSON.stringify(payload),
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        Authorization: `Bearer ${accessToken.value}`,
      },
      withCredentials: true,
    })

    await fetchAssetTypes()
    isFormOpen.value = false
    $toast.success("Asset type saved successfully!")
  } catch (err) {
    $toast.error(err.response.data && "Failed to save asset type")
  } finally {
    isSubmitting.value = false
  }
}

// Edit asset type
const editAssetType = (assetType) => {
  currentAssetType.value = { ...assetType }
  isFormOpen.value = true
  forView.value = false
}

// Edit asset type
const viewAssetType = (assetType) => {
  currentAssetType.value = { ...assetType }
  isFormOpen.value = true
  forView.value = true
}

// Delete asset type
const deleteAssetType = async (id) => {
  try {
    isDeleting.value = true
    await $api(`/asset-types/${id}`, { method: "DELETE" })
    await fetchAssetTypes()
    $toast.success("Asset type deleted successfully!")
  } catch (err) {
    $toast.error("Failed to delete asset type")
  } finally {
    isDeleting.value = false
  }
}

// Open form for new asset type
const newAssetType = () => {
  currentAssetType.value = null
  isFormOpen.value = true
  forView.value = false
}
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Assets' }, { title: 'Asset Types' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search asset types"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('asset_type.create')" prepend-icon="tabler-plus" @click="newAssetType">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading asset types...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load asset types" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="assetTypes"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <h6 class="text-base font-weight-medium text-high-emphasis">
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <template #item.description="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.description || "-" }}
          </div>
        </template>

        <template #item.created_at="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ formatDate(item.created_at) }}
          </div>
        </template>
        <template #item.updated_at="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ formatDate(item.updated_at) }}
          </div>
        </template>

        <template #item.actions="{ item }">

          <IconBtn v-if="hasPermission('asset_type.update')" @click="editAssetType(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('asset_type.delete')" @click="deleteAssetType(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewAssetTypeDrawer
      v-model:is-open="isFormOpen"
      :for-view="forView"
      :asset-type="currentAssetType"
      :loading="isSubmitting"
      @submit="handleSubmit"
    />
  </section>
</template>

<style lang="scss">
.text-capitalize {
  text-transform: capitalize;
}
</style>
