<template>
  <VForm ref="form" @submit.prevent="submitForm">
    <VCard>
      <VCardText>
        <h2 class="text-h5 mb-2">Edit Leave</h2>
        <p class="mb-6">Update leave request</p>
      </VCardText>

      <VRow class="ma-0 pa-0">
        <VCol cols="12" md="12">
          <VCardText>
            <h3 class="text-h6 mb-4">Leave Details</h3>
            <VRow>
              <VCol cols="12" md="4">
                <AppSelect v-model="formData.leave_type_id" label="Leave Type*" :items="leaveTypes" :rules="[requiredRule]" />
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
                <VFileInput v-model="formData.leave_attachment" label="Attachment (Leave empty to keep current)" prepend-icon="tabler-file" clearable density="comfortable" variant="outlined" />
                <small v-if="attachmentUrl" class="text-primary">Current: <a :href="attachmentUrl" target="_blank">View</a></small>
              </VCol>
              
            </VRow>
          </VCardText>
        </VCol>
        
      </VRow>

      <VCardActions class="justify-end px-6 pb-6">
        <VBtn color="primary" type="submit" :loading="isSubmitting">Update</VBtn>
        <VBtn color="secondary" variant="tonal" @click="goBack" :disabled="isSubmitting">Cancel</VBtn>
      </VCardActions>
    </VCard>
  </VForm>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const form = ref(null)
const isSubmitting = ref(false)
const accessToken = useCookie('accessToken')
const attachmentUrl = ref(null)

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

const fetchLeave = async () => {
  const { data } = await $api(`/leaves/${route.params.id}`, {
    headers: { Authorization: `Bearer ${accessToken.value}` },
  })
  Object.keys(formData.value).forEach(k => { if (data[k] !== undefined) formData.value[k] = data[k] })
  formData.value.leave_attachment = null
  attachmentUrl.value = data.leave_attachment_url
}

onMounted(async () => {
  await Promise.all([fetchLeaveTypes(), fetchLeave()])
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

    body.append('_method', 'PUT') // Laravel will treat this as a PUT

    // Ensure employee_id is enforced from logged-in user data
    const userData = useCookie('userData').value
    if (userData?.employee?.id) body.append('employee_id', userData.employee.id)
    
    try {
      const response = await $api(`/leaves/${route.params.id}`, {
        method: 'POST',
        body,
        headers: { Authorization: `Bearer ${accessToken.value}` },
      })

      $toast.success(response?.message || 'Leave updated successfully')
      router.push({ name: 'hrm-leave-list' })

    } catch (error) {
      let message = "Something went wrong!"
      // Handle validation errors
      if (error.response && error.response.status === 422) {
        message = Object.values(error.response?._data?.errors).join("\n")
      }
      $toast.error(message)
      return;
    }
  } finally {
    isSubmitting.value = false
  }
}

const goBack = () => router.push({ name: 'hrm-leave-list' })
</script>

<style scoped>
.border-e { border-inline-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }

@media (max-width: 959px) {
  .border-e { border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); border-inline-end: none; margin-block-end: 1.5rem; padding-block-end: 1.5rem; }
}
</style>


