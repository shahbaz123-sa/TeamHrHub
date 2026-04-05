<script setup>
import { hasPermission } from "@/utils/permission"
import AddNewTicketCategoryDrawer from "@/views/apps/hrm/ticket-category/AddNewTicketCategoryDrawer.vue"

const searchQuery = ref("")
const ticketCategories = ref([])
const loading = ref(false)
const error = ref(null)
const isFormOpen = ref(false)
const currentTicketCategory = ref(null)
const forView = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const accessToken = useCookie("accessToken")

// Headers
const headers = [
  { title: "Name", key: "name" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Updated At", key: "updated_at" },
  { title: "Actions", key: "actions", sortable: false },
]

// Fetch ticket categorys
const fetchTicketCategories = async () => {
  loading.value = true
  try {
    const response = await $api("/ticket-categories")
    ticketCategories.value = response.data || []
  } catch (err) {
    error.value = err
  } finally {
    loading.value = false
  }
}

// Watch for search changes
watch(searchQuery, fetchTicketCategories, { deep: true })

// Initial fetch
onMounted(fetchTicketCategories)

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-"
}

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true
  try {
    const method = formData.id ? "PUT" : "POST"
    const url = formData.id
      ? `/ticket-categories/${formData.id}`
      : "/ticket-categories"

    const payload = {
      name: formData.name,
      description: formData.description,
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

    await fetchTicketCategories()
    isFormOpen.value = false
    $toast.success("Ticket category saved successfully!")
  } catch (err) {
    $toast.error("Failed to save ticket category")
  } finally {
    isSubmitting.value = false
  }
}

// Edit ticket category
const editTicketCategory = (ticketCategory) => {
  currentTicketCategory.value = { ...ticketCategory }
  isFormOpen.value = true
  forView.value = false
}

// View ticket category
const viewTicketCategory = (ticketCategory) => {
  currentTicketCategory.value = { ...ticketCategory }
  isFormOpen.value = true
  forView.value = true
}

// Delete ticket category
const deleteTicketCategory = async (id) => {
  try {
    isDeleting.value = true
    await $api(`/ticket-categories/${id}`, { method: "DELETE" })
    await fetchTicketCategories()
    $toast.success("Ticket category deleted successfully!")
  } catch (err) {
    $toast.error("Failed to delete ticket category")
  } finally {
    isDeleting.value = false
  }
}

// Open form for new ticket category
const newTicketCategory = () => {
  currentTicketCategory.value = null
  isFormOpen.value = true
  forView.value = false
}
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Ticket Category' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search ticket categorys"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('ticket_category.create')" prepend-icon="tabler-plus" @click="newTicketCategory">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading ticket categorys...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load ticket categorys" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="ticketCategories"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('ticket_category.update')" @click="editTicketCategory(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('ticket_category.delete')" @click="deleteTicketCategory(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewTicketCategoryDrawer
      v-model:is-open="isFormOpen"
      :for-view="forView"
      :ticket-category="currentTicketCategory"
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
