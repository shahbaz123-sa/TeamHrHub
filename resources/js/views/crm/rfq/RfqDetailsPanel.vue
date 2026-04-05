<script setup>
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue'
import { humanize } from '@/utils/helpers/str'

const props = defineProps({
  rfqData: {
    type: Object,
    required: true,
  },
})

const firstItem = computed(() => props.rfqData?.item)

const resolveStatus = (status) => {
  const statusMap = {
    pending: { text: 'Pending', color: 'warning' },
    quotation_received: { text: 'Quotation Received', color: 'warning' },
    quotation_sent: { text: 'Quotation Sent', color: 'success' },
    completed: { text: 'Completed', color: 'success' },
  }
  return statusMap[status] || { text: status, color: 'secondary' }
}
</script>

<template>
  <VCard class="mb-6">
    <VCardText class="text-center">
      <h5 class="text-h5 mt-4 color-primary">
        {{ props.rfqData?.reference_no }}
      </h5>
    </VCardText>
    <VCardText>

      <h5 class="text-h5">
        Details
      </h5>

      <VDivider class="my-4" />

      <VList class="card-list mt-2">
        <VListItem>
          <h6 class="text-h6">
            Customer:
            <span class="text-body-1 d-inline-block">
              {{ props.rfqData?.user?.type === 'B2B' ? props.rfqData?.user?.company?.company_name : props.rfqData?.user?.username }}
            </span>
          </h6>
        </VListItem>
        <VListItem>
          <h6 class="text-h6">
            Email:
            <span class="text-body-1 d-inline-block">
              {{ props.rfqData?.user?.email }}
            </span>
          </h6>
        </VListItem>

        <VListItem>
          <div class="d-flex gap-x-2 align-center">
            <h6 class="text-h6">
              Status:
            </h6>
            <VChip :color="resolveStatus(props.rfqData?.status).color" size="small">
              {{ resolveStatus(props.rfqData?.status).text }}
            </VChip>
          </div>
        </VListItem>

        <VListItem>
          <h6 class="text-h6">
            Commodity:
            <span class="text-body-1 d-inline-block">
              {{ firstItem?.product?.name ?? humanize(firstItem?.product_name) }}
            </span>
          </h6>
        </VListItem>
        
        <VListItem>
          <div class="d-flex gap-x-2 align-center">
            <h6 class="text-h6">
              Quantity:
              <span class="text-body-1 d-inline-block">
              {{ firstItem?.quantity }} {{ firstItem?.uom }}
            </span>
            </h6>
          </div>
        </VListItem>

        <VListItem>
          <h6 class="text-h6">
            RFQ Date:
            <span class="text-body-1 d-inline-block">
              {{ props.rfqData?.req_date }}
            </span>
          </h6>
        </VListItem>

        <VListItem>
          <h6 class="text-h6">
            Payment Method:
            <span class="text-body-1 d-inline-block">
              {{ props.rfqData?.payment_method }}
            </span>
          </h6>
        </VListItem>
        
        <VListItem>
          <h6 class="text-h6">
            Preferred Delivery Date:
            <span class="text-body-1 d-inline-block">
              {{ props.rfqData?.preferred_delivery_date }}
            </span>
          </h6>
        </VListItem>
        
        <VListItem>
          <h6 class="text-h6">
            Delivery Location:
            <span class="text-body-1 d-inline-block">
              {{ props.rfqData?.delivery_location }}
            </span>
          </h6>
        </VListItem>
        
        <VListItem>
          <h6 class="text-h6">
            Technical Specs:
            <span class="text-body-1 d-inline-block">
              {{ firstItem?.technical_specs }}
            </span>
          </h6>
        </VListItem>
        
        <VListItem>
          <h6 class="text-h6">
            Supporting Documents:
            <span class="text-body-1 d-inline-block ml-3">
              <DocumentImageViewer
                v-if="props.rfqData?.supporting_documents"
                :src="props.rfqData?.supporting_documents"
                :pdf-title="`Supporting Documents for RFQ - ${props.rfqData?.reference_no}`"
              />
            </span>
          </h6>
        </VListItem>
      
      </VList>

    </VCardText>
  </VCard>
</template>
