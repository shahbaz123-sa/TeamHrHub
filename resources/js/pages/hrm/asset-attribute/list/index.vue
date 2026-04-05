<script setup>
import AddNewAssetAttributeDrawer from "@/views/apps/hrm/asset-attribute/AddNewAssetAttributeDrawer.vue"
import { useToast } from "vue-toast-notification"
import "vue-toast-notification/dist/theme-sugar.css"

const $toast = useToast()
const searchQuery = ref("")
const assetAttributes = ref([])
const assetTypes = ref([])
const loading = ref(false)
const error = ref(null)
const isFormOpen = ref(false)
const currentAssetAttribute = ref(null)
const forView = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const accessToken = useCookie("accessToken")

// Headers
const headers = [
  { title: "Name", key: "name" },
  { title: "Asset Type", key: "asset_type_name" },
  { title: "Field Type", key: "field_type" },
  { title: "Options", key: "options" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
]

// Fetch asset attributes
const fetchAssetAttributes = async () => {
  loading.value = true
  try {
    const response = await $api("/asset-attributes")
    if (response.success) {
      assetAttributes.value = response.data || []
    } else {
      throw new Error(response.message || "Failed to load asset attributes")
    }
  } catch (err) {
    error.value = err
    $toast.error("Failed to load asset attributes")
  } finally {
    loading.value = false
  }
}

// Fetch asset types for dropdown
const fetchAssetTypes = async () => {
  try {
    const response = await $api("/asset-attributes/asset-types/list")
    if (response.success) {
      assetTypes.value = response.data || []
    } else {
      console.error("Failed to load asset types:", response.message)
    }
  } catch (err) {
    console.error("Failed to load asset types:", err)
  }
}

// Watch for search changes
watch(searchQuery, fetchAssetAttributes, { deep: true })

// Initial fetch
onMounted(() => {
  fetchAssetAttributes()
  fetchAssetTypes()
})

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-"
}

// Helper function to get field type color
const getFieldTypeColor = (fieldType) => {
  const colors = {
    string: 'primary',
    number: 'success',
    date: 'warning',
    boolean: 'info',
    select: 'secondary'
  }
  return colors[fieldType] || 'default'
}

// Helper function to display user-friendly field type names
const getFieldTypeDisplay = (fieldType) => {
  const displayNames = {
    string: 'Text',
    number: 'Number',
    date: 'Date',
    boolean: 'Boolean',
    select: 'Select'
  }
  return displayNames[fieldType] || fieldType
}

// Helper function to display options safely
const getOptionsDisplay = (options) => {
  if (!options) return "-"
  
  // If options is already an array (from API)
  if (Array.isArray(options)) {
    return options.join(', ')
  }
  
  // If options is a JSON string, try to parse it
  if (typeof options === 'string') {
    try {
      const parsed = JSON.parse(options)
      return Array.isArray(parsed) ? parsed.join(', ') : "-"
    } catch {
      return "-"
    }
  }
  
  return "-"
}

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true
  try {
    const method = formData.id ? "PUT" : "POST"
    const url = formData.id ? `/asset-attributes/${formData.id}` : "/asset-attributes"

    const payload = {
      name: formData.name,
      asset_type_id: formData.asset_type_id,
      field_type: formData.field_type,
      options: formData.options,
    }

    const response = await $api(url, {
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

    if (response.success) {
      await fetchAssetAttributes()
      isFormOpen.value = false
      $toast.success(response.message || "Asset attribute saved successfully!")
    } else {
      throw new Error(response.message || "Failed to save asset attribute")
    }
  } catch (err) {
    $toast.error(err.message || "Failed to save asset attribute")
  } finally {
    isSubmitting.value = false
  }
}

// Edit asset attribute
const editAssetAttribute = (assetAttribute) => {
  currentAssetAttribute.value = { ...assetAttribute }
  isFormOpen.value = true
  forView.value = false
}

// View asset attribute
const viewAssetAttribute = (assetAttribute) => {
  currentAssetAttribute.value = { ...assetAttribute }
  isFormOpen.value = true
  forView.value = true
}

// Delete asset attribute
const deleteAssetAttribute = async (id) => {
  try {
    isDeleting.value = true
    const response = await $api(`/asset-attributes/${id}`, { method: "DELETE" })
    
    if (response.success) {
      await fetchAssetAttributes()
      $toast.success(response.message || "Asset attribute deleted successfully!")
    } else {
      throw new Error(response.message || "Failed to delete asset attribute")
    }
  } catch (err) {
    $toast.error(err.message || "Failed to delete asset attribute")
  } finally {
    isDeleting.value = false
  }
}

// Open form for new asset attribute
const newAssetAttribute = () => {
  currentAssetAttribute.value = null
  isFormOpen.value = true
  forView.value = false
}
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Assets' }, { title: 'Asset Attributes' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search asset attributes"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('asset_attributes.create')" prepend-icon="tabler-plus" @click="newAssetAttribute">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading asset attributes...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load asset attributes" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="assetAttributes"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "L" }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base font-weight-medium text-high-emphasis">
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <template #item.asset_type_name="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ item.asset_type?.name || "-" }}
          </div>
        </template>

        <template #item.field_type="{ item }">
          <VChip
            :color="getFieldTypeColor(item.field_type)"
            size="small"
            variant="tonal"
          >
            {{ getFieldTypeDisplay(item.field_type) }}
          </VChip>
        </template>

        <template #item.options="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ getOptionsDisplay(item.options) }}
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
          <IconBtn v-if="hasPermission('asset_attributes.read')" @click="viewAssetAttribute(item)">
            <VIcon icon="tabler-eye" />
          </IconBtn>

          <IconBtn v-if="hasPermission('asset_attributes.update')" @click="editAssetAttribute(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('asset_attributes.delete')" @click="deleteAssetAttribute(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewAssetAttributeDrawer
      v-model:is-open="isFormOpen"
      :for-view="forView"
      :asset-attribute="currentAssetAttribute"
      :asset-types="assetTypes"
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
