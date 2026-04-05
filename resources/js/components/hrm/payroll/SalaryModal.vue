<template>
  <VDialog v-model="isOpen" max-width="500px" persistent>
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center pa-4">
        <span class="text-h6">{{ isEdit ? 'Edit Basic Salary' : 'Set Basic Salary' }}</span>
        <VBtn
          icon="tabler-x"
          variant="text"
          size="small"
          @click="closeModal"
        />
      </VCardTitle>
      
      <VDivider />
      
      <VCardText class="pa-6">
        <VForm ref="formRef" @submit.prevent="handleSubmit">
          <VRow>
            <!-- Payslip Type -->
            <VCol cols="12">
              
              <AppSelect
                v-model="formData.payslip_type_id"
                :items="payslipTypes"
                item-title="name"
                item-value="id"
                placeholder="Select Payslip Type"
                variant="outlined"
                label="Payslip Type*"
                :rules="[v => !!v || 'Payslip Type is required']"
                :loading="loadingPayslipTypes"
              />
            </VCol>
            
            <!-- Amount -->
            <VCol cols="12">
              <!-- <VLabel class="text-body-2 mb-2">
                Amount <span class="text-error">*</span>
              </VLabel> -->
              <AppTextField
                v-model="formData.amount"
                type="number"
                placeholder="Enter amount"
                variant="outlined"
                label="Amount*"
                :rules="[
                  v => !!v || 'Amount is required',
                  v => v > 0 || 'Amount must be greater than 0'
                ]"
                @input="formatAmount"
              />
            </VCol>
            
            <!-- Effective Date - Hidden, will be set to null -->
            <VCol cols="12" style="display: none;">
              <input type="hidden" v-model="formData.effective_date" />
            </VCol>
            <!-- Tax Applicable Switch -->
            <VCol cols="12">
              <VSwitch
                v-model="formData.is_tax_applicable"
                label="Apply Tax"
                inset
                color="primary"
              />
            </VCol>
            <!-- Tax Slab Mode Selection -->
            <VCol
              v-if="formData.is_tax_applicable"
              cols="12"
            >
              <AppSelect
                v-model="formData.tax_slab_mode"
                :items="[
                  { text: 'Default (Auto-select)', value: 'default' },
                  { text: 'Custom (Manual select)', value: 'custom' }
                ]"
                item-title="text"
                item-value="value"
                label="Tax Slab Mode"
                placeholder="Select mode"
                variant="outlined"
              />
            </VCol>

            <!-- Tax Slab Selection - only when custom -->
            <VCol
              v-if="formData.is_tax_applicable && formData.tax_slab_mode === 'custom'"
              cols="12"
            >

              <AppSelect
                v-model="formData.tax_slab_id"
                :items="taxSlabs"
                item-title="name"
                item-value="id"
                label="Tax Slab"
                placeholder="Pick a slab"
                :loading="loadingTaxSlabs"
                clearable
                :rules="[
                  v => formData.tax_slab_mode !== 'custom' || !!v || 'Tax slab is required when using custom mode'
                ]"
              />
              <small class="text-medium-emphasis">Pick any slab manually for this employee.</small>
            </VCol>

            <!-- Net Salary Preview -->
            <VCol cols="12">
              <VAlert
                variant="tonal"
                density="compact"
                color="info"
              >
                <div class="d-flex flex-column">
                  <strong>Preview</strong>
                  <span v-if="formData.is_tax_applicable">
                    Mode: {{ formData.tax_slab_mode === 'custom' ? 'Custom slab' : 'Default (auto)' }} |
                    Tax (monthly): {{ formatMoney(calculatedTax) }} |
                    Annual ≈ {{ formatMoney(annualTaxPreview) }} |
                    Net Salary: {{ formatMoney(netAmount) }}
                  </span>
                  <span v-else>
                    Tax not applied | Net Salary: {{ formatMoney(netAmount) }}
                  </span>
                  <span v-if="selectedSlab">
                    Slab: {{ selectedSlab.name }} • Annual range:
                    {{ formatMoney(selectedSlab.min_salary ?? selectedSlab.min_salary_annual) }} -
                    {{ selectedSlab.max_salary == null && selectedSlab.max_salary_annual == null ? 'No cap' : formatMoney(selectedSlab.max_salary ?? selectedSlab.max_salary_annual) }}
                    <br>
                    <small class="text-medium-emphasis">
                      Monthly ≈
                      {{ formatMoney(selectedSlab.min_salary_monthly ?? ((selectedSlab.min_salary ?? selectedSlab.min_salary_annual) / 12), { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                      -
                      {{ selectedSlab.max_salary_monthly == null && selectedSlab.max_salary == null && selectedSlab.max_salary_annual == null ? '—' : formatMoney(selectedSlab.max_salary_monthly ?? (((selectedSlab.max_salary ?? selectedSlab.max_salary_annual) || 0) / 12), { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </small>
                  </span>
                </div>
              </VAlert>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      
      <VDivider />
      
      <VCardActions class="pa-4">
        <VSpacer />
        <VBtn
          variant="outlined"
          color="error"
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
import { computed, ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  employeeId: {
    type: [String, Number],
    required: true
  },
  salaryData: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'saved', 'updated'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.salaryData)

const loading = ref(false)
const loadingPayslipTypes = ref(false)
const loadingTaxSlabs = ref(false)
const formRef = ref()
const payslipTypes = ref([])
const taxSlabs = ref([])

const formData = ref({
  payslip_type_id: null,
  amount: '',
  effective_date: null,
  status: true,
  is_tax_applicable: false,
  tax_slab_mode: 'default', // 'default' uses auto selection, 'custom' allows manual
  tax_slab_id: null,
})

const resetForm = () => {
  formData.value = {
    payslip_type_id: null,
    amount: '',
    effective_date: null,
    status: true,
    is_tax_applicable: false,
    tax_slab_mode: 'default',
    tax_slab_id: null,
  }
}

// Watch for salary data changes to populate form for editing
watch(() => props.salaryData, (newData) => {
  if (newData && isEdit.value) {
    formData.value = {
      payslip_type_id: newData.payslip_type_id || null,
      amount: newData.amount || '',
      effective_date: null, // Always set to null for new entries
      status: newData.status !== undefined ? newData.status : true,
      is_tax_applicable: !!newData.is_tax_applicable,
      tax_slab_mode: newData.tax_slab_id ? 'custom' : 'default',
      tax_slab_id: newData.tax_slab_id || null,
    }
  } else {
    resetForm()
  }
}, { immediate: true })

const toAnnual = amount => {
  const monthly = Number(amount || 0)
  if (!monthly) return 0
  return Number((monthly * 12).toFixed(2))
}

const isWithinSlab = (annualAmount, slab) => {
  const min = Number(slab.min_salary ?? slab.min_salary_annual ?? 0)
  const rawMax = slab.max_salary ?? slab.max_salary_annual
  const max = rawMax === null || rawMax === undefined ? Infinity : Number(rawMax)
  return annualAmount >= min && annualAmount <= max
}

const autoSelectedSlab = computed(() => {
  if (!formData.value.is_tax_applicable) return null
  const annualAmount = toAnnual(formData.value.amount)
  if (!annualAmount || !taxSlabs.value.length) return null

  return taxSlabs.value.find(slab => isWithinSlab(annualAmount, slab)) || null
})

const selectedSlab = computed(() => {
  if (!formData.value.is_tax_applicable) return null

  if (formData.value.tax_slab_mode === 'custom') {
    const manual = taxSlabs.value.find(slab => slab.id === formData.value.tax_slab_id)
    return manual || null
  }

  return autoSelectedSlab.value
})

const computeMonthlyTax = (monthlyAmount, slab) => {
  if (!monthlyAmount || !slab) return 0
  const annualAmount = toAnnual(monthlyAmount)
  const rate = Number(slab.tax_rate || 0)
  const fixedAnnual = Number(slab.fixed_amount ?? slab.fixed_amount_annual ?? 0)
  const thresholdAnnual = Number(slab.exceeding_threshold ?? slab.exceeding_threshold_annual ?? 0)
  const taxable = Math.max(0, annualAmount - thresholdAnnual)
  const percentPortion = taxable * (rate / 100)
  const annualTax = fixedAnnual + percentPortion
  return Number((annualTax / 12).toFixed(2))
}

const calculatedTax = computed(() => {
  if (!formData.value.is_tax_applicable) return 0
  const slab = selectedSlab.value
  const amount = parseFloat(formData.value.amount || 0)
  if (!slab || !amount) return 0
  return computeMonthlyTax(amount, slab)
})

const annualTaxPreview = computed(() => Number((calculatedTax.value || 0) * 12).toFixed(2))

const netAmount = computed(() => {
  const amount = parseFloat(formData.value.amount || 0)
  return Number((amount - calculatedTax.value).toFixed(2))
})

watch(() => [formData.value.amount, formData.value.is_tax_applicable, formData.value.tax_slab_mode], () => {
  if (!formData.value.is_tax_applicable) {
    formData.value.tax_slab_id = null
    formData.value.tax_slab_mode = 'default'
    return
  }

  if (formData.value.tax_slab_mode === 'default' && autoSelectedSlab.value) {
    formData.value.tax_slab_id = autoSelectedSlab.value.id
  }

  if (formData.value.tax_slab_mode === 'custom' && !formData.value.tax_slab_id) {
    formData.value.tax_slab_id = null
  }
})

const formatMoney = (value, options = {}) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: options.minimumFractionDigits ?? 2,
    maximumFractionDigits: options.maximumFractionDigits ?? 2,
  }).format(amount)
}

const formatAmount = event => {
  const value = event?.target?.value ?? ''
  const numericValue = value.toString().replace(/[^0-9.]/g, '')
  formData.value.amount = numericValue
}

// Fetch PayslipTypes
const fetchPayslipTypes = async () => {
  try {
    loadingPayslipTypes.value = true
    const result = await $api('/payslip-types')
    payslipTypes.value = result.data || []
  } catch (error) {
    console.error('Failed to fetch payslip types:', error)
  } finally {
    loadingPayslipTypes.value = false
  }
}

const fetchTaxSlabs = async () => {
  try {
    loadingTaxSlabs.value = true
    const result = await $api('/payroll/tax-slabs')
    taxSlabs.value = result.data || []
  } catch (error) {
    console.error('Failed to fetch tax slabs:', error)
  } finally {
    loadingTaxSlabs.value = false
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

  // Additional validation: prevent creating new salary if one already exists
  if (!isEdit.value) {
    // Check if employee already has a salary
    try {
      const result = await $api(`/salaries/employee/${props.employeeId}/current`);
      if (result.data && parseFloat(result.data.amount) > 0) {
        $toast.error('Salary already exists for this employee. Please edit the existing salary instead.');
        return;
      }
    } catch (error) {
      console.error('Error checking existing salary:', error);
    }
  }

  loading.value = true
  
  try {
    const payload = {
      employee_id: props.employeeId,
      payslip_type_id: formData.value.payslip_type_id,
      amount: parseFloat(formData.value.amount),
      effective_date: formData.value.effective_date,
      status: formData.value.status,
      is_tax_applicable: formData.value.is_tax_applicable,
      tax_slab_id: formData.value.is_tax_applicable && formData.value.tax_slab_mode === 'custom'
        ? formData.value.tax_slab_id
        : null,
    }

    if (isEdit.value) {
      // Update existing salary
      const result = await $api(`/salaries/${props.salaryData.id}`, {
        method: 'PUT',
        body: payload,
      });
      
      emit('updated', result.data);
      $toast.success('Salary updated successfully');
    } else {
      // Create new salary
      const result = await $api('/salaries', {
        method: 'POST',
        body: payload,
      });
      
      emit('saved', result.data);
      $toast.success('Salary created successfully');
    }
    
    closeModal()
  } catch (error) {
    console.error('Error saving salary:', error)
    $toast.error(isEdit.value ? 'Failed to update salary' : 'Failed to create salary')
  } finally {
    loading.value = false
  }
}

// Watch for modal opening to fetch PayslipTypes
watch(() => props.modelValue, (val) => {
  if (val) {
    fetchPayslipTypes()
    fetchTaxSlabs()
  }
})
</script>
