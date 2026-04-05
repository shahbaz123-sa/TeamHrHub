<script setup>
import { isNullOrUndefined } from '@/@core/utils/helpers'
import { getAllowed, getStatuses, resolvePaymentStatus, resolveStatus } from '@/utils/helpers/order'
import { formatLongText, humanize, stripHtml } from '@/utils/helpers/str'
import { hasPermission } from '@/utils/permission'
import { onMounted, ref } from 'vue'

const orderData = ref()
const orderDetails = ref()
const orderHistories = ref()

const allowedStatuses = ref([])
const selectedNewStatus = ref(null)
const isUpdatingStatus = ref(false)
const accessToken = useCookie('accessToken').value

const route = useRoute('apps-ecommerce-order-details-id')

const fetchOrder = async () => {
  try {
    const { data } = await $api(`/orders/${route.params.id}`, {
      method: 'GET',
    });

    orderData.value = {...data};
    // refresh allowed statuses for this order
    try { updateAllowedStatuses() } catch (e) {}

    if(!isNullOrUndefined(data.items)){
      orderDetails.value = (data.items ?? []).map((item) => {

        const product = {...(item?.variation ?? item?.product)}

        const uomName = item?.uom_name || ''
        const itemPrice = item?.price || 0
        return {
          'productName': product?.name || '',
          'productImage': item?.product?.image?.src || null,
          'subtitle': formatLongText(stripHtml(product?.short_description || '')),
          'price': uomName ? itemPrice +' / '+ uomName : itemPrice,
          'quantity': item?.quantity || 0,
          'total': item?.subtotal || 0
        }
      })
    }

    if(!isNullOrUndefined(data?.histories)) {
      
      orderHistories.value = getStatuses().map((status) => {

        if(status.value === 'pending') return null;

        const statusInHistory = (data?.histories ?? []).find((history) => history.status === status.value);

        return {
          status: status?.title || '',
          color: statusInHistory ? 'primary' : 'secondary',
          history_datetime: statusInHistory?.history_datetime || '',
        }
      }).filter((history) => !isNullOrUndefined(history))
    }

  } catch (error) {
    $toast.error("Failed to fetch order details");
  }
};

const updateAllowedStatuses = () => {
  const allowed = getAllowed(orderData.value?.status, orderData.value?.payment_method)
  const statuses = getStatuses()
  allowedStatuses.value = statuses.filter(s => allowed.includes(s.value))
}

const isCancelReasonDialogOpen = ref(false)
const cancelReasonSubmitting = ref(false)
const cancelReasonForm = ref({ cancel_reason: '' })

const updateOrderStatus = async () => {
  if (!selectedNewStatus.value) {
    $toast.warning('Please select a status')
    return
  }
  if (selectedNewStatus.value === 'cancelled') {
    isCancelReasonDialogOpen.value = true
    return
  }
  await doOrderStatusUpdate()
}

const doOrderStatusUpdate = async (withCancelReason = false) => {
  isUpdatingStatus.value = true
  try {
    const body = { status: selectedNewStatus.value }
    if (withCancelReason) body.cancel_reason = cancelReasonForm.value.cancel_reason
    const res = await $api(`/orders/${route.params.id}/status`, {
      method: 'PATCH',
      body,
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    $toast.success(res?.message || 'Order status updated successfully')
    selectedNewStatus.value = null
    isCancelReasonDialogOpen.value = false
    cancelReasonForm.value.cancel_reason = ''
    await fetchOrder()
  } catch (error) {
    let msg = 'Failed to update order status'
    if (error?.response?.status === 422) {
      msg = error.response._data?.message || msg
    }
    $toast.error(msg)
  } finally {
    isUpdatingStatus.value = false
  }
}

const confirmCancelOrder = async () => {
  if (!cancelReasonForm.value.cancel_reason.trim()) {
    $toast.warning('Please provide a cancel reason')
    return
  }
  await doOrderStatusUpdate(true)
}

const closeCancelReasonDialog = () => {
  isCancelReasonDialogOpen.value = false
  cancelReasonForm.value.cancel_reason = ''
}

const markPaymentReceived = async () => {
  if (!orderData.value) return

  try {
    const res = await $api(`/orders/${route.params.id}/payment-received`, {
      method: 'PATCH',
      headers: { Authorization: `Bearer ${accessToken}` },
    })

    $toast.success(res?.message || 'Payment status updated')
    await fetchOrder()
  } catch (error) {
    let msg = 'Failed to update payment status'
    if (error?.response?.status === 422) {
      msg = error.response._data?.message || msg
    }
    $toast.error(msg)
  }
}

const headers = [
  {
    title: 'Product',
    key: 'productName',
  },
  {
    title: 'Price',
    key: 'price',
  },
  {
    title: 'Quantity',
    key: 'quantity',
  },
  {
    title: 'Total',
    key: 'total',
  },
]

const getCustomerImage = (customer) => {
  if(customer?.type === 'B2B') {
    return customer?.company?.company_image;
  }

  return customer?.profile?.profile_image;
}

const getCustomerName = (customer) => {
  if(customer?.type === 'B2B') {
    return customer?.company?.company_name || '';
  }

  return(customer?.profile?.first_name || '') + ' ' + (customer?.profile?.last_name || '')
}

onMounted(() => {
  fetchOrder()
})
</script>

<template>
  <div v-if="orderData">
    <div class="d-flex justify-space-between align-center flex-wrap gap-y-4 mb-6">
      <div>
        <div class="d-flex gap-2 align-center mb-2 flex-wrap">
          <h5 class="text-h5">
            {{ orderData?.order_name || '' }}
          </h5>
          <div class="d-flex gap-x-2">
            <VChip
              v-if="orderData?.payment_status"
              variant="tonal"
              :color="resolvePaymentStatus(orderData?.payment_status || '')?.color"
              label
              size="small"
            >
              {{ resolvePaymentStatus(orderData?.payment_status || '')?.text }}
            </VChip>
            <VChip
              v-if="orderData?.status"
              v-bind="resolveStatus(orderData?.status || '')"
              label
              size="small"
            />
          </div>
        </div>
        <div class="text-body-1">
          {{ orderData?.order_datetime || '' }}
        </div>
      </div>
    </div>

    <VRow>
      <VCol
        cols="12"
        md="8"
      >
        <!-- 👉 Order Details -->
        <VCard class="mb-6">
          <VCardItem>
            <template #title>
              <h5 class="text-h5">
                Order Details
              </h5>

              <VDivider />
              
              <VRow class="mt-5 mb-2">
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>RFQ Id: </h6>
                    <p class="text-body-2 mb-0">{{  orderData?.rfq?.reference_no  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Order Type: </h6>
                    <p class="text-body-2 mb-0">{{  orderData?.order_type  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Payment Method: </h6>
                    <p class="text-body-2 mb-0">{{  humanize(orderData?.payment_method) }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Payment Schedule: </h6>
                    <p class="text-body-2 mb-0">{{  humanize(orderData?.payment_schedule) }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Platform: </h6>
                    <p class="text-body-2 mb-0">{{  humanize(orderData?.platfoam)  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Purchase Order No: </h6>
                    <p class="text-body-2 mb-0">{{  humanize(orderData?.purchase_order_number)  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Site Contact Person: </h6>
                    <p class="text-body-2 mb-0">{{  orderData?.site_contact_person  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Site Contact Phone: </h6>
                    <p class="text-body-2 mb-0">{{  orderData?.site_contact_phone  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Finance Contact: </h6>
                    <p class="text-body-2 mb-0">{{  orderData?.finance_contact  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Special Instructions: </h6>
                    <p class="text-body-2 mb-0">{{  orderData?.special_instructions  }}</p>
                  </div>
                </VCol>
                <VCol cols="6" class="pt-0 pb-0">
                  <div class="d-flex gap-1 align-center">
                    <h6>Cancel Reason: </h6>
                    <p class="text-body-2 mb-0">{{  orderData?.cancel_reason  }}</p>
                  </div>
                </VCol>
              </VRow>

            </template>
          </VCardItem>

          <VCardItem v-if="orderData?.documents?.length??0 > 0">
            <template #title>
              <h5 class="text-h5">
                Order Documents
              </h5>

              <VDivider />
              
              <VRow class="mt-5 mb-2">
                <VCol cols="6" class="pt-3 pb-0" v-for="document in orderData?.documents" :key="document.id">
                  <div class="d-flex gap-1 align-center">
                    <h6>{{ humanize(document?.document_type) }} </h6>
                    <div class="ml-4"></div>
                      <DocumentImageViewer
                        v-if="document?.document_url"
                        :src="document?.document_url"
                        :pdf-title="`${document?.document_type} for Order - ${orderData?.order_name}`"
                      />
                  </div>
                </VCol>
              </VRow>

            </template>
          </VCardItem>

          <VDivider />

          <VDataTable
            :headers="headers"
            :items="orderDetails"
            item-value="productName"
            class="text-no-wrap"
          >
            <template #item.productName="{ item }">
              <div class="d-flex gap-x-3 align-center">
                <VAvatar
                  size="34"
                  :image="item.productImage"
                  :rounded="0"
                />

                <div class="d-flex flex-column align-start">
                  <h6 class="text-h6 text-pre-wrap" style="max-inline-size: 300px;">
                    {{ item.productName }}
                  </h6>

                  <span class="text-body-2">
                    {{ item?.subtitle }}
                  </span>
                </div>
              </div>
            </template>

            <template #item.price="{ item }">
              <div class="text-body-1">
                PKR {{ item.price }}
              </div>
            </template>

            <template #item.total="{ item }">
              <div class="text-body-1">
                PKR {{ item.total }}
              </div>
            </template>

            <template #item.quantity="{ item }">
              <div class="text-body-1">
                {{ item.quantity }}
              </div>
            </template>

            <template #bottom />
          </VDataTable>
          <VDivider />

          <VCardText>
            <div class="d-flex align-end flex-column">
              <table class="text-high-emphasis">
                <tbody>
                  <tr>
                    <td width="200px">
                      Subtotal:
                    </td>
                    <td class="font-weight-medium">
                      PKR {{ orderData?.subtotal || 0 }}
                    </td>
                  </tr>
                  <tr>
                    <td>Discount: </td>
                    <td class="font-weight-medium">
                      PKR {{ orderData?.discount || 0 }}
                    </td>
                  </tr>
                  <tr>
                    <td>Shipping Total: </td>
                    <td class="font-weight-medium">
                      PKR {{ orderData?.shipping_fee || 0 }}
                    </td>
                  </tr>
                  <tr>
                    <td class="text-high-emphasis font-weight-medium">
                      Total:
                    </td>
                    <td class="font-weight-medium">
                      PKR {{ orderData?.total_amount || 0 }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </VCardText>
        </VCard>

        <!-- 👉 Shipping Activity -->
        <VCard title="Shipping Activity">
          <VCardText>
            <VTimeline
              truncate-line="both"
              line-inset="9"
              align="start"
              side="end"
              line-color="primary"
              density="compact"
            >
              <VTimelineItem
                dot-color="primary"
                size="x-small"
              >
                <div class="d-flex justify-space-between align-center">
                  <div class="app-timeline-title">
                    Order was placed
                  </div>
                  <div class="app-timeline-meta">
                    {{ orderData.order_datetime }}
                  </div>
                </div>
              </VTimelineItem>

              <VTimelineItem
                v-if="orderHistories"
                v-for="history in orderHistories"
                :key="history"
                :dot-color="history?.color"
                size="x-small"
              >
                <div class="d-flex justify-space-between align-center">
                  <span class="app-timeline-title">{{ humanize(history?.status || '') }}</span>
                  <span class="app-timeline-meta">{{ history?.history_datetime || '' }}</span>
                </div>
              </VTimelineItem>
            </VTimeline>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="4"
      >
        <!-- 👉 Update Status -->
        <VCard class="mb-6">
          <VCardText>
            <div class="d-flex align-center justify-space-between gap-4" style="flex: 1; min-inline-size: 0;">
              <VSelect
                v-model="selectedNewStatus"
                :items="allowedStatuses"
                item-title="title"
                item-value="value"
                :label="'Move to status'"
                dense
                hide-details
                style="flex: 1; min-inline-size: 0;"
              />

              <VBtn
                :loading="isUpdatingStatus"
                color="primary"
                variant="tonal"
                @click="updateOrderStatus"
              >
                Update
              </VBtn>
            </div>
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
                    :loading="isUpdatingStatus"
                    @click="confirmCancelOrder"
                    class="mr-3"
                  >
                    Cancel Order
                  </VBtn>
                </VCardActions>
              </VCard>
            </VDialog>
            <div class="mt-4">
              <VBtn
                v-if="hasPermission('order.update') && orderData?.payment_status !== 'paid'"
                color="success"
                variant="tonal"
                @click="markPaymentReceived"
              >
                Mark Payment Received
              </VBtn>
            </div>
          </VCardText>
        </VCard>
        <!-- 👉 Customer Details  -->
        <VCard class="mb-6">
          <VCardText class="d-flex flex-column gap-y-6">
            <h5 class="text-h5">
              Customer details
            </h5>

            <div class="d-flex align-center">
              <VAvatar
                v-if="orderData"
                :variant="!getCustomerImage(orderData?.customer) ? 'tonal' : undefined"
                :rounded="1"
                class="me-3"
              >
                <VImg
                  v-if="getCustomerImage(orderData?.customer)"
                  :src="getCustomerImage(orderData?.customer)"
                />

                <span
                  v-else-if="orderData?.customer?.type === 'B2C'"
                  class="font-weight-medium"
                >{{ avatarText(orderData?.customer?.username || '') }}</span>
              </VAvatar>
              <div>
                <h6 class="text-h6">
                  {{ getCustomerName(orderData?.customer) }}
                </h6>
                <div class="text-body-1" v-if="orderData?.customer?.type === 'B2C'">
                  Customer ID: {{ orderData?.customer?.username || '' }}
                </div>
              </div>
            </div>

            <div class="d-flex gap-x-3 align-center">
              <VAvatar
                variant="tonal"
                color="success"
              >
                <VIcon icon="tabler-shopping-cart" />
              </VAvatar>
              <h6 class="text-h6">
                {{ orderData?.customer?.total_orders || 0 }} Orders
              </h6>
            </div>

            <div class="d-flex flex-column gap-y-1">
              <div class="d-flex justify-space-between align-center">
                <h6 class="text-h6">
                  Contact Info
                </h6>
              </div>
              <span>Email: {{ orderData?.customer?.email || '' }}</span>
              <span>Mobile: {{ orderData?.customer?.phone_number || '' }}</span>
            </div>
          </VCardText>
        </VCard>

        <!-- 👉 Shipping Address -->
        <VCard class="mb-6">
          <VCardItem>
            <VCardTitle>Shipping Address ({{ orderData?.shipping_option || '' }})</VCardTitle>
          </VCardItem>
          <VCardText>
            <div class="text-body-1">
              {{ orderData?.street_address || '' }} <br> {{ orderData?.postcode || '' }}, {{ orderData?.state || '' }} <br> {{ orderData?.city || '' }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

  </div>
  <div v-else class="text-center">
    <VProgressCircular indeterminate size="64" />
  </div>
</template>
