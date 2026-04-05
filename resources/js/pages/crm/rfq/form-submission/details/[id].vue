<script setup>
import { humanize } from '@/utils/helpers/str'
import { onMounted } from 'vue'

const route = useRoute()
const rfqData = ref(null)
const isLoading = ref(false)

const fetchRfq = async () => {
  isLoading.value = true
  try {
    const { data } = await $api(`/rfq/form-submissions/${route.params.id}`, {
      method: 'GET',
    })
    rfqData.value = data
  } catch (error) {
    $toast.error('Failed to fetch RFQ details')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchRfq()
})
</script>

<template>
  <div>
    <VRow v-if="rfqData">
      <VCol cols="12" md="8" lg="9">
        <VCard>
          <VCardTitle class="mt-5">RFQ Details</VCardTitle>
          <VCardText class="mt-5">
            <VRow>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Email</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.email }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Phone Number</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.phone }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Commodity</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.commodity }}</h6>
                </div>
              </VCol>
            </VRow>
            <VDivider class="mt-7 mb-7" /><VRow>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Product</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.product_required }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Unit of Measure</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.uom_name }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Quantity</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.quantity }}</h6>
                </div>
              </VCol>
            </VRow>
            <VDivider class="mt-7 mb-7" />
            <VRow>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Payment Method</div>
                  <h6 class="text-h6 mt-1">{{ humanize(rfqData.payment_method) }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Preferred Delivery Date</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.preferred_date }}</h6>
                </div>
              </VCol>
              <VCol cols=4>
                <div>
                  <div class="text-body-2 text-muted">Delivery Destination</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.delivery_location }}</h6>
                </div>
              </VCol>
            </VRow>
            <VDivider class="mt-7 mb-7" />
            <VRow>
              <VCol cols=12>
                <div>
                  <div class="text-body-2 text-muted">Technical Specifications</div>
                  <h6 class="text-h6 mt-1">{{ rfqData.technical_specs }}</h6>
                </div>
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" md="4" lg="3"></VCol>
    </VRow>
    <div v-else class="text-center">
      <VProgressCircular indeterminate size="64" />
    </div>
  </div>
</template>
