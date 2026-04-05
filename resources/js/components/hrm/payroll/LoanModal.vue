<template>
  <VDialog v-model="isOpen" max-width="500" persistent>
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center pa-6">
        <span class="text-h5">{{ isEdit ? 'Edit Loan' : 'Create Loan' }}</span>
        <VBtn icon variant="text" color="default" size="small" @click="closeModal">
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-6">
        <VForm ref="formRef" @submit.prevent="handleSubmit">
          <VRow>
            <!-- Loan Options -->
            <VCol cols="12">
              <AppSelect
                v-model="formData.loan_id"
                :items="loanOptions"
                item-title="name"
                item-value="id"
                placeholder="Select Loan Option"
                variant="outlined"
                label="Loan Options*"
                :rules="[v => !!v || 'Loan option is required']"
                :loading="loadingLoans"
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
                label="Type"
                placeholder="Select Type"
                variant="outlined"
              />
            </VCol>
            <!-- Reasons -->
            <VCol cols="12">
              <AppTextField
                v-model="formData.reasons"
                label="Reasons"
                placeholder="Enter reasons for the loan"
                variant="outlined"
                type="textarea"
                rows="3"
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
                  v => !!v || 'Amount is required',
                  v => v >= 0 || 'Amount must be greater than or equal to 0'
                ]"
                @input="formatAmount"
                label="Amount*"
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
import { computed, ref, watch } from 'vue'

// Props
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  employeeId: { type: [String, Number], required: true },
  loanData: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue', 'saved', 'updated'])

// Computed
const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.loanData)

// Reactive data
const loading = ref(false)
const loadingLoans = ref(false)
const formRef = ref()
const loanOptions = ref([])

// Type options for loans (same as allowances)
const typeOptions = [
  { title: 'Fixed', value: 1 },
  // { title: 'Percentage', value: 2 },
  // { title: 'Variable', value: 3 },
]


const formData = ref({
  loan_id: null,
  title: '',
  type: null,
  reasons: '',
  amount: ''
})

const resetForm = () => {
  formData.value = {
    loan_id: null,
    title: '',
    type: null,
    reasons: '',
    amount: ''
  }
}

// Watch for loan data changes to populate form for editing
watch(() => props.loanData, (newData) => {
  if (newData && isEdit.value) {
    formData.value = {
      loan_id: newData.loan_id || null,
      title: newData.title || '',
      type: newData.type || null,
      reasons: newData.reasons || '',
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

const fetchLoanOptions = async () => {
  try {
    loadingLoans.value = true
    const result = await $api('/loan-options')
    loanOptions.value = result.data || []
  } catch (error) {
    console.error('Failed to fetch loan options:', error)
  } finally {
    loadingLoans.value = false
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
      loan_id: formData.value.loan_id,
      title: formData.value.title,
      type: formData.value.type,
      reasons: formData.value.reasons,
      amount: parseFloat(formData.value.amount)
    }

    if (isEdit.value) {
      // Update existing loan
      const result = await $api(`/employee-loans/${props.loanData.id}`, {
        method: 'PUT',
        body: payload,
      });
      emit('updated', result.data);
      $toast.success('Loan updated successfully');
    } else {
      // Create new loan
      const result = await $api('/employee-loans', {
        method: 'POST',
        body: payload,
      });
      emit('saved', result.data);
      $toast.success('Loan created successfully');
    }
    
    closeModal()
  } catch (error) {
    console.error('Error saving loan:', error)
    $toast.error(isEdit.value ? 'Failed to update loan' : 'Failed to create loan')
  } finally {
    loading.value = false
  }
}

// Watch for modal opening to fetch loan options
watch(() => props.modelValue, (val) => {
  if (val) {
    fetchLoanOptions()
  }
})
</script>
