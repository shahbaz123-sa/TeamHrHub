<script setup>
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue'
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue'
import { onMounted } from 'vue'

const headers = [
  {
    title: 'Company Name',
    key: 'company.company_name',
  },
  {
    title: 'Company Type',
    key: 'company.company_type',
  },
  {
    title: 'Industry',
    key: 'company.industry_type',
  },
  {
    title: 'Status',
    key: 'company.status',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

const filters = ref({
  status: null,
  type: 'B2B',
  q: '',
})

const status = ref([
  {
    title: 'Pending',
    value: 'PENDING',
  },
  {
    title: 'Approved',
    value: 'APPROVED',
  },
  {
    title: 'Rejected',
    value: 'REJECTED',
  },
])

const accessToken = useCookie('accessToken').value

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const resolveStatus = statusMsg => {
  if (statusMsg === 'APPROVED')
    return {
      text: 'Approved',
      color: 'success',
    }
  else if (statusMsg === 'PENDING')
    return {
      text: 'Pending',
      color: 'warning',
    }
  else if (statusMsg === 'REJECTED')
    return {
      text: 'Rejected',
      color: 'error',
    }
}

const widgetData = ref([])

const fetchWidgetData = async () => {
  const { data } = await useApi(createUrl('/customers/widgets/status-counts'))
  const stats = data.value.data ?? {}
  widgetData.value = [
    { title: 'Customers', value: stats.customers ?? 0, icon: 'tabler-user' },
    { title: 'Pending', value: stats.pending ?? 0, icon: 'tabler-clock' },
    { title: 'Approved', value: stats.approved ?? 0, icon: 'tabler-check' },
    { title: 'Rejected', value: stats.rejected ?? 0, icon: 'tabler-trash' },
  ]
}

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const customers = ref([])
const totalCustomers = ref(0)
const dataTableLoading = ref(false)
const fetchCustomers = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/customers', {
    query: {
      ...filters.value,
      page,
      per_page: itemsPerPage,
      sort_by: sortBy,
      order: orderBy,
    },
  }))

  customers.value = data.value?.data ?? []
  totalCustomers.value = data.value?.meta?.total || 0

  dataTableLoading.value = false
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
    await $api(`/customers/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchCustomers()
    $toast.success('Customer deleted!')
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
  fetchCustomers()
}, {deep: true})


const isUpdatingCustomerStatus = ref(false)
const updatingCustomerId = ref(null)

const updateCompanyStatus = async (id, status) => {
  isUpdatingCustomerStatus.value = true
  updatingCustomerId.value = id
  try {
    const res = await $api(`/customers/${id}/company/status`, {
      method: 'PATCH',
      body: { status },
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    $toast.success(res?.message || 'Company status updated')
    fetchCustomers()
  } catch (error) {
    let msg = 'Failed to update company status'
    if (error?.response?.status === 422) {
      msg = error.response._data?.message || msg
    }
    $toast.error(msg)
  } finally {
    isUpdatingCustomerStatus.value = false
    updatingCustomerId.value = null
  }
}

onMounted(() => {
  fetchCustomers()
  fetchWidgetData()
})
</script>

<template>
  <div>

    <VCard class="mb-6">
      <VCardText>
        <VRow>
          <template v-for="(data, id) in widgetData" :key="id">
            <VCol
              cols="12"
              sm="6"
              md="3"
              class="px-6"
              :class="id > 0 ? 'border-s-md' : ''"
            >
              <div class="d-flex justify-space-between">
                <div>
                  <h4 class="text-h4">{{ data.value }}</h4>
                  <div class="text-body-1">{{ data.title }}</div>
                </div>
                <VAvatar v-if="data.icon" variant="tonal" rounded size="42">
                  <VIcon :icon="data.icon" size="26" class="text-high-emphasis" />
                </VAvatar>
              </div>
            </VCol>
          </template>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 customers -->
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>
          
          <!-- 👉 Select Status -->
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="filters.status"
              placeholder="Status"
              :items="status"
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
              placeholder="Search Customer"
            />
          </VCol>
          <VCol cols="12" sm="6" />
          <VCol
            cols="12"
            sm="3"
          >
          <div class="d-flex flex-wrap float-right">
              <AppSelect
                v-model="itemsPerPage"
                :items="[5, 10, 20, 25, 50]"
                style="max-inline-size: 80px;"
              />
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
        :items="customers"
        :items-length="totalCustomers"
        :loading="dataTableLoading"
        :loading-text="'Loading customers...'"
        class="text-no-wrap"
        @update:options="updateOptions"
      >
        <template #item.company.company_name="{ item }">
          <div class="d-flex align-center gap-x-4">
            <DocumentImageViewer
              :type="'avatar'"
              :src="item?.company?.company_image"
              :default-name="item?.company?.company_name?.slice(0, 2)"
            />
            <div class="d-flex flex-column">
              <RouterLink
                :to="{ name: 'crm-customer-details-id', params: { id: item.id } }"
                class="text-link font-weight-medium d-inline-block"
                style="line-height: 1.375rem;"
              >
                {{ item?.company?.company_name }}
              </RouterLink>
            </div>
          </div>
        </template>

        <template #item.company.status="{ item }">
          <VChip
            v-if="item?.company?.status"
            v-bind="resolveStatus(item?.company?.status)"
            density="default"
            label
            size="small"
          />
        </template>

        <template #item.actions="{ item }">
          <VMenu
            v-if="item?.company?.status && item?.company?.status === 'PENDING'"
          >
            <template #activator="{ props }">
              <VBtn v-bind="props" icon size="small" variant="text">
                <VIcon icon="tabler-dots-vertical" />
              </VBtn>
            </template>
            <VList>
              <VListItem
                v-if="item?.company?.status === 'PENDING'"
                :disabled="updatingCustomerId === item.id && isUpdatingCustomerStatus"
                @click="updateCompanyStatus(item.id, 'APPROVED')"
              >
                Mark Approved
              </VListItem>
              <VListItem
                v-if="item?.company?.status === 'PENDING'"
                :disabled="updatingCustomerId === item.id && isUpdatingCustomerStatus"
                @click="updateCompanyStatus(item.id, 'REJECTED')"
              >
                Mark Rejected
              </VListItem>
            </VList>
          </VMenu>
        </template>

        <!-- pagination -->
        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalCustomers"
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
