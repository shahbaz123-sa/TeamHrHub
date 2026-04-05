<template>
  <VDialog v-model="isOpen" max-width="500" persistent>
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center pa-6">
        <span class="text-h5">{{ isEdit ? 'Edit Deduction' : 'Create Deduction' }}</span>
        <VBtn icon variant="text" color="default" size="small" @click="closeModal">
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-6">
        <VForm ref="formRef" @submit.prevent="handleSubmit">
          <VRow>
            <!-- Deduction Options -->
            <VCol cols="12">
              <AppSelect
                v-model="formData.deduction_id"
                :items="deductionOptions"
                item-title="name"
                item-value="id"
                placeholder="Select Deduction Option"
                variant="outlined"
                label="Deduction Options*"
                :rules="[v => !!v || 'Deduction option is required']"
                :loading="loadingDeductions"
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
        <VBtn variant="outlined" color="secondary" @click="closeModal">
          Cancel
        </VBtn>
        <VBtn color="primary" :loading="loading" @click="handleSubmit">
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
  modelValue: { type: Boolean, default: false },
  employeeId: { type: [String, Number], required: true },
  deductionData: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue', 'saved', 'updated'])

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.deductionData)

// Reactive data
const loading = ref(false)
const loadingDeductions = ref(false)
const formRef = ref()
const deductionOptions = ref([])
const typeOptions = AllowanceTypeOptions // Uses the same constants as allowances

const formData = ref({
  deduction_id: null,
  title: '',
  type: null,
  amount: ''
})

const resetForm = () => {
  formData.value = {
    deduction_id: null,
    title: '',
    type: null,
    amount: ''
  }
}

// Watch for deduction data changes to populate form for editing
watch(() => props.deductionData, (newData) => {
  if (newData && isEdit.value) {
    formData.value = {
      deduction_id: newData.deduction_id || null,
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
  const numericValue = value.replace(/[^0-9.]/g, '')
  formData.value.amount = numericValue
}

const fetchDeductionOptions = async () => {
  try {
    loadingDeductions.value = true
    const result = await $api('/deductions')
    deductionOptions.value = result.data || []
  } catch (error) {
    console.error('Failed to fetch deduction options:', error)
  } finally {
    loadingDeductions.value = false
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
      deduction_id: formData.value.deduction_id,
      title: formData.value.title,
      type: formData.value.type,
      amount: formData.value.amount ? parseFloat(formData.value.amount) : null
    }

    if (isEdit.value) {
      // Update existing deduction
      const result = await $api(`/employee-deductions/${props.deductionData.id}`, {
        method: 'PUT',
        body: payload,
      });
      emit('updated', result.data);
      $toast.success('Deduction updated successfully');
    } else {
      // Create new deduction
      const result = await $api('/employee-deductions', {
        method: 'POST',
        body: payload,
      });
      emit('saved', result.data);
      $toast.success('Deduction created successfully');
    }
    
    closeModal()
  } catch (error) {
    console.error('Error saving deduction:', error)
    $toast.error(isEdit.value ? 'Failed to update deduction' : 'Failed to create deduction')
  } finally {
    loading.value = false
  }
}

// Watch for modal opening to fetch deduction options
watch(() => props.modelValue, (val) => {
  if (val) {
    fetchDeductionOptions()
  }
})
</script>
