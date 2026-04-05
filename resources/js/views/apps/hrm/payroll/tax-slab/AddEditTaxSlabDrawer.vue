<script setup>
import { computed, defineEmits, defineProps, nextTick, ref, watch } from 'vue'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import AppDrawerHeaderSection from '@/@core/components/AppDrawerHeaderSection.vue'
import AppTextField from '@/@core/components/app-form-elements/AppTextField.vue'

const props = defineProps({
  isDrawerOpen: { type: Boolean, required: true },
  loading: { type: Boolean, default: false },
  slab: { type: Object, default: null },
})

const emit = defineEmits(['update:isDrawerOpen', 'submit'])

const isFormValid = ref(false)
const refForm = ref()

const form = ref({
  id: null,
  name: '',
  min_salary: 0,
  max_salary: null,
  tax_rate: 0,
  fixed_amount: 0,
  exceeding_threshold: 0,
})

const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    min_salary: 0,
    max_salary: null,
    tax_rate: 0,
    fixed_amount: 0,
    exceeding_threshold: 0,
  }
}

const monthlyPreview = computed(() => {
  const toMonthly = value => Number(((Number(value ?? 0)) / 12).toFixed(2))
  return {
    min: toMonthly(form.value.min_salary),
    max: form.value.max_salary === null || form.value.max_salary === ''
      ? null
      : toMonthly(form.value.max_salary),
    fixed: toMonthly(form.value.fixed_amount),
    threshold: toMonthly(form.value.exceeding_threshold),
  }
})

const formatCurrency = value => new Intl.NumberFormat('en-US', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
}).format(Number(value ?? 0))

watch(
  () => props.slab,
  val => {
    if (val) {
      form.value = {
        id: val.id,
        name: val.name || '',
        min_salary: val.min_salary ?? 0,
        max_salary: val.max_salary ?? null,
        tax_rate: val.tax_rate ?? 0,
        fixed_amount: val.fixed_amount ?? 0,
        exceeding_threshold: val.exceeding_threshold ?? 0,
      }
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const close = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.reset()
    refForm.value?.resetValidation()
    resetForm()
  })
}

const onSubmit = async () => {
  const { valid } = await refForm.value.validate()
  if (!valid) return

  emit('submit', { ...form.value })
  close()
}

const handleDrawerModelValueUpdate = val => {
  emit('update:isDrawerOpen', val)
}
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="420"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="handleDrawerModelValueUpdate"
  >
    <AppDrawerHeaderSection :title="form.id ? 'Edit Tax Slab' : 'Add Tax Slab'" @cancel="close" />
    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <AppTextField v-model="form.name" :rules="[requiredValidator]" label="Slab Name" />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.min_salary"
                  :rules="[requiredValidator]"
                  type="number"
                  label="Min Salary (Annual)"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.max_salary"
                  type="number"
                  label="Max Salary (Annual, optional)"
                  placeholder="Leave empty for no upper limit"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.tax_rate"
                  :rules="[requiredValidator]"
                  type="number"
                  label="Tax Rate (%)"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.fixed_amount"
                  :rules="[requiredValidator]"
                  type="number"
                  label="Fixed Amount (Annual)"
                />
              </VCol>

              <VCol cols="12" md="6">
                <AppTextField
                  v-model.number="form.exceeding_threshold"
                  :rules="[requiredValidator, numberRule]"
                  type="number"
                  label="Exceeding Threshold (Annual)"
                  placeholder="Annual salary amount above which the rate applies"
                />
              </VCol>

              <VCol cols="12">
                <VAlert type="info" variant="tonal">
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium">Monthly equivalents</span>
                    <span>Min salary ≈ {{ formatCurrency(monthlyPreview.min) }}</span>
                    <span v-if="monthlyPreview.max !== null">Max salary ≈ {{ formatCurrency(monthlyPreview.max) }}</span>
                    <span>Fixed deduction ≈ {{ formatCurrency(monthlyPreview.fixed) }}</span>
                    <span>Threshold ≈ {{ formatCurrency(monthlyPreview.threshold) }}</span>
                  </div>
                </VAlert>
              </VCol>

              <VCol cols="12" class="d-flex justify-end gap-4 mt-6">
                <VBtn type="submit" :loading="props.loading">
                  {{ form.id ? 'Update' : 'Save' }}
                </VBtn>
                <VBtn type="reset" variant="tonal" color="error" @click="close">Cancel</VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>

