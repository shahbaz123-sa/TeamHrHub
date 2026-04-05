<script setup>
import { humanize } from '@/utils/helpers/str'
import { onMounted } from 'vue'


const route = useRoute('apps-ecommerce-customer-details-id')

const searchQuery = ref('')

// Data table options
const itemsPerPage = ref(10)
const page = ref(1)
const sortBy = ref()
const orderBy = ref()

const updateOptions = options => {
  sortBy.value = options.sortBy[0]?.key
  orderBy.value = options.sortBy[0]?.order
}

const orders = ref([])
const totalOrders = ref(0)
const dataTableLoading = ref(false)
const fetchOrders = async () => {
  dataTableLoading.value = true
  const { data } = await useApi(createUrl('/orders', {
    query: {
      page,
      per_page: itemsPerPage,
      sort_by: sortBy,
      order: orderBy,
      customer_id: route.params.id
    },
  }))

  orders.value = data.value?.data ?? []
  totalOrders.value = data.value?.meta?.total || 0

  dataTableLoading.value = false
}

const headers = [
  {
    title: 'Order',
    key: 'order_name',
  },
  {
    title: 'Date',
    key: 'order_date',
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
    title: 'Spent',
    key: 'spent',
  },
]

const resolvePaymentStatus = status => {
  if (status === 'paid')
    return {
      text: 'Paid',
      color: 'success',
    }
  if (status === 'unpaid')
    return {
      text: 'Unpaid',
      color: 'error',
    }
  if (status === 'refunded')
    return {
      text: 'Refunded',
      color: 'secondary',
    }
  if (status === 'failed')
    return {
      text: 'Failed',
      color: 'error',
    }
}

const resolveStatus = status => {
  if (status === 'pending')
    return {
      text: 'Pending',
      color: 'warning',
    }
  if (status === 'awaiting_payment')
    return {
      text: 'Awaiting Payment',
      color: 'info',
    }
  if (status === 'processing')
    return {
      text: 'Processing',
      color: 'info',
    }
  if (status === 'completed')
    return {
      text: 'Completed',
      color: 'success',
    }
  if (status === 'cancelled')
    return {
      text: 'Cancelled',
      color: 'primary',
    }
  if (status === 'refunded')
    return {
      text: 'Refunded',
      color: 'warning',
    }
}

onMounted(() => {
  fetchOrders()
})
</script>

<template>
  <VCard>
    <VCardText>
      <div class="d-flex justify-space-between flex-wrap align-center gap-4">
        <h5 class="text-h5">
          Orders placed
        </h5>
      </div>
    </VCardText>

    <VDivider />
    <VDataTableServer
      v-model:items-per-page="itemsPerPage"
      v-model:page="page"
      :headers="headers"
      :items="orders"
      item-value="id"
      :items-length="totalOrders"
      class="text-no-wrap"
      :loading="dataTableLoading"
      :loading-text="'Loading order...'"
      @update:options="updateOptions"
    >
      <!-- Order ID -->
      <template #item.order="{ item }">
        <RouterLink :to="{ name: 'apps-ecommerce-order-details-id', params: { id: item.order } }">
          #{{ item.order }}
        </RouterLink>
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

      <!-- Status -->
      <template #item.status="{ item }">
        <VChip
          label
          :color="resolveStatus(item.status)?.color"
          size="small"
        >
          {{ humanize(item.status) }}
        </VChip>
      </template>

      <!-- Spent -->
      <template #item.spent="{ item }">
        PKR {{ item.total_amount }}
      </template>

      <!-- Actions -->
      <template #item.actions="{ item }">
        <IconBtn>
          <VIcon icon="tabler-dots-vertical" />
          <VMenu activator="parent">
            <VList>
              <VListItem
                value="view"
                :to="{ name: 'apps-ecommerce-order-details-id', params: { id: item.order } }"
              >
                View
              </VListItem>
            </VList>
          </VMenu>
        </IconBtn>
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
</template>
