<template>
  <VDialog 
    v-model="isOpen" 
    :max-width="$vuetify.display.mobile ? '95vw' : '800px'" 
    :fullscreen="$vuetify.display.mobile"
    persistent
  >
    <VCard>
      <VCardTitle class="d-flex justify-center align-center pa-6 position-relative">
        <h2 v-if="props.mode === 'create'">Create Leave</h2>
        <h2 v-else-if="props.mode === 'edit'">Update Leave</h2>
        <h2 v-else-if="props.mode === 'view'">View Leave</h2>
        <h2 v-else>Leave</h2>
        <VBtn icon="tabler-x" variant="text" @click="closeModal" class="position-absolute" style="inset-inline-end: 24px;" />
      </VCardTitle>

      <VDivider />
      <!-- Leave Type Summary Cards -->
      <VCardText class="pa-6">
        <div v-if="isLoadingBalance" class="leave-cards-container mb-6">
          <VCard 
            v-for="n in 3" 
            :key="n" 
            class="leave-balance-card"
            elevation="2"
          >
            <VCardText class="pa-4">
              <VSkeletonLoader type="text" class="mb-3" />
              <VSkeletonLoader type="text" class="mb-1" />
              <VSkeletonLoader type="text" />
            </VCardText>
          </VCard>
        </div>
        <div v-else class="leave-cards-container mb-6">
          <VCard 
            v-for="leaveType in leaveTypeSummary"
            :key="leaveType.id" 
            class="leave-balance-card"
            elevation="2"
          >
            <VCardText class="pa-4">
              <h4 class="text-h6 mb-3 text-high-emphasis">{{ leaveType.name }}</h4>
              <div class="text-h4 mb-1">
                {{ leaveType.used || 0 }} / {{ leaveType.total || 0 }}
              </div>
              <!-- <div class="text-caption text-medium-emphasis">
                {{ leaveType.remaining || leaveType.total || 0 }} remaining
              </div> -->
            </VCardText>
          </VCard>
        </div>

        <VForm ref="form" @submit.prevent="submitForm">
          <VRow>
            <VCol cols="12" md="6">
              <AppSelect
                v-model="formData.leave_type_id"
                label="Leave Type*"
                :items="leaveTypeSummary"
                item-title="name"
                item-value="id"
                :rules="[requiredRule]"
                :readonly="props.mode === 'view'"
                @update:model-value="onLeaveTypeChange" />
            </VCol>
            
            <!-- Duration Type - Only show if leave type is selected -->
            <VCol v-if="formData.leave_type_id" cols="12" md="6">
              <AppSelect
                v-model="formData.duration_type"
                label="Duration Type*"
                :items="durationTypeOptions" 
                item-title="title"
                item-value="value"
                :rules="[requiredRule]"
                :readonly="props.mode === 'view'"
                @update:model-value="onDurationTypeChange" />
            </VCol>
            
            <!-- Date Selection - Only show if duration type is selected -->
            <template v-if="formData.duration_type">
              <VCol cols="12" md="6">
                <AppDateTimePicker 
                  v-model="formData.start_date" 
                  label="Start Date*" 
                  :rules="[requiredRule]"
                  :readonly="props.mode === 'view'"
                  @update:model-value="onStartDateChange" />
              </VCol>
              
              <!-- End Date - Show different labels based on duration type -->
              <VCol cols="12" md="6">
                <AppDateTimePicker 
                  v-model="formData.end_date" 
                  :label="endDateLabel" 
                  :rules="[requiredRule, endDateValidationRule]"
                  :readonly="props.mode === 'view'"
                  :disabled="isEndDateDisabled" />
              </VCol>
            </template>
            <VCol cols="12" v-if="props.mode === 'view'">
              <AppDateTimePicker
                v-model="leaveData.applied_on"
                label="Applied On"
                readonly
                @update:model-value="onStartDateChange" />
            </VCol>
            <VCol cols="12">
              <AppTextarea 
                v-model="formData.leave_reason" 
                label="Description" 
                placeholder="Leave Description"
                :readonly="props.mode === 'view'"
                rows="3" />
            </VCol>
            <VCol cols="12">
              <VFileInput
                v-model="formData.leave_attachment"
                label="Attachment"
                prepend-icon="tabler-file"
                clearable
                density="comfortable"
                :readonly="props.mode === 'view'"
                variant="outlined" />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>

      <VDivider />

      <VCardActions class="justify-end pa-6">
        <VBtn color="secondary" variant="tonal" @click="closeModal" :disabled="isSubmitting">
          {{ props.mode === 'view' ? 'Close' : 'Cancel' }}
        </VBtn>
        <VBtn color="primary" @click="submitForm" :loading="isSubmitting" v-if="props.mode !== 'view'">
          {{ isEdit ? 'Update Changes' : 'Save Changes' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { endDateRule } from '@/utils/form/validation';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  leaveData: {
    type: Object,
    default: null
  },
  mode:{
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'submitted'])

const form = ref(null)
const isSubmitting = ref(false)
const accessToken = useCookie('accessToken')

const requiredRule = value => !!value || 'Required'

// Utility function to extract error message from different error structures
const extractErrorMessage = (error) => {
  // Handle success as error (weird pattern)
  if (error.response?.status === 201) {
    return { isSuccess: true, message: isEdit.value ? 'Leave updated successfully' : 'Leave created successfully' }
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

const leaveTypeSummary = ref([])
const isLoadingBalance = ref(false)

// Duration type options
const durationTypeOptions = ref([
  { title: 'Full Day', value: 1 },
  { title: 'Half Day', value: 2 },
  { title: 'Short Leave', value: 3 }
])

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.leaveData)

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

const formData = ref({
  leave_type_id: null,
  start_date: '',
  end_date: '',
  duration_type: 1, // Default to Full Day
  leave_reason: '',
  leave_attachment: null,
})

// Define resetForm function before it's used in watch
const resetForm = () => {
  formData.value = {
    leave_type_id: null,
    start_date: '',
    end_date: '',
    duration_type: null, // Reset to null for better UX flow
    leave_reason: '',
    leave_attachment: null,
  }
  if (form.value) {
    form.value.reset()
  }
}

// Watch for leave data changes to populate form for editing
watch(() => props.leaveData, (newData) => {
  if (newData) {
    formData.value = {
      leave_type_id: newData.leave_type_id,
      start_date: newData.start_date,
      end_date: newData.end_date,
      duration_type: newData.duration_type || 1, // Default to Full Day if not set
      leave_reason: newData.leave_reason || '',
      leave_attachment: newData.leave_attachment || null, // Don't pre-populate file input
    }
  } else {
    // Reset form when no leave data (add mode)
    resetForm()
  }
}, { immediate: true })

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

const fetchLeaveTypeSummary = async () => {
  isLoadingBalance.value = true
  try {
    // Fetch leave balance summary for current user using the new API
    const userData = useCookie('userData').value
    
    if (userData?.employee_id) {
      // Add cache-busting to ensure fresh data
      const response = await $api(`/leaves/balance/${userData.employee_id}?t=${Date.now()}`);
      
      if (response && response.success) {
        // Transform the API response to match our component structure
        leaveTypeSummary.value = response.data
          .map(item => ({
            id: item.leave_type_id,
            name: item.leave_type_name,
            used: item.used || 0,
            total: item.quota || 0,
            remaining: item.remaining || item.quota || 0,
            percentage_used: item.percentage_used || 0
          }))
        
      } else {
        throw new Error('Invalid API response')
      }
    } else {
      // If no employee data, show default leave types
      throw new Error('No employee data found')
    }
  } catch (error) {
    console.error('Error fetching leave balance summary:', error)
    $toast.error('Failed to load leave balance summary')
  } finally {
    isLoadingBalance.value = false
  }
}

// Expose method to parent component
defineExpose({
  refreshLeaveBalance: fetchLeaveTypeSummary
})

const submitForm = async () => {
  const { valid } = await form.value.validate()
  if (!valid) return

  isSubmitting.value = true
  try {
    // Prepare data object
    const data = { ...formData.value }

    // Check if there's a file attachment
    const hasFileAttachment = data.leave_attachment && data.leave_attachment instanceof File

    if (hasFileAttachment) {
      // If there's a file attachment, send as FormData for both create and edit
      const body = new FormData()
      Object.entries(data).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') {
          body.append(k, v)
        }
      })
      
      if (isEdit.value) {
        // Update existing leave with file - send as FormData
        body.append('_method', 'PUT')
        await $api(`/leaves/${props.leaveData.id}`, {
          method: 'POST',
          body,
          headers: { Authorization: `Bearer ${accessToken.value}` },
        })
        $toast.success('Leave updated successfully')
      } else {
        // Create new leave with file - send as FormData
        await $api('/leaves', {
          method: 'POST',
          body,
          headers: { Authorization: `Bearer ${accessToken.value}` },
        })
        $toast.success('Leave created successfully')
      }
    } else {
      // No file attachment, send as JSON for both create and edit
      if (isEdit.value) {
        // Update existing leave without file - send as JSON
        await $api(`/leaves/${props.leaveData.id}`, {
          method: 'PUT',
          body: data,
          headers: { 
            Authorization: `Bearer ${accessToken.value}`,
            'Content-Type': 'application/json'
          },
        })
        $toast.success('Leave updated successfully')
      } else {
        // Create new leave without file - send as JSON
        await $api('/leaves', {
          method: 'POST',
          body: data,
          headers: { 
            Authorization: `Bearer ${accessToken.value}`,
            'Content-Type': 'application/json'
          },
        })
        $toast.success('Leave created successfully')
      }
    }

    emit('submitted')
    // Refresh leave balance after successful submission
    await fetchLeaveTypeSummary()
    closeModal()
    
  } catch (error) {
    const errorResult = extractErrorMessage(error)
    
    if (errorResult.isSuccess) {
      $toast.success(errorResult.message)
      emit('submitted')
      closeModal()
      return
    }
    
    $toast.error(errorResult.message)
  } finally {
    isSubmitting.value = false
  }
}

const closeModal = () => {
  isOpen.value = false
  // Only reset form if not in edit mode
  if (!isEdit.value) {
    resetForm()
  }
}

// Watch for modal opening to fetch latest data and load form data
watch(() => props.modelValue, async (newValue, oldValue) => {
  if (newValue && !oldValue) {
    // Modal is opening, fetch latest leave balance data
    await fetchLeaveTypeSummary()
    
    // If in edit mode, ensure form data is loaded
    if (isEdit.value && props.leaveData) {
      formData.value = {
        leave_type_id: props.leaveData.leave_type_id,
        start_date: props.leaveData.start_date,
        end_date: props.leaveData.end_date,
        duration_type: props.leaveData.duration_type || 1,
        leave_reason: props.leaveData.leave_reason || '',
        leave_attachment: props.leaveData.leave_attachment || null,
      }
    }
  } else if (!newValue && oldValue) {
    // Modal is closing, reset form after a short delay to allow for proper cleanup
    setTimeout(() => {
      resetForm()
    }, 100)
  }
}, { immediate: false })

onMounted(async () => {
  await Promise.all([
    fetchLeaveTypeSummary()
  ])
})
</script>

<style scoped>
/* Leave Cards Container - Responsive Grid */
.leave-cards-container {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(3, 1fr);
}

.leave-balance-card {
  display: flex;
  flex-direction: column;
  justify-content: center;
  border: 1px solid rgba(var(--v-theme-outline), 0.12);
  border-radius: 12px;
  min-block-size: 120px;
  text-align: center;
  transition: all 0.2s ease-in-out;
}

.leave-balance-card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 10%);
  transform: translateY(-2px);
}

/* Mobile Responsive Adjustments */
@media (max-width: 960px) {
  .leave-cards-container {
    gap: 0.75rem;
    grid-template-columns: repeat(2, 1fr);
  }

  .leave-balance-card {
    min-block-size: 100px;
  }

  .leave-balance-card .v-card-text {
    padding: 12px;
  }

  .leave-balance-card h4 {
    font-size: 0.875rem;
    margin-block-end: 8px;
  }

  .leave-balance-card .text-h4 {
    font-size: 1.25rem;
  }
}

@media (max-width: 600px) {
  .leave-cards-container {
    gap: 0.5rem;
    grid-template-columns: 1fr;
  }

  .leave-balance-card {
    min-block-size: 80px;
  }

  .leave-balance-card .v-card-text {
    padding: 8px;
  }

  .leave-balance-card h4 {
    font-size: 0.75rem;
    margin-block-end: 6px;
  }

  .leave-balance-card .text-h4 {
    font-size: 1rem;
  }
}

/* Modal Mobile Improvements */
@media (max-width: 600px) {
  .v-dialog--fullscreen .v-card {
    border-radius: 0;
  }

  .v-dialog--fullscreen .v-card__title {
    padding: 16px;
    border-block-end: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .v-dialog--fullscreen .v-card__text {
    padding: 16px;
  }

  .v-dialog--fullscreen .v-card__actions {
    padding: 16px;
    border-block-start: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  }

  .v-dialog--fullscreen .v-card__title h2 {
    font-size: 1.25rem;
  }
}

/* Form Mobile Improvements */
@media (max-width: 600px) {
  .v-form .v-row {
    margin: 0;
  }

  .v-form .v-col {
    padding: 4px;
  }
}

.flex-1 {
  flex: 1;
}

.leave-balance-card .v-card-text {
  padding: 1.5rem;
}

.leave-balance-card h4 {
  color: rgba(var(--v-theme-on-surface), 0.87);
  font-weight: 500;
}

.leave-balance-card .text-h4 {
  font-size: 16px !important;
  font-weight: 500;
  line-height: 1.2;
}
</style>
