<template>
  <VDialog v-model="localOpen" max-width="800">
    <VCard :title="'Leave Action'">
      <VDivider />
      <VCardText>
        <VForm ref="decisionFormRef" @submit.prevent>
          <VRow>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Employee Name:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.employee?.name }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Leave Type:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.leave_type?.name }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Applied On:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.applied_on_timestamp }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Start Date:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.start_date }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>End Date:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.end_date }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Leave Reason:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.leave_reason }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Line Manager Approval:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.manager_status }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Leave Attachment:</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <VBtn
                    v-if="selectedLeave?.leave_attachment_url || selectedLeave?.leave_attachment"
                    :href="selectedLeave?.leave_attachment_url || selectedLeave?.leave_attachment"
                    target="_blank"
                    download
                    variant="tonal"
                    icon="tabler-download"
                  />
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="2" md="2"></VCol>
            <VCol cols="8" md="8">
              <CustomRadios
                v-model:selectedRadio="decisionForm.is_paid"
                class="leave-dialog-radio"
                :radio-content="leaveOptions"
                :grid-column="{ cols: '6' }"
              >
              </CustomRadios>
            </VCol>
            <VCol cols="2" md="2"></VCol>
            <template v-if="decisionForm.is_paid === 'false'">
              <VCol cols="1" md="1"></VCol>
              <VCol cols="5" md="5">
                <AppTextField v-model="decisionForm.total_paid_days" label="Add Paid Leaves" type="number" min="0" />
              </VCol>
              <VCol cols="5" md="5">
                <AppTextField v-model="decisionForm.total_unpaid_days" label="Add Unpaid Leaves" type="number" min="0" />
              </VCol>
              <VCol cols="1" md="1"></VCol>
              <VCol cols="12" md="6">
                <AppDateTimePicker v-model="decisionForm.paid_start_date" label="Paid Start Date" />
              </VCol>
              <VCol cols="12" md="6">
                <AppDateTimePicker v-model="decisionForm.paid_end_date" label="Paid End Date" />
              </VCol>
              <VCol cols="12" md="6">
                <AppDateTimePicker v-model="decisionForm.unpaid_start_date" label="Unpaid Start Date" />
              </VCol>
              <VCol cols="12" md="6">
                <AppDateTimePicker v-model="decisionForm.unpaid_end_date" label="Unpaid End Date" />
              </VCol>
            </template>
            <VCol cols="12" md="12">
              <VAlert
                v-if="alert.show"
                :title="alert.title"
                :text="alert.message"
                variant="tonal"
                :color="alert.color"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn color="outlined" @click="localOpen = false" variant="plain">Cancel</VBtn>
        <VBtn color="error" variant="outlined" :loading="decisionSubmitting" @click="submitDecision('reject')">{{ 'Reject' }}</VBtn>
        <VBtn color="success" variant="flat" :loading="decisionSubmitting" @click="submitDecision('approve')">{{ 'Approve' }}</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  leave: { type: Object, default: null },
})

const emit = defineEmits(['update:modelValue', 'submitted'])

const accessToken = useCookie('accessToken')

const localOpen = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
})

const selectedLeave = ref(props.leave)

const leaveOptions = [
  { icon: 'tabler-settings', title: 'Paid', value: 'true' },
  { icon: 'tabler-message', title: 'Unpaid', value: 'false' },
]

const decisionFormRef = ref()
const decisionSubmitting = ref(false)
const decisionForm = ref({
  is_paid: 'true',
  total_paid_days: '',
  total_unpaid_days: '',
  paid_start_date: '',
  paid_end_date: '',
  unpaid_start_date: '',
  unpaid_end_date: '',
})

const alert = ref({})

const resetAlert = () => {
  alert.value = { show: false, title: '', message: '', color: '' }
}

watch(
  () => [props.leave, props.modelValue],
  () => {
    selectedLeave.value = props.leave
    if (props.modelValue && props.leave) {
      decisionForm.value = {
        is_paid: 'true',
        total_paid_days: props.leave.total_paid_days ?? '',
        total_unpaid_days: props.leave.total_unpaid_days ?? '',
        paid_start_date: props.leave.paid_start_date ?? '',
        paid_end_date: props.leave.paid_end_date ?? '',
        unpaid_start_date: props.leave.unpaid_start_date ?? '',
        unpaid_end_date: props.leave.unpaid_end_date ?? '',
      }
      resetAlert()
    }
  },
  { immediate: true, deep: false }
)

const daysBetween = (startDate, endDate) => {
  if (!startDate || !endDate) return 0
  const start = new Date(startDate)
  const end = new Date(endDate)
  const diffTime = Math.abs(end - start)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays + 1
}

const submitDecision = async mode => {
  if (!selectedLeave.value) return
  decisionSubmitting.value = true
  try {
    resetAlert()
    const body = new FormData()
    const fields = [
      'is_paid',
      'total_paid_days',
      'total_unpaid_days',
      'paid_start_date',
      'paid_end_date',
      'unpaid_start_date',
      'unpaid_end_date',
    ]

    const totalPaidDays = parseInt(decisionForm.value.total_paid_days || 0)
    const totalUnpaidDays = parseInt(decisionForm.value.total_unpaid_days || 0)

    if (
      decisionForm.value.is_paid === 'false' &&
      totalPaidDays + totalUnpaidDays !==
        daysBetween(selectedLeave.value.start_date, selectedLeave.value.end_date)
    ) {
      alert.value = {
        show: true,
        title: 'Error',
        message: 'Paid/Unpaid leaves total should be equal to total leaves',
        color: 'error',
      }
      return
    }

    fields.forEach(k => {
      const v = decisionForm.value[k]
      if (v !== null && v !== undefined && v !== '') body.append(k, v)
    })

    body.append('applied_start_date', selectedLeave.value.start_date)
    body.append('applied_end_date', selectedLeave.value.end_date)
    body.append('hr_status', mode)

    if (mode === 'approve' || mode === 'reject') {
      try {
        const response = await $api(`/leaves/${selectedLeave.value.id}/hr/approve-reject`, {
          method: 'POST',
          body,
          headers: { Authorization: `Bearer ${accessToken.value}` },
        })

        emit('update:modelValue', false)
        emit('submitted')
        $toast.success(response?.message || 'Leave updated successfully')
      } catch (error) {
        let message = 'Something went wrong!'
        if (error.response && error.response.status === 422) {
          message = Object.values(error.response?._data?.errors).join('\n')
        }
        alert.value = { show: true, title: 'Error', message, color: 'error' }
        return
      }
    }
  } finally {
    decisionSubmitting.value = false
  }
}

onMounted(() => {
  resetAlert()
})
</script>

<style scoped>
.leave-dialog-radio :deep(label.v-label) { border: none !important; }
</style>


