<script setup>
import RfqDetailsPanel from '@/views/crm/rfq/RfqDetailsPanel.vue'
import RfqAssignForm from '@/views/crm/rfq/RfqAssignForm.vue'
import RfqSendQuotationForm from '@/views/crm/rfq/RfqSendQuotationForm.vue'
import { onMounted } from 'vue'

const route = useRoute()
const rfqData = ref(null)
const isLoading = ref(false)

const fetchRfq = async () => {
  isLoading.value = true
  try {
    const { data } = await $api(`/rfqs/${route.params.id}`, {
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
      <VCol cols="12" md="5" lg="4">
        <RfqDetailsPanel :rfq-data="rfqData" />
      </VCol>
      <VCol cols="12" md="7" lg="8">
        <RfqAssignForm
          :rfq-id="route.params.id"
          :assigned-to="rfqData?.assigned_to"
          :rfq-status="rfqData?.status"
          @refresh="fetchRfq"
        />
        
        <RfqSendQuotationForm
          :rfq-id="route.params.id"
          :quotation-data="rfqData?.quotation"
          :rfq-status="rfqData?.status"
          :rfq-data="rfqData"
          @refresh="fetchRfq"
        />
      </VCol>
    </VRow>
    <div v-else class="text-center">
      <VProgressCircular indeterminate size="64" />
    </div>
  </div>
</template>
