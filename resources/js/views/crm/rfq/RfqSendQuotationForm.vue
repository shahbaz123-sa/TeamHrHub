<script setup>
import DocumentImageViewer from '@/components/common/DocumentImageViewer.vue'
import { watch } from 'vue'

const props = defineProps({
  rfqId: {
    type: [String, Number],
    required: true,
  },
  quotationData: {
    type: [Array, Object]
  },
  rfqStatus: {
    type: String
  },
  rfqData: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['refresh'])

const accessToken = useCookie('accessToken').value
const isSubmitting = ref(false)

const form = ref({
  procs: props.quotationData?.procs,
  due_date: props.quotationData?.due_date,
  price_per_unit: props.quotationData?.price_per_unit,
  total_price: props.quotationData?.total_price,
  invoice: null,
})

const handleSendQuotation = async () => {
  if (!form.value.procs || !form.value.due_date || !form.value.price_per_unit) {
    $toast.error('Please fill all required fields')
    return
  }

  const fd = new FormData()
  fd.append('procs', form.value.procs ?? "")
  fd.append('due_date', form.value.due_date ?? "")
  fd.append('price_per_unit', form.value.price_per_unit ?? "")
  fd.append('total_price', form.value.total_price ?? "")

  if (form.value.invoice) {
    fd.append('invoice', form.value.invoice)
  }

  isSubmitting.value = true
  try {
    await $api(`/rfqs/${props.rfqId}/send-quotation`, {
      method: 'POST',
      body: fd,
      headers: { Authorization: `Bearer ${accessToken}` },
    })
    $toast.success('Quotation sent successfully')
    emit('refresh')
  } catch (error) {

    let message = 'Failed to send quotation'

    if (error.status === 422) {
      message = Object.values(error._data?.errors).slice(0, 2).join("\n")
    }

    $toast.error(message)

  } finally {
    isSubmitting.value = false
  }
}

const calculateTotalPrice = () => {
  
  const quantity = props.rfqData?.item?.quantity || 0
  const pricePerUnit = parseFloat(form.value.price_per_unit) || 0
  form.value.total_price = (quantity * pricePerUnit).toFixed(2)
}

watch(() => form.value.price_per_unit, () => {
  calculateTotalPrice()
})

watch(() => props.quotationData, (newValue) => {

  form.value = {
    procs: newValue?.procs,
    due_date: newValue?.due_date,
    price_per_unit: newValue?.price_per_unit,
    total_price: newValue?.total_price,
    invoice: null,
  }

}, {deep: true, immediate: true})

</script>

<template>
  <VCard>
    <VCardTitle>Please attach quotation</VCardTitle>
    <VCardText>
      <VRow>
        <VCol cols="6">
          <AppTextField v-model="form.procs" label="Quotation Title *" :readonly="props.rfqStatus !== 'pending'" />
        </VCol>
        <VCol cols="6">
          <AppDateTimePicker v-model="form.due_date" label="Due Date *" :readonly="props.rfqStatus !== 'pending'" />
        </VCol>
        <VCol cols="4">
          <AppTextField v-model="form.price_per_unit" label="Price per unit *" :readonly="props.rfqStatus !== 'pending'" />
        </VCol>
        <VCol cols="2">
          <AppTextField :value="'x ' + props.rfqData?.item?.quantity" label="Quantity" :readonly="props.rfqStatus !== 'pending'" />
        </VCol>
        <VCol cols="6">
          <AppTextField v-model="form.total_price" label="Total Price" :readonly="props.rfqStatus !== 'pending'" />
        </VCol>
        <VCol cols="12">
          <VFileInput v-if="props.rfqStatus === 'pending'" v-model="form.invoice" label="Attach Invoice" />
          <DocumentImageViewer v-else-if="props.rfqStatus !== 'pending' && props.quotationData?.invoice" :src="props.quotationData?.invoice" />
        </VCol>
      </VRow>
    </VCardText>
    <VCardActions class="pl-6">
      <VBtn
        v-if="props.rfqStatus === 'pending'"
        :loading="isSubmitting"
        color="primary"
        variant="elevated"
        @click="handleSendQuotation"
      >
        Send Quotation
      </VBtn>
    </VCardActions>
  </VCard>
</template>
