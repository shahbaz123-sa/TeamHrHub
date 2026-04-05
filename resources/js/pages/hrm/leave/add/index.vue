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
                  :rules="[requiredRule]"
                  @update:model-value="onLeaveTypeChange" />
              </VCol>
              
              <!-- Duration Type - Only show if leave type is selected -->
              <VCol v-if="formData.leave_type_id" cols="12" md="4">
                <AppSelect
                  v-model="formData.duration_type"
                  label="Duration Type*"
                  :items="durationTypeOptions"
                  item-title="title"
                  item-value="value"
                  :rules="[requiredRule]"
                  @update:model-value="onDurationTypeChange" />
              </VCol>
              
              <!-- Date Selection - Only show if duration type is selected -->
              <template v-if="formData.duration_type">
                <VCol cols="12" md="4">
                  <AppDateTimePicker 
                    v-model="formData.start_date" 
                    label="Start Date*" 
                    :rules="[requiredRule]"
                    @update:model-value="onStartDateChange" />
                </VCol>
                
                <!-- End Date - Show different labels based on duration type -->
                <VCol cols="12" md="4">
                  <AppDateTimePicker 
                    v-model="formData.end_date" 
                    :label="endDateLabel" 
                    :rules="[requiredRule, endDateValidationRule]"
                    :disabled="isEndDateDisabled" />
                </VCol>
              </template>
              
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
import { endDateRule } from '@/utils/form/validation'
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const form = ref(null)
const isSubmitting = ref(false)
const accessToken = useCookie('accessToken')

const requiredRule = value => !!value || 'Required'

// Utility function to extract error message from different error structures
const extractErrorMessage = (error) => {
  // Handle success as error (weird pattern)
  if (error.response?.status === 201) {
    return { isSuccess: true, message: 'Leave created successfully' }
  }
  
  // Extract data from different error structures
  const data = error.response?._data || error._data
  
  // Handle validation errors (422)
  if (error.response?.status === 422 || error.status === 422) {
    if (data?.errors) {
      return { message: Object.values(data.errors).flat().slice(0, 2).join("\n") }
    }
  }
  
  // Return server message or fallback
  return { 
    message: data?.message || data?.error || error.message || "Something went wrong!"
  }
}

const leaveTypes = ref([])

// Duration type options
const durationTypeOptions = ref([
  { title: 'Full Day', value: 1 },
  { title: 'Half Day', value: 2 },
  { title: 'Short Leave', value: 3 }
])

const formData = ref({
  leave_type_id: null,
  start_date: '',
  end_date: '',
  duration_type: null, // Reset to null for better UX flow
  leave_reason: '',
  leave_attachment: null,
  
})

// Computed validation rule for end date based on duration type
const endDateValidationRule = computed(() => {
  return (value) => endDateRule(value, formData.value.start_date, formData.value.duration_type);
})

// Computed properties for better UX
const endDateLabel = computed(() => {
  switch (formData.value.duration_type) {
    case 1: // Full Day
      return 'End Date*';
    case 2: // Half Day
      return 'Date* (Same as Start Date)';
    case 3: // Short Leave
      return 'Date* (Same as Start Date)';
    default:
      return 'End Date*';
  }
})

const isEndDateDisabled = computed(() => {
  return formData.value.duration_type === 2 || formData.value.duration_type === 3;
})

// Event handlers for improved UX flow
const onLeaveTypeChange = (leaveTypeId) => {
  // Reset duration type and dates when leave type changes
  formData.value.duration_type = null;
  formData.value.start_date = '';
  formData.value.end_date = '';
}

const onDurationTypeChange = (durationType) => {
  // Reset dates when duration type changes
  formData.value.start_date = '';
  formData.value.end_date = '';
}

const onStartDateChange = (startDate) => {
  // For Half Day and Short Leave, automatically set end date to start date
  if (formData.value.duration_type === 2 || formData.value.duration_type === 3) {
    formData.value.end_date = startDate;
  }
}

// Watch for duration type changes to auto-adjust end date
watch(() => formData.value.duration_type, (newDurationType) => {
  if (formData.value.start_date && formData.value.end_date) {
    const startDate = new Date(formData.value.start_date);
    const endDate = new Date(formData.value.end_date);
    
    // If Half Day or Short Leave is selected and dates span multiple days, adjust end date to start date
    if ((newDurationType === 2 || newDurationType === 3) && startDate.getTime() !== endDate.getTime()) {
      formData.value.end_date = formData.value.start_date;
    }
  }
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
      const errorResult = extractErrorMessage(error)
      
      if (errorResult.isSuccess) {
        $toast.success(errorResult.message)
        router.push({ name: 'hrm-leave-list' })
        return
      }
      
      $toast.error(errorResult.message)
      return
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
