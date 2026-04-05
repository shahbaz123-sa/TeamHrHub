<script setup>
import ConfirmationDialog from '@/components/common/ConfirmationDialog.vue'
import { getAllowed, getPaymentMethods, getPaymentStatuses, getStatuses, resolvePaymentStatus, resolveStatus } from '@/utils/helpers/order'
import { hasPermission } from '@/utils/permission'
import { onMounted } from 'vue'

const headers = [
  {
    title: 'Select',
    key: 'data-table-select',
  },
  {
    title: 'Order #',
    key: 'order_name',
  },
  {
    title: 'Date',
    key: 'order_date',
  },
  {
    title: 'Customer',
    key: 'customer_username',
  },
  {
    title: 'Method',
    key: 'payment_method',
  },
  {
    title: 'Payment',
    key: 'payment_status',
  },
  {
    title: 'Status',
    key: 'status',
  },
  {
    title: 'Action',
    key: 'actions',
  },
]

const today = new Date().toISOString().slice(0, 10)
const filters = ref({
  method: null,
  payment_status: null,
  status: null,
  start_date: "2025-10-01",
  end_date: today,
  type: null,
  q: '',
  customer_type: null
})

const customerTypeTabs = ref([
  { title: 'All', value: null },
  { title: 'Business to Business', value: 'B2B' },
  { title: 'Business to Consumer', value: 'B2C' },
])

const widgetData = ref([])

const fetchWidgetData = async () => {
  const { data } = await useApi(createUrl('/orders/widgets/status-counts'))
  const stats = data.value ?? {}

  widgetData.value = [
    {
      title: 'Pending Payment',
      value: stats.pending_payment ?? 0,
      icon: 'tabler-calendar-stats',
    },
    {
      title: 'Completed',
      value: stats.completed ?? 0,
      icon: 'tabler-checks',
    },
    {
      title: 'Refunded',
      value: stats.refunded ?? 0,
      icon: 'tabler-wallet',
    },
    {
      title: 'Failed',
      value: stats.failed ?? 0,
      icon: 'tabler-alert-octagon',
    },
  ]
}

const methods = ref([
  ...getPaymentMethods()
])

const paymentStatuses = ref([
  ...getPaymentStatuses()
])

const statuses = ref([
  ...getStatuses()
])

const accessToken = useCookie('accessToken').value

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()
const orders = ref([])
const totalOrders = ref(0)
const dataTableLoading = ref(false)
const selectedOrders = ref([])

const fetchOrders = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/orders', {
    query: {
      ...filters.value,
      page,
      per_page: itemsPerPage,
      sort_by: sortBy,
      order: orderBy,
    },
  }))

  orders.value = data.value?.data ?? []
  totalOrders.value = data.value?.meta?.total || 0

  dataTableLoading.value = false
  
  await fetchWidgetData()
}

const statusChangeItem = ref(null)
const statusChangeTarget = ref(null)
const isCancelReasonDialogOpen = ref(false)
const cancelReasonSubmitting = ref(false)
const cancelReasonForm = ref({
  cancel_reason: '',
})

const changeStatus = async (item, target) => {
  // If cancelling, open the dialog for cancel reason
  if (target === 'cancelled') {
    statusChangeItem.value = item
    statusChangeTarget.value = target
    isCancelReasonDialogOpen.value = true
    return
  }

  // For other statuses, proceed without dialog
  try {
    const res = await $api(`/orders/${item.id}/status`, {
      method: 'PATCH',
      body: { status: target },
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    
    await fetchOrders()
    $toast.success(res?.message || 'Order status updated')
  } catch (e) {
    let msg = 'Failed to update status'
    if (e?.response && e?.response?.status === 422) {
      msg = e.response._data?.message || msg
    }
    $toast.error(msg)
  }
}

const closeCancelReasonDialog = () => {
  isCancelReasonDialogOpen.value = false
  cancelReasonForm.value.cancel_reason = ''
  statusChangeItem.value = null
  statusChangeTarget.value = null
}

const confirmCancelOrder = async () => {
  if (!cancelReasonForm.value.cancel_reason.trim()) {
    $toast.warning('Please provide a cancel reason')
    return
  }

  cancelReasonSubmitting.value = true
  try {
    const res = await $api(`/orders/${statusChangeItem.value.id}/status`, {
      method: 'PATCH',
      body: { 
        status: statusChangeTarget.value,
        cancel_reason: cancelReasonForm.value.cancel_reason,
      },
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    
    closeCancelReasonDialog()
    await fetchOrders()
    $toast.success(res?.message || 'Order cancelled successfully')
  } catch (e) {
    let msg = 'Failed to cancel order'
    if (e?.response && e?.response?.status === 422) {
      msg = e.response._data?.message || msg
    }
    $toast.error(msg)
  } finally {
    cancelReasonSubmitting.value = false
  }
}

const markPaymentReceived = async (item) => {
  try {
    const res = await $api(`/orders/${item.id}/payment-received`, {
      method: 'PATCH',
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    await fetchOrders()
    $toast.success(res?.message || 'Payment status updated')
  } catch (e) {
    let msg = 'Failed to update payment status'
    if (e?.response && e?.response?.status === 422) {
      msg = e.response._data?.message || msg
    }
    $toast.error(msg)
  }
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
    await $api(`/orders/${deleteTargetId.value}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${accessToken}` }
    })
    fetchOrders()
    $toast.success('Order deleted!')
  } catch (e) {
    $toast.error('Failed to delete')
  } finally {
    isDeleteDialogOpen.value = false;
    deleteSubmitting.value = false;
    deleteTargetId.value = null;
  }
}

// Bulk status update

const isBulkStatusDialogOpen = ref(false)
const isBulkCancelReasonDialogOpen = ref(false)
const bulkStatusSubmitting = ref(false)
const bulkStatusForm = ref({
  status: null,
})
const bulkCancelReasonForm = ref({
  cancel_reason: '',
})

// Bulk payment received dialog
const isBulkPaymentDialogOpen = ref(false)
const bulkPaymentSubmitting = ref(false)


const openBulkStatusDialog = () => {
  if (selectedOrders.value.length === 0) {
    $toast.warning('Please select at least one order')
    return
  }
  isBulkStatusDialogOpen.value = true
}


const closeBulkStatusDialog = () => {
  isBulkStatusDialogOpen.value = false
  bulkStatusForm.value.status = null
}

const closeBulkCancelReasonDialog = () => {
  isBulkCancelReasonDialogOpen.value = false
  bulkCancelReasonForm.value.cancel_reason = ''
}


const confirmBulkStatusUpdate = async () => {
  if (!bulkStatusForm.value.status) {
    $toast.warning('Please select a status')
    return
  }
  // If cancelling, show cancel reason dialog
  if (bulkStatusForm.value.status === 'cancelled') {
    isBulkStatusDialogOpen.value = false
    isBulkCancelReasonDialogOpen.value = true
    return
  }
  await doBulkStatusUpdate()
}

const doBulkStatusUpdate = async (withCancelReason = false) => {
  bulkStatusSubmitting.value = true
  try {
    const body = {
      order_ids: [...selectedOrders.value],
      status: bulkStatusForm.value.status,
    }
    if (withCancelReason) {
      body.cancel_reason = bulkCancelReasonForm.value.cancel_reason
    }
    const res = await $api('/orders/bulk/change-status', {
      method: 'POST',
      body,
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    selectedOrders.value = []
    closeBulkStatusDialog()
    closeBulkCancelReasonDialog()
    await fetchOrders()
    
    const { updated, skipped, errors } = res.data
    let msg = `${updated} order(s) updated`
    if (skipped > 0) msg += `, ${skipped} skipped`
    if (errors.length > 0) msg += `, ${errors.length} error(s)`
    
    $toast.success(msg)
  } catch (e) {
    let msg = 'Failed to update orders'
    if (e?.response && e?.response?.status === 422) {
      msg = e.response._data?.message || msg
    }
    $toast.error(msg)
  } finally {
    bulkStatusSubmitting.value = false
  }
}

const confirmBulkCancelOrder = async () => {
  if (!bulkCancelReasonForm.value.cancel_reason.trim()) {
    $toast.warning('Please provide a cancel reason')
    return
  }
  await doBulkStatusUpdate(true)
}

const openBulkPaymentDialog = () => {
  if (selectedOrders.value.length === 0) {
    $toast.warning('Please select at least one order')
    return
  }
  isBulkPaymentDialogOpen.value = true
}

const closeBulkPaymentDialog = () => {
  isBulkPaymentDialogOpen.value = false
}

const confirmBulkPaymentReceived = async () => {
  bulkPaymentSubmitting.value = true
  try {
    const res = await $api('/orders/bulk/mark-payment-received', {
      method: 'POST',
      body: {
        order_ids: [...selectedOrders.value],
      },
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    selectedOrders.value = []
    closeBulkPaymentDialog()
    await fetchOrders()

    const { updated, skipped, errors } = res.data
    let msg = `${updated} order(s) updated`
    if (skipped > 0) msg += `, ${skipped} skipped`
    if (errors.length > 0) msg += `, ${errors.length} error(s)`

    $toast.success(msg)
  } catch (e) {
    let msg = 'Failed to update orders'
    if (e?.response && e?.response?.status === 422) {
      msg = e.response._data?.message || msg
    }
    $toast.error(msg)
  } finally {
    bulkPaymentSubmitting.value = false
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
  fetchOrders()
}, {deep: true})

onMounted(() => {
  fetchOrders()
})
</script>

<template>
  <div>

    <!-- 👉 Customer Type Tabs -->
    <div class="mb-6 d-flex gap-3 align-center">
      <VTabs
        background-color="transparent"
      >
        <VTab
          v-for="tab in customerTypeTabs"
          :key="tab.value"
          @click="filters.customer_type = tab.value"
        >
        {{ tab.title }}
        </VTab>
      </VTabs>
    </div>

    <VCard class="mb-6">
      <!-- 👉 Widgets  -->
      <VCardText>
        <VRow>
          <template
            v-for="(data, id) in widgetData"
            :key="id"
          >
            <VCol
              cols="12"
              sm="6"
              md="3"
              class="px-6"
            >
              <div
                class="d-flex justify-space-between"
                :class="$vuetify.display.xs
                  ? id !== widgetData.length - 1 ? 'border-b pb-4' : ''
                  : $vuetify.display.sm
                    ? id < (widgetData.length / 2) ? 'border-b pb-4' : ''
                    : ''"
              >
                <div class="d-flex flex-column">
                  <h4 class="text-h4">
                    {{ data.value }}
                  </h4>

                  <div class="text-body-1">
                    {{ data.title }}
                  </div>
                </div>

                <VAvatar
                  variant="tonal"
                  rounded
                  size="42"
                >
                  <VIcon
                    :icon="data.icon"
                    size="26"
                    class="text-high-emphasis"
                  />
                </VAvatar>
              </div>
            </VCol>
            <VDivider
              v-if="$vuetify.display.mdAndUp ? id !== widgetData.length - 1
                : $vuetify.display.smAndUp ? id % 2 === 0
                  : false"
              vertical
              inset
              length="60"
            />
          </template>
        </VRow>
      </VCardText>
    </VCard>

    <!-- 👉 orders -->
    <VCard
      title="Filters"
      class="mb-6"
    >
      <VCardText>
        <VRow>

          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="filters.status"
              placeholder="Order Status"
              :items="statuses"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="filters.payment_status"
              placeholder="Payment Status"
              :items="paymentStatuses"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <VCol
            cols="12"
            sm="3"
          >
            <AppSelect
              v-model="filters.method"
              placeholder="Payment Method"
              :items="methods"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

        </VRow>

        <VRow>

          <VCol
            cols="12"
            sm="3"
          >
            <AppDateTimePicker
              v-model="filters.start_date"
              label="Order Start Date"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>
          
          <VCol
            cols="12"
            sm="3"
          >
            <AppDateTimePicker
              v-model="filters.end_date"
              label="Order End Date"
              clearable
              clear-icon="tabler-x"
            />
          </VCol>

        </VRow>

      </VCardText>

      <VDivider />

      <VCardText>
        <VRow>
          <VCol cols="12" sm="3">
            <AppTextField
              v-model="filters.q"
              placeholder="Search Order"
            />
          </VCol>
          <VCol cols="12" sm="3">
            <div class="d-flex gap-2">
              <VBtn
                v-if="selectedOrders.length > 0 && hasPermission('order.update')"
                color="primary"
                @click="openBulkStatusDialog"
              >
                Bulk Update ({{ selectedOrders.length }})
              </VBtn>

              <VBtn
                v-if="selectedOrders.length > 0 && hasPermission('order.update')"
                color="success"
                variant="tonal"
                @click="openBulkPaymentDialog"
              >
                Mark Payment Received ({{ selectedOrders.length }})
              </VBtn>
            </div>
          </VCol>
          <VCol cols="12" sm="3" />
          <VCol cols="12" sm="3">
            <div class="d-flex flex-wrap gap-2 float-right">
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
        v-model="selectedOrders"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="orders"
        :items-length="totalOrders"
        :loading="dataTableLoading"
        :loading-text="'Loading orders...'"
        class="text-no-wrap"
        show-select
        @update:options="updateOptions"
      >
        <template #item.customer_username="{ item }">
          <div class="d-flex align-center gap-x-4">
            <div class="d-flex flex-column">
              <h6 class="text-base">
                {{ item?.customer?.type === 'B2B' ? item?.customer?.company?.company_name : item?.customer?.username }}
              </h6>
              <div class="text-sm">
                {{ item?.customer?.email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.payment_status="{ item }">
          <div
            v-if="item.payment_status"
            :class="`text-${resolvePaymentStatus(item.payment_status)?.color}`"
            class="font-weight-medium d-flex align-center gap-x-2"
          >
            <VIcon
              icon="tabler-circle-filled"
              size="10"
            />
            <div style="line-height: 22px;">
              {{ resolvePaymentStatus(item.payment_status)?.text }}
            </div>
          </div>
        </template>
        
        <template #item.status="{ item }">
          <VChip
            v-if="item.status"
            v-bind="resolveStatus(item.status)"
            density="default"
            label
            size="small"
          />
        </template>

        <template #item.order_name="{ item }">
          <RouterLink
            :to="{ name: 'crm-order-details-id', params: { id: item.id } }"
            class="text-link font-weight-medium d-inline-block"
            style="line-height: 1.375rem;"
          >
            <span>{{ item.order_name }}</span>
          </RouterLink>
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <!-- <IconBtn v-if="hasPermission('order.read')" :to="{
            name: 'crm-order-details-id',
            params: { id: item.id },
          }">
            <VIcon icon="tabler-eye" />
          </IconBtn> -->
          <!-- <IconBtn v-if="hasPermission('order.update')" :to="{
            name: 'crm-order-edit-id',
            params: { id: item.id },
          }">
            <VIcon icon="tabler-edit" />
          </IconBtn> -->

          <!-- <IconBtn v-if="hasPermission('order.delete')" @click="askDelete(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn> -->

          <!-- Status transitions menu (hidden for final states) -->
          <div
          v-if="hasPermission('order.read') || (hasPermission('order.update') && !['completed','cancelled','refunded'].includes(item.status))"
          >
            <IconBtn>
              <VIcon icon="tabler-dots-vertical" />
              <VMenu activator="parent">
                <VList>
                  <VListItem
                    v-if="hasPermission('order.read')"
                    value="view"
                    :to="{ name: 'crm-order-details-id', params: { id: item.id } }"
                  >
                    View
                  </VListItem>
                  <VDivider class="m-0 p-0" />
                  <VListItem
                    v-if="hasPermission('order.update') && item.payment_status !== 'paid'"
                    @click.prevent="markPaymentReceived(item)">
                    <VListItemTitle>
                      Mark Payment Received
                    </VListItemTitle>
                  </VListItem>

                  <VListItem
                    v-if="hasPermission('order.update') && !['completed','cancelled','refunded'].includes(item.status)"
                    v-for="s in getAllowed(item.status, item.payment_method)"
                    :key="s"
                    @click="changeStatus(item, s)">
                    <VListItemTitle>
                      Move to {{ s.replace('_', ' ') }}
                    </VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </IconBtn>
          </div>
        </template>

        <!-- pagination -->
        <template #bottom>
          <TablePagination
            v-model:page="page"
            :items-per-page="itemsPerPage"
            :total-items="totalOrders"
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

    <VDialog
      v-model="isBulkPaymentDialogOpen"
      max-width="400"
    >
      <VCard>
        <VCardTitle>Mark Payment Received</VCardTitle>
        <VDivider />
        <VCardText>
          <p class="text-body-2">{{ selectedOrders.length }} Order(s) payment status will be marked as "Paid". Are you sure?</p>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" @click="closeBulkPaymentDialog">Cancel</VBtn>
          <VBtn color="primary" :loading="bulkPaymentSubmitting" @click="confirmBulkPaymentReceived">Confirm</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Bulk Status Update Dialog -->

    <VDialog
      v-model="isBulkStatusDialogOpen"
      max-width="400"
    >
      <VCard>
        <VCardTitle>Update Status for {{ selectedOrders.length }} Order(s)</VCardTitle>
        <VDivider />
        <VCardText>
          <AppSelect
            v-model="bulkStatusForm.status"
            label="Select New Status"
            placeholder="Choose status"
            :items="statuses"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            @click="closeBulkStatusDialog"
          >
            Cancel
          </VBtn>
          <VBtn
            color="primary"
            :loading="bulkStatusSubmitting"
            @click="confirmBulkStatusUpdate"
          >
            Update
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Bulk Cancel Reason Dialog -->
    <VDialog
      v-model="isBulkCancelReasonDialogOpen"
      max-width="500"
    >
      <VCard>
        <VCardTitle>Cancel Orders</VCardTitle>
        <VDivider />
        <VCardText class="py-6">
          <AppTextField
            v-model="bulkCancelReasonForm.cancel_reason"
            label="Cancel Reason"
            placeholder="Enter reason for cancellation"
            type="textarea"
            rows="4"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            @click="closeBulkCancelReasonDialog"
          >
            Close
          </VBtn>
          <VBtn
            color="error"
            variant="elevated"
            :loading="bulkStatusSubmitting"
            @click="confirmBulkCancelOrder"
            class="mr-3"
          >
            Cancel Orders
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Cancel Reason Dialog -->
    <VDialog
      v-model="isCancelReasonDialogOpen"
      max-width="500"
    >
      <VCard>
        <VCardTitle>Cancel Order</VCardTitle>
        <VDivider />
        <VCardText class="py-6">
          <AppTextField
            v-model="cancelReasonForm.cancel_reason"
            label="Cancel Reason"
            placeholder="Enter reason for cancellation"
            type="textarea"
            rows="4"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn
            color="secondary"
            @click="closeCancelReasonDialog"
          >
            Close
          </VBtn>
          <VBtn
            color="error"
            variant="elevated"
            :loading="cancelReasonSubmitting"
            @click="confirmCancelOrder"
            class="mr-3"
          >
            Cancel Order
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

  </div>
</template>
