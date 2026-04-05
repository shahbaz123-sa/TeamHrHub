<script setup>
import { hasPermission } from "@/utils/permission"
import AddNewHolidayDrawer from "@/views/apps/hrm/holiday/AddNewHolidayDrawer.vue"
import { useToast } from "vue-toast-notification"
import "vue-toast-notification/dist/theme-sugar.css"

const $toast = useToast()
const searchQuery = ref("")
const holidays = ref([])
const loading = ref(false)
const error = ref(null)
const isFormOpen = ref(false)
const currentHoliday = ref(null)
const forView = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const accessToken = useCookie("accessToken")

// Headers
const headers = [
  { title: "Name", key: "name" },
  { title: "Date", key: "date" },
  { title: "Recurring", key: "is_recurring" },
  { title: "Description", key: "description" },
  { title: "Created At", key: "created_at" },
  { title: "Actions", key: "actions", sortable: false },
]

// Fetch holidays
const fetchHolidays = async () => {
  loading.value = true
  try {
    const response = await $api("/holidays")
    holidays.value = response.data || []
  } catch (err) {
    error.value = err
    $toast.error("Failed to load holidays")
  } finally {
    loading.value = false
  }
}

// Watch for search changes
watch(searchQuery, fetchHolidays, { deep: true })

// Initial fetch
onMounted(fetchHolidays)

const formatDate = (dateString) => {
  return dateString ? new Date(dateString).toISOString().split("T")[0] : "-"
}

const truncateText = (text, maxLength = 50) => {
  if (!text) return "-"
  return text.length > maxLength ? text.substring(0, maxLength) + "..." : text
}

// Handle form submission
const handleSubmit = async (formData) => {
  isSubmitting.value = true
  try {
    const method = formData.id ? "PUT" : "POST"
    const url = formData.id ? `/holidays/${formData.id}` : "/holidays"

    const payload = {
      name: formData.name,
      date: formData.date,
      is_recurring: formData.is_recurring,
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

    await fetchHolidays()
    isFormOpen.value = false
    $toast.success("Holiday saved successfully!")
  } catch (err) {
    $toast.error(err.response.data && "Failed to save holiday")
  } finally {
    isSubmitting.value = false
  }
}

// Edit holiday
const editHoliday = (holiday) => {
  currentHoliday.value = { ...holiday }
  isFormOpen.value = true
  forView.value = false
}

// View holiday
const viewHoliday = (holiday) => {
  currentHoliday.value = { ...holiday }
  isFormOpen.value = true
  forView.value = true
}

// Delete holiday
const deleteHoliday = async (id) => {
  try {
    isDeleting.value = true
    await $api(`/holidays/${id}`, { method: "DELETE" })
    await fetchHolidays()
    $toast.success("Holiday deleted successfully!")
  } catch (err) {
    $toast.error("Failed to delete holiday")
  } finally {
    isDeleting.value = false
  }
}

// Open form for new holiday
const newHoliday = () => {
  currentHoliday.value = null
  isFormOpen.value = true
  forView.value = false
}
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'HR Administration' }, { title: 'Holidays' }]"
    />
    <VCard>
      <VCardText class="d-flex flex-wrap gap-4">
        <VSpacer />
        <div class="d-flex align-center flex-wrap gap-4">
          <AppTextField
            v-model="searchQuery"
            placeholder="Search holidays"
            style="inline-size: 15.625rem;"
          />
          <VBtn v-if="hasPermission('holidays.create')" prepend-icon="tabler-plus" @click="newHoliday">
            Add New
          </VBtn>
        </div>
      </VCardText>

      <VDivider />

      <div v-if="loading" class="text-center pa-4">
        <VProgressCircular indeterminate />
        <p>Loading holidays...</p>
      </div>

      <VAlert v-else-if="error" type="error" variant="tonal" class="ma-4">
        {{ error.message || "Failed to load holidays" }}
      </VAlert>

      <VDataTable
        v-else
        :headers="headers"
        :items="holidays"
        :search="searchQuery"
        class="text-no-wrap"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" variant="tonal" color="primary">
              <span>{{ item.name?.charAt(0)?.toUpperCase() || "H" }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base font-weight-medium text-high-emphasis">
                {{ item.name }}
              </h6>
            </div>
          </div>
        </template>

        <template #item.date="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ formatDate(item.date) }}
          </div>
        </template>

        <template #item.is_recurring="{ item }">
          <VChip
            :color="item.is_recurring ? 'success' : 'default'"
            size="small"
            variant="tonal"
          >
            {{ item.is_recurring ? 'Yes' : 'No' }}
          </VChip>
        </template>

        <template #item.description="{ item }">
          <div class="text-high-emphasis text-body-1">
            <VTooltip v-if="item.description && item.description.length > 50" :text="item.description">
              <template #activator="{ props }">
                <span v-bind="props">{{ truncateText(item.description) }}</span>
              </template>
            </VTooltip>
            <span v-else>{{ truncateText(item.description) }}</span>
          </div>
        </template>

        <template #item.created_at="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ formatDate(item.created_at) }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <IconBtn v-if="hasPermission('holidays.update')" @click="editHoliday(item)">
            <VIcon icon="tabler-pencil" />
          </IconBtn>

          <IconBtn v-if="hasPermission('holidays.delete')" @click="deleteHoliday(item.id)" :loading="isDeleting">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </VDataTable>
    </VCard>

    <AddNewHolidayDrawer
      v-model:is-open="isFormOpen"
      :for-view="forView"
      :holiday="currentHoliday"
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
