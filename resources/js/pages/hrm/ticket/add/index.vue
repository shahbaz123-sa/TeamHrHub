<template>
  <VForm ref="form" @submit.prevent="submitForm">
    <VCard>
      <VCardText>
        <h2 class="text-h5 mb-2">Add Leave</h2>
        <p class="mb-6">Create a new leave request</p>
      </VCardText>

      <VRow class="ma-0 pa-0">
        <VCol cols="12" md="12">
          <VCardText>
            <h3 class="text-h6 mb-4">Leave Details</h3>
            <VRow>
              <VCol cols="12" md="4">
                <AppSelect
                  v-model="formData.leave_type_id"
                  label="Leave Type*"
                  :items="leaveTypes" 
                  item-title="title"
                  item-value="value"
                  :rules="[requiredRule]" />
              </VCol>
              <VCol cols="12" md="4">
                <AppDateTimePicker v-model="formData.start_date" label="Start Date*" :rules="[requiredRule]" />
              </VCol>
              <VCol cols="12" md="4">
                <AppDateTimePicker v-model="formData.end_date" label="End Date*" :rules="[requiredRule]" />
              </VCol>
              <VCol cols="12">
                <AppTextarea v-model="formData.leave_reason" label="Reason" rows="3" />
              </VCol>
              <VCol cols="12">
                <VFileInput v-model="formData.leave_attachment" label="Attachment" prepend-icon="tabler-file" clearable density="comfortable" variant="outlined" />
              </VCol>
              
            </VRow>
          </VCardText>
        </VCol>
        
      </VRow>

      <VCardActions class="justify-end px-6 pb-6">
        <VBtn color="primary" type="submit" :loading="isSubmitting">Submit</VBtn>
        <VBtn color="secondary" variant="tonal" @click="resetForm" :disabled="isSubmitting">Cancel</VBtn>
      </VCardActions>
    </VCard>
  </VForm>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const form = ref(null)
const isSubmitting = ref(false)
const accessToken = useCookie('accessToken')

const requiredRule = value => !!value || 'Required'

const leaveTypes = ref([])

const formData = ref({
  leave_type_id: null,
  start_date: '',
  end_date: '',
  leave_reason: '',
  leave_attachment: null,
  
})

const fetchLeaveTypes = async () => {
  const { data } = await $api('/leave-types', {
    headers: { Authorization: `Bearer ${accessToken.value}` },
  })
  leaveTypes.value = (data || []).map(t => ({ title: t.name, value: t.id }))
}

onMounted(async () => {
  await Promise.all([fetchLeaveTypes()])
})

const submitForm = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  isSubmitting.value = true
  try {
    const body = new FormData()
    Object.entries(formData.value).forEach(([k, v]) => {
      if (v !== null && v !== undefined && v !== '') body.append(k, v)
    })
    // Ensure employee_id is set from logged-in user data
    const userData = useCookie('userData').value
    if (userData?.employee?.id) body.append('employee_id', userData.employee.id)

    try {
      await $api('/leaves', {
        method: 'POST',
        body,
        headers: { Authorization: `Bearer ${accessToken.value}` },
      })

      $toast.success('Leave created successfully')
      router.push({ name: 'hrm-leave-list' })
      
    } catch (error) {
      let message = "Something went wrong!"
      // Handle validation errors
      if (error.response && error.response.status === 201) {
        $toast.success('Leave created successfully')
        router.push({ name: 'hrm-leave-list' })
      }
      
      if (error.response && error.response.status === 422) {
        message = Object.values(error.response?._data?.errors).join("\n")
      } else if (error.response && error.response._data?.message) {
        // Handle server errors (like quota validation) - check message first
        message = error.response._data.message
      } else if (error.response && error.response._data?.error) {
        // Handle other error formats
        message = error.response._data.error
      }

      $toast.error(message)
      return;
    }

  } finally {
    isSubmitting.value = false
  }
}

const resetForm = () => {
  form.value.reset()
}
</script>

<style scoped>
.border-e { border-inline-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }

@media (max-width: 959px) {
  .border-e {
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-inline-end: none;
    margin-block-end: 1.5rem;
    padding-block-end: 1.5rem;
  }
}
</style>
