<template>
  <VDialog v-model="localOpen" max-width="800">
    <VCard :title="'Leave Action'">
      <VDivider />
      <VCardText>
        <VForm ref="managerDecisionFormRef" @submit.prevent>
          <VRow>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Employee Name</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.employee?.name }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Leave Type</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.leave_type?.name }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Applied On</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.applied_on_timestamp }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Start Date</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.start_date }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>End Date</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.end_date }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Leave Reason</h5>
                </VCol>
                <VCol cols="6" md="7" class="pa-2">
                  <span>{{ selectedLeave?.leave_reason }}</span>
                </VCol>
              </VRow>
            </VCol>
            <VCol cols="12" md="6" class="mt-2">
              <VRow>
                <VCol cols="6" md="5" class="pa-2">
                  <h5>Leave Attachment</h5>
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
          </VRow>
        </VForm>
      </VCardText>
      <VCardActions class="justify-end">
        <VBtn color="outlined" @click="localOpen = false" variant="plain">Cancel</VBtn>
        <VBtn color="error" variant="outlined" :loading="managerDecisionSubmitting" @click="submitDecision('reject')">{{ 'Reject' }}</VBtn>
        <VBtn color="success" variant="flat" :loading="managerDecisionSubmitting" @click="submitDecision('approve')">{{ 'Approve' }}</VBtn>
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

const managerDecisionFormRef = ref()
const managerDecisionSubmitting = ref(false)

const alert = ref({})

const resetAlert = () => {
  alert.value = { show: false, title: '', message: '', color: '' }
}

watch(
  () => [props.leave, props.modelValue],
  () => {
    selectedLeave.value = props.leave
    if (props.modelValue && props.leave) {
      resetAlert()
    }
  },
  { immediate: true, deep: false }
)

const submitDecision = async mode => {
  if (!selectedLeave.value) return
  managerDecisionSubmitting.value = true
  try {
    resetAlert()
    const body = new FormData()
    body.append('manager_status', mode)

    if (mode === 'approve' || mode === 'reject') {
      try {
        const response = await $api(`/leaves/${selectedLeave.value.id}/manager/approve-reject`, {
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
    managerDecisionSubmitting.value = false
  }
}

onMounted(() => {
  resetAlert()
})
</script>

<style scoped>
.leave-dialog-radio :deep(label.v-label) { border: none !important; }
</style>


