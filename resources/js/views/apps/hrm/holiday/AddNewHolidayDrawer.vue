<script setup>
import { PerfectScrollbar } from "vue3-perfect-scrollbar"

const props = defineProps({
  isOpen: Boolean,
  forView: Boolean,
  holiday: Object,
  loading: Boolean,
})

const emit = defineEmits(["update:isOpen", "submit"])

const isFormValid = ref(false)
const refForm = ref()

const form = ref({
  id: null,
  name: "",
  date: null,
  is_recurring: false,
  description: "",
})

// Convert date string to Date object for picker
const formatDateForPicker = (dateString) => {
  if (!dateString) return null
  
  // If it's already a Date object, return as is
  if (dateString instanceof Date) {
    return dateString
  }
  
  // Create Date object from string
  const date = new Date(dateString)
  return !isNaN(date.getTime()) ? date : null
}

// Reset form when opening/closing drawer
watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      if (props.holiday) {
        form.value = {
          ...props.holiday,
          date: formatDateForPicker(props.holiday.date),
          description: props.holiday.description || ""
        }
        nextTick(() => refForm.value?.resetValidation())
      } else {
        refForm.value?.reset()
        form.value = { 
          id: null, 
          name: "", 
          date: null, 
          is_recurring: false, 
          description: "" 
        }
      }
    }
  }
)

const onSubmit = () => {
  refForm.value?.validate().then(({ valid }) => {
    if (valid) {
      const formData = {
        name: form.value.name,
        date: form.value.date instanceof Date 
          ? form.value.date.toISOString().split('T')[0] 
          : form.value.date,
        is_recurring: form.value.is_recurring,
        description: form.value.description,
      }

      if (form.value.id) {
        formData.id = form.value.id
      }

      emit("submit", formData)
    }
  })
}

const closeForm = () => {
  emit("update:isOpen", false)
}

// Date validation rule
const dateValidator = (value) => {
  if (!value) return 'This field is required'
  if (value instanceof Date && !isNaN(value.getTime())) return true
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return true
  return 'Please enter a valid date'
}

</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isOpen"
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <AppDrawerHeaderSection
      :title="form.id ? 'Update Holiday' : 'Add New Holiday'"
      @cancel="closeForm"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VForm ref="refForm" v-model="isFormValid" @submit.prevent="onSubmit">
            <VRow>
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  :rules="[requiredValidator]"
                  label="Holiday Name"
                  placeholder="Enter holiday name"
                />
              </VCol>

              <VCol cols="12">
                <AppDateTimePicker
                  v-model="form.date"
                  :rules="[dateValidator]"
                  label="Holiday Date"
                  placeholder="Select holiday date"
                />
              </VCol>

              <VCol cols="12">
                <VCheckbox
                  v-model="form.is_recurring"
                  label="Recurring Holiday"
                  hint="Check if this holiday occurs every year"
                />
              </VCol>

              <VCol cols="12">
                <AppTextarea
                  v-model="form.description"
                  label="Description"
                  placeholder="Enter holiday description"
                  rows="3"
                />
              </VCol>

              <VCol cols="12" class="d-flex justify-end gap-4 mt-6" v-if="!props.forView">
                <VBtn type="submit" :loading="props.loading"> Save </VBtn>
                <VBtn
                  type="reset"
                  variant="tonal"
                  color="error"
                  @click="closeForm"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
