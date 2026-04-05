<template>
  <VDialog
    v-model="isOpen"
    max-width="500"
    persistent
  >
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center pa-6">
        <span class="text-h5">{{ isEdit ? 'Edit Allowance' : 'Create Allowance' }}</span>
        <VBtn
          icon
          variant="text"
          color="default"
          size="small"
          @click="closeModal"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>
      
      <VDivider />
      
      <VCardText class="pa-6">
        <VForm ref="formRef" @submit.prevent="handleSubmit">
          <VRow>
            <!-- Allowance Options -->
            <VCol cols="12">
             
              <AppSelect
                v-model="formData.allowance_id"
                :items="allowanceOptions"
                item-title="name"
                item-value="id"
                placeholder="Select Allowance Option"
                variant="outlined"
                label="Allowance Options*"
                :rules="[v => !!v || 'Allowance option is required']"
                :loading="loadingAllowances"
              />
            </VCol>
            
            <!-- Title -->
            <VCol cols="12">
              
               <AppTextField
                  v-model="formData.title"
                  label="Title"
                  placeholder="Enter title"
                />
            </VCol>
            
            <!-- Type -->
            <VCol cols="12">
              
              <AppSelect
                v-model="formData.type"
                :items="typeOptions"
                label="Type*"
                placeholder="Select Type"
                variant="outlined"
                :rules="[v => !!v || 'Type is required']"
              />
              
            </VCol>
            
            <!-- Amount -->
            <VCol cols="12">
              <AppTextField
                v-model="formData.amount"
                type="number"
                placeholder="Enter amount"
                variant="outlined"
                :rules="[
                  v => !v || v >= 0 || 'Amount must be greater than or equal to 0'
                ]"
                @input="formatAmount"
                label="Amount"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      
      <VDivider />
      
      <VCardActions class="pa-6">
        <VSpacer />
        <VBtn
          variant="outlined"
          color="secondary"
          @click="closeModal"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          @click="handleSubmit"
        >
          {{ isEdit ? 'Update Changes' : 'Save Changes' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { AllowanceTypeOptions } from '@/constants/hrm/constants'
import { computed, ref, watch } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  employeeId: {
    type: [String, Number],
    required: true
  },
  allowanceData: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'saved', 'updated'])

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.allowanceData)

const loading = ref(false)
const loadingAllowances = ref(false)
const formRef = ref()

// Data
const allowanceOptions = ref([])
const typeOptions = AllowanceTypeOptions

const formData = ref({
  allowance_id: null,
  title: '',
  type: null,
  amount: ''
})

const resetForm = () => {
  formData.value = {
    allowance_id: null,
    title: '',
    type: null,
    amount: ''
  }
}

// Watch for allowance data changes to populate form for editing
watch(() => props.allowanceData, (newData) => {
  if (newData && isEdit.value) {
    formData.value = {
      allowance_id: newData.allowance_id || null,
      title: newData.title || '',
      type: newData.type || null,
      amount: newData.amount || ''
    }
  } else {
    resetForm()
  }
}, { immediate: true })

const formatAmount = (event) => {
  const value = event.target.value
  // Remove any non-numeric characters except decimal point
  const numericValue = value.replace(/[^0-9.]/g, '')
  formData.value.amount = numericValue
}

// Fetch Allowance Options
const fetchAllowanceOptions = async () => {
  try {
    loadingAllowances.value = true
    const result = await $api('/allowances')
    allowanceOptions.value = result.data || []
  } catch (error) {
    console.error('Failed to fetch allowance options:', error)
  } finally {
    loadingAllowances.value = false
  }
}

const closeModal = () => {
  isOpen.value = false
  resetForm()
  if (formRef.value) {
    formRef.value.resetValidation()
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  
  const { valid } = await formRef.value.validate()
  if (!valid) return

  loading.value = true
  
  try {
    const payload = {
      employee_id: props.employeeId,
      allowance_id: formData.value.allowance_id,
      title: formData.value.title,
      type: formData.value.type,
      amount: formData.value.amount ? parseFloat(formData.value.amount) : null
    }

    if (isEdit.value) {
      // Update existing allowance
      const result = await $api(`/employee-allowances/${props.allowanceData.id}`, {
        method: 'PUT',
        body: payload,
      });
      
      emit('updated', result.data);
      $toast.success('Allowance updated successfully');
    } else {
      // Create new allowance
      const result = await $api('/employee-allowances', {
        method: 'POST',
        body: payload,
      });
      
      emit('saved', result.data);
      $toast.success('Allowance created successfully');
    }
    
    closeModal()
  } catch (error) {
    console.error('Error saving allowance:', error)
    $toast.error(isEdit.value ? 'Failed to update allowance' : 'Failed to create allowance')
  } finally {
    loading.value = false
  }
}

// Watch for modal opening to fetch allowance options
watch(() => props.modelValue, (val) => {
  if (val) {
    fetchAllowanceOptions()
  }
})
</script>
