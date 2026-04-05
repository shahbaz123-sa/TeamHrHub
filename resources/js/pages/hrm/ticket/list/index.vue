<script setup>
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue'
import { hasPermission } from '@/utils/permission'
import TicketDialog from "@/views/apps/hrm/ticket/TicketDialog.vue"
import { range } from '@/views/demos/forms/form-elements/date-time-picker/demoCodeDateTimePicker'
import DocumentImageViewer from "@/components/common/DocumentImageViewer.vue";

const searchQuery = ref("")
const selectedStatus = ref(null)
const selectedMonth = ref(new Date().toISOString().slice(0, 7))
const itemsPerPage = ref(10)
const sortBy = ref()
const orderBy = ref()
const page = ref(1)
const statsType = ref("Monthly")
const stats = ref([])
const selectedDepartment = ref()
const dateRange = ref([]);
const departments = ref([])

const tickets = ref([])
const ticketsData = ref([])
const totalTickets = ref(0)
const loading = ref(false)
const error = ref(null)
const accessToken = useCookie("accessToken")

const route = useRoute();
const initialStatus = route.query.status ?? null;
selectedStatus.value = initialStatus;

const headers = [
  { title: "Assigned By", key: "employee.name" },
  { title: "TICKET CODE", key: "ticket_code" },
  { title: "DEPARTMENT", key: "department.name" },
  { title: "Assigned To", key: "poc.name" },
  { title: "START DATE", key: "start_date" },
  { title: "STATUS", key: "status" },
  { title: "ACTIONS", key: "actions", sortable: false },
]

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
  fetchTickets()
}

const fetchDepartments = async () => {
  try {
    const { data } = await $api("/departments?context=filters", {
      headers: {
        Authorization: `Bearer ${accessToken}`,
      },
    })
    departments.value = data.map((dept) => ({
      title: dept.name,
      value: dept.id,
    }))
  } catch (error) {
  }
}

const formatDateRange = (date) =>
  date.toISOString().split('T')[0];

const fetchTickets = async () => {
  loading.value = true;
  try {
    let startDate = '';
    let endDate = '';
    if (dateRange.value && typeof dateRange.value === 'string') {
      [startDate, endDate] = dateRange.value.split(' to ').map(d => d.trim());
      if (!startDate || !endDate) {
        return;
      }
    }
    const { data, error } = await useApi(
      createUrl("/tickets", {
        query: {
          q: searchQuery.value,
          department_id: selectedDepartment.value,
          start_date: startDate,
          end_date: endDate,
          status: selectedStatus.value,
          per_page: itemsPerPage.value,
          page: page.value,
          sortBy: sortBy.value,
          orderBy: orderBy.value,
        },
      }),
      { headers: { Authorization: `Bearer ${accessToken.value}` } }
    )

    ticketsData.value = data.value;
    tickets.value = ticketsData.value?.data || [];
    totalTickets.value = ticketsData.meta?.total ?? 0;
  } catch (err) {
    console.log('Error:', err);
    error.value = err
    $toast.error("Failed to fetch tickets")
  } finally {
    loading.value = false
  }
}

// const fetchStats = async () => {
//   try {
//     const response = await $api(
//       `/tickets/stats/${statsType.value}/${selectedMonth.value}`,
//       {
//         headers: {
//           Authorization: `Bearer ${accessToken.value}`,
//         },
//       }
//     )
//     stats.value = response.data || []
//   } catch (err) {
//     $toast.error("Failed to fetch ticket stats")
//     stats.value = []
//   }
// }

watch([statsType, selectedMonth, searchQuery, selectedStatus, selectedDepartment], fetchTickets)

onMounted(() => {
  fetchDepartments();
  // fetchTickets()
  // fetchStats()
})

const statuses = [
  { title: "Open", value: "Open" },
  { title: "Pending", value: "Pending" },
  { title: "Resolved", value: "Resolved" },
  { title: "Closed", value: "Closed" },
]

const resolveStatusVariant = (status) => {
  const variants = {
    Open: "primary",
    Pending: "warning",
    Resolved: "success",
    Closed: "secondary",
  }
  return variants[status] || "secondary"
}

const formatDate = (dateString) => {
  return dateString
    ? new Date(dateString).toLocaleDateString("en-GB", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    })
    : "-"
}

// Dialog control
const isTicketDialogVisible = ref(false)
const isFirst = ref(true)
const dialogMode = ref("create") // create | edit | view
const selectedTicket = ref(null)
const ticketDialogRef = ref(null)

const openCreateDialog = () => {
  dialogMode.value = "create"
  selectedTicket.value = null
  isTicketDialogVisible.value = true
  isFirst.value = true
}

const openEditDialog = (ticket) => {
  dialogMode.value = "edit"
  selectedTicket.value = { ...ticket }
  isTicketDialogVisible.value = true
  isFirst.value = true
}

const openViewDialog = (ticket) => {
  dialogMode.value = "view"
  selectedTicket.value = { ...ticket }
  isTicketDialogVisible.value = true
  isFirst.value = true
}

const handleDialogSubmit = async (data) => {
  try {
    const formData = new FormData()
    formData.append("employee_id", data.employee_id)
    formData.append("department_id", data.department_id)
    formData.append("poc_id", data.poc_id)
    formData.append("category_id", data.category_id)
    formData.append("description", data.description)
    formData.append("attachment", data.attachment || "")
    formData.append("status", data.status)
    formData.append("id", data.id)

    if (dialogMode.value === "create") {
      await $api("/tickets", {
        method: "POST",
        body: formData,
        headers: { Authorization: `Bearer ${accessToken.value}` },
      })
      $toast.success(`Ticket created successfully`)
    } else if (dialogMode.value === "edit" && selectedTicket.value?.id) {
      formData.append("_method", "PUT")
      await $api(`/tickets/${selectedTicket.value.id}`, {
        method: "POST",
        body: formData,
        headers: { Authorization: `Bearer ${accessToken.value}` },
      })
      $toast.success(`Ticket updated successfully`)
    }
    fetchTickets()
    isTicketDialogVisible.value = false
  } catch (err) {
    let message = "Something went wrong!"
    if (err.response && err.response.status === 422) {
      message = Object.values(err.response?._data?.errors).slice(0, 2).join("\n")
    }
    $toast.error(message)
    return;
  } finally {
    // Reset loading state in dialog
    ticketDialogRef.value?.resetLoadingState()
  }
}

// Delete confirmation dialog logic
const isDeleteDialogOpen = ref(false)
const deleteSubmitting = ref(false)
const deleteTargetId = ref(null)

const askDelete = id => {
  deleteTargetId.value = id
  isDeleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!deleteTargetId.value) {
    isDeleteDialogOpen.value = false
    return
  }
  deleteSubmitting.value = true
  try {
    await $api(`/tickets/${deleteTargetId.value}`, {
      method: "DELETE",
      headers: { Authorization: `Bearer ${accessToken.value}` },
    })
    isDeleteDialogOpen.value = false
    deleteTargetId.value = null
    await fetchTickets()
    $toast.success("Ticket deleted successfully")
  } catch (err) {
    $toast.error("Failed to delete ticket")
  } finally {
    deleteSubmitting.value = false
  }
}
</script>

<template>
  <section>
    <VBreadcrumbs
      class="px-0 pb-2 pt-0 help-center-breadcrumbs"
      :items="[{ title: 'Tickets' }]"
    />
    <!-- 👉 Tickets Table -->
    <VCard>
      <VCardText>
        <VRow>
            <VCol cols="12" md="4">
            <VAutocomplete
              v-model="selectedDepartment"
              :items="departments"
              label=""
              item-title="title"
              item-value="value"
              placeholder="Select Department"
              clearable
              no-data-text="No department found"
            />
          </VCol>
          <VCol cols="12" md="3" sm="6">
            <DemoDateTimePickerRange v-model="dateRange" label="" @change="fetchTickets"/>
          </VCol>
          <VCol cols="12" md="2">
            <AppSelect v-model="selectedStatus" placeholder="Status" :items="statuses" density="comfortable" clearable
                       clear-icon="tabler-x" hide-details />
          </VCol>
          <VCol cols="12" md="3" class="d-flex justify-end">
            <VBtn v-if="hasPermission('ticket.create')" prepend-icon="tabler-plus" color="primary"
                  @click="openCreateDialog">
              Create Ticket
            </VBtn>
          </VCol>
        </VRow>
        <VRow>
          <VCol cols="12" md="3">
            <AppTextField v-model="searchQuery" placeholder="Search Ticket" density="comfortable" hide-details />
          </VCol>
          <VCol cols="12" md="8"></VCol>
          <VCol cols="12" md="1" class="d-flex justify-end">
            <AppSelect v-model="itemsPerPage" :items="[10, 25, 50, 100]" density="comfortable" style="inline-size: 80px;"
                       hide-details class="show-select" />
          </VCol>
        </VRow>
      </VCardText>
      <VDivider />

      <!-- Data table -->
      <VDataTable :headers="headers" :items="tickets" :search="searchQuery" :items-per-page="itemsPerPage"
        v-model:page="page" v-model:items-per-page="itemsPerPage" class="text-no-wrap ticket-table" :items-length="totalTickets"
        @update:options="updateOptions" :loading="loading" loading-text="Loading data...">
        <!-- Employee -->
        <template #item.employee.name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <VAvatar size="34" :color="!item.profile_picture ? 'primary' : undefined"
                     :variant="!item.profile_picture ? 'tonal' : undefined">
              <DocumentImageViewer v-if="item.profile_picture" :type="'avatar'" :src="item.profile_picture" :pdf-title="item.employee?.name" />
              <span v-else>{{ item.employee?.name.charAt(0) || '-' }}</span>
            </VAvatar>
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item.employee.name }}
                <span v-if="useCookie('userData').value.employee_id === item.employee.id">(Me)</span>
              </h6>
              <div class="text-sm">
                {{ item.employee.official_email || item.employee.personal_email }}
              </div>
            </div>
          </div>
        </template>
        <!-- Ticket Code -->
        <template #item.ticket_code="{ item }">
          <div class="font-weight-medium text-high-emphasis">
            {{ item.ticket_code }}
          </div>
        </template>

        <!-- Department -->
        <template #item.department.name="{ item }">
          <div class="font-weight-medium">
            {{ item.department.name }}
          </div>
        </template>

        <!-- Status -->
        <template #item.status="{ item }">
          <VChip :color="resolveStatusVariant(item.status)" size="small" label>
            {{ item.status }}
          </VChip>
        </template>

        <!-- POC -->
        <template #item.poc.name="{ item }">
          <div class="font-weight-medium">
            {{ item.poc.name }}
            <span v-if="useCookie('userData').value.employee_id === item.poc.id">(Me)</span>
          </div>
        </template>

        <!-- Date -->
        <template #item.start_date="{ item }">
          <div class="text-high-emphasis text-body-1">
            {{ formatDate(item.start_date) }}
          </div>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <IconBtn v-if="hasPermission('ticket.read')" @click="openViewDialog(item)">
              <VIcon icon="tabler-eye" size="20" />
            </IconBtn>
            <IconBtn
              v-if="hasPermission('ticket.update') ||
              useCookie('userData').value.employee_id === item.poc.id ||
              useCookie('userData').value.employee_id === item.employee.id"
              @click="openEditDialog(item)"
            >
              <VIcon icon="tabler-pencil" size="20" />
            </IconBtn>
            <IconBtn v-if="hasPermission('ticket.delete')" @click="askDelete(item.id)">
              <VIcon icon="tabler-trash" size="20" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <TablePagination v-model:page="page" :items-per-page="itemsPerPage" :total-items="totalTickets" />
        </template>
      </VDataTable>
    </VCard>

    <!-- Ticket Dialog -->
    <TicketDialog ref="ticketDialogRef" v-model:is-open="isTicketDialogVisible" v-model:is-first="isFirst" :mode="dialogMode"
      :ticket="selectedTicket" @submit="handleDialogSubmit" @close="isTicketDialogVisible = false" />

    <!-- Delete Confirmation Dialog -->
    <ConfirmationDialog v-model="isDeleteDialogOpen" title="Are you sure"
      description="This action can not be undone. Do you want to continue?" cancel-text="No" confirm-text="Yes"
      :loading="deleteSubmitting" @confirm="confirmDelete" @close="isDeleteDialogOpen = false" />
  </section>
</template>

<style lang="scss" scoped>
.v-data-table {
  th {
    background-color: rgb(var(--v-theme-background));
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }

  td {
    vertical-align: middle;
  }
}

.month-picker {
  :deep(.v-field) {
    font-size: 0.875rem;
  }
}

.show-select {
  :deep(.v-field) {
    font-size: 0.875rem;
  }
}

.ticket-table {
  :deep(thead th) {
    background-color: rgb(var(--v-theme-surface));
    block-size: 56px;
  }

  :deep(tbody td) {
    block-size: 60px;
  }
}

// Align radio buttons and labels properly
:deep(.v-radio-group) {
  .v-col {
    padding-block: 0;
  }
}

:deep(.v-label.v-radio-label) {
  margin-block-end: 0;
}
</style>
